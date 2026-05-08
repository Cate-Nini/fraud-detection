<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Transaction;
use App\Models\FraudAnalysis;
use App\Models\Account;

class FraudController extends Controller
{
    // Analyze a transaction for fraud
    public function analyze($transaction_id)
    {
        $transaction = Transaction::with('account')->find($transaction_id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
            ], 404);
        }

        // Check if already analyzed
        $existing = FraudAnalysis::where('transaction_id', $transaction_id)->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction already analyzed.',
                'data'    => $existing,
            ], 409);
        }

        // Get account details
        $account = $transaction->account;

        // Calculate account age in days
        $accountAgeDays = $account->created_at->diffInDays(now());

        // Calculate transaction frequency (transactions today)
        $frequency = Transaction::where('account_id', $account->id)
            ->whereDate('created_at', today())
            ->count();

        // Map transaction type to number
        $typeMap = [
            'deposit'    => 0,
            'withdrawal' => 1,
            'transfer'   => 2,
        ];

        // Prepare features for ML model
        $features = [
            'amount'           => $transaction->amount,
            'transaction_type' => $typeMap[$transaction->type] ?? 0,
            'hour_of_day'      => now()->hour,
            'frequency'        => $frequency,
            'account_age_days' => $accountAgeDays,
        ];

        // Send to Python Flask ML API
        try {
            $response = Http::post('http://127.0.0.1:5000/predict', $features);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ML API error.',
                ], 500);
            }

            $result = $response->json();

            // Store fraud analysis result
            $fraudAnalysis = FraudAnalysis::create([
                'transaction_id' => $transaction->id,
                'prediction'     => $result['prediction'],
                'anomaly_score'  => $result['anomaly_score'],
            ]);

            // If suspicious update transaction status to Flagged
            if ($result['prediction'] === 'Suspicious') {
                $transaction->status_id = 8; // Flagged
                $transaction->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Fraud analysis completed.',
                'data'    => [
                    'transaction_id' => $transaction->id,
                    'type'           => $transaction->type,
                    'amount'         => $transaction->amount,
                    'prediction'     => $result['prediction'],
                    'anomaly_score'  => $result['anomaly_score'],
                    'is_fraud'       => $result['is_fraud'],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not connect to ML API.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // Get all flagged transactions (admin)
    public function flagged()
    {
        $flagged = FraudAnalysis::with(['transaction.account.user'])
            ->where('prediction', 'Suspicious')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Flagged transactions fetched successfully.',
            'data'    => $flagged->map(function ($f) {
                return [
                    'fraud_id'       => $f->id,
                    'transaction_id' => $f->transaction_id,
                    'prediction'     => $f->prediction,
                    'anomaly_score'  => $f->anomaly_score,
                    'transaction'    => [
                        'type'   => $f->transaction->type,
                        'amount' => $f->transaction->amount,
                        'date'   => $f->transaction->date,
                    ],
                    'account' => [
                        'account_number' => $f->transaction->account->account_number,
                        'owner'          => $f->transaction->account->user->name,
                        'email'          => $f->transaction->account->user->email,
                    ],
                ];
            }),
        ], 200);
    }

    // Get all fraud analysis results (admin)
    public function index()
    {
        $analyses = FraudAnalysis::with(['transaction.account.user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Fraud analyses fetched successfully.',
            'data'    => $analyses->map(function ($f) {
                return [
                    'fraud_id'       => $f->id,
                    'transaction_id' => $f->transaction_id,
                    'prediction'     => $f->prediction,
                    'anomaly_score'  => $f->anomaly_score,
                    'transaction'    => [
                        'type'   => $f->transaction->type,
                        'amount' => $f->transaction->amount,
                        'date'   => $f->transaction->date,
                    ],
                    'account' => [
                        'account_number' => $f->transaction->account->account_number,
                        'owner'          => $f->transaction->account->user->name,
                    ],
                ];
            }),
        ], 200);
    }

    // Get single fraud analysis
    public function show($id)
    {
        $analysis = FraudAnalysis::with(['transaction.account.user'])->find($id);

        if (!$analysis) {
            return response()->json([
                'success' => false,
                'message' => 'Fraud analysis not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fraud analysis fetched successfully.',
            'data'    => [
                'fraud_id'       => $analysis->id,
                'transaction_id' => $analysis->transaction_id,
                'prediction'     => $analysis->prediction,
                'anomaly_score'  => $analysis->anomaly_score,
                'transaction'    => [
                    'type'   => $analysis->transaction->type,
                    'amount' => $analysis->transaction->amount,
                    'date'   => $analysis->transaction->date,
                ],
                'account' => [
                    'account_number' => $analysis->transaction->account->account_number,
                    'owner'          => $analysis->transaction->account->user->name,
                ],
            ],
        ], 200);
    }
}