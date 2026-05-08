<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\FraudAnalysis;
use App\Models\AuditLog;

class AdminController extends Controller
{
    // =====================
    // DASHBOARD STATS
    // =====================
    public function dashboard()
    {
        $totalUsers        = User::count();
        $totalCustomers    = User::whereHas('role', fn($q) => $q->where('role_name', 'Customer'))->count();
        $totalAccounts     = Account::count();
        $totalTransactions = Transaction::count();
        $totalDeposits     = Transaction::where('type', 'deposit')->sum('amount');
        $totalWithdrawals  = Transaction::where('type', 'withdrawal')->sum('amount');
        $totalTransfers    = Transaction::where('type', 'transfer')->sum('amount');
        $flaggedCount      = FraudAnalysis::where('prediction', 'Suspicious')->count();
        $totalBalance      = Account::sum('balance');

        return response()->json([
            'success' => true,
            'message' => 'Dashboard stats fetched successfully.',
            'data'    => [
                'total_users'        => $totalUsers,
                'total_customers'    => $totalCustomers,
                'total_accounts'     => $totalAccounts,
                'total_transactions' => $totalTransactions,
                'total_deposits'     => $totalDeposits,
                'total_withdrawals'  => $totalWithdrawals,
                'total_transfers'    => $totalTransfers,
                'flagged_transactions'=> $flaggedCount,
                'total_balance'      => $totalBalance,
            ],
        ], 200);
    }

    // =====================
    // USER MANAGEMENT
    // =====================

    // Get all users
    public function users()
    {
        $users = User::with(['role', 'status'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Users fetched successfully.',
            'data'    => $users->map(function ($user) {
                return [
                    'id'     => $user->id,
                    'name'   => $user->name,
                    'email'  => $user->email,
                    'phone'  => $user->phone,
                    'role'   => $user->role->role_name,
                    'status' => $user->status->status_name,
                ];
            }),
        ], 200);
    }

    // Suspend user
    public function suspendUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $user->status_id = 2; // Suspended
        $user->save();

        // Log action
        $this->log('Suspended user: ' . $user->email);

        return response()->json([
            'success' => true,
            'message' => 'User suspended successfully.',
        ], 200);
    }

    // Activate user
    public function activateUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $user->status_id = 1; // Active
        $user->save();

        // Log action
        $this->log('Activated user: ' . $user->email);

        return response()->json([
            'success' => true,
            'message' => 'User activated successfully.',
        ], 200);
    }

    // =====================
    // TRANSACTION MANAGEMENT
    // =====================

    // Get all transactions with fraud analysis
    public function transactions()
    {
        $transactions = Transaction::with(['account.user', 'status', 'fraudAnalysis'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Transactions fetched successfully.',
            'data'    => $transactions->map(function ($t) {
                return [
                    'transaction_id' => $t->id,
                    'type'           => $t->type,
                    'amount'         => $t->amount,
                    'status'         => $t->status->status_name,
                    'description'    => $t->description,
                    'date'           => $t->date,
                    'account'        => [
                        'account_number' => $t->account->account_number,
                        'owner'          => $t->account->user->name,
                        'email'          => $t->account->user->email,
                    ],
                    'fraud_analysis' => $t->fraudAnalysis ? [
                        'prediction'    => $t->fraudAnalysis->prediction,
                        'anomaly_score' => $t->fraudAnalysis->anomaly_score,
                    ] : 'Not analyzed',
                ];
            }),
        ], 200);
    }

    // =====================
    // FRAUD MANAGEMENT
    // =====================

    // Get all flagged transactions
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

    // =====================
    // AUDIT LOGS
    // =====================

    // Get all audit logs
    public function auditLogs()
    {
        $logs = AuditLog::with('admin')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Audit logs fetched successfully.',
            'data'    => $logs->map(function ($log) {
                return [
                    'log_id'    => $log->id,
                    'action'    => $log->action,
                    'admin'     => $log->admin->name,
                    'timestamp' => $log->timestamp,
                ];
            }),
        ], 200);
    }

    // =====================
    // REPORTS
    // =====================
    public function report()
    {
        $totalDeposits    = Transaction::where('type', 'deposit')->sum('amount');
        $totalWithdrawals = Transaction::where('type', 'withdrawal')->sum('amount');
        $totalTransfers   = Transaction::where('type', 'transfer')->sum('amount');
        $totalFlagged     = FraudAnalysis::where('prediction', 'Suspicious')->count();
        $totalNormal      = FraudAnalysis::where('prediction', 'Normal')->count();

        // Recent flagged transactions
        $recentFlagged = FraudAnalysis::with(['transaction.account.user'])
            ->where('prediction', 'Suspicious')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Report generated successfully.',
            'data'    => [
                'summary' => [
                    'total_deposits'    => $totalDeposits,
                    'total_withdrawals' => $totalWithdrawals,
                    'total_transfers'   => $totalTransfers,
                    'total_flagged'     => $totalFlagged,
                    'total_normal'      => $totalNormal,
                ],
                'recent_flagged' => $recentFlagged->map(function ($f) {
                    return [
                        'transaction_id' => $f->transaction_id,
                        'amount'         => $f->transaction->amount,
                        'type'           => $f->transaction->type,
                        'owner'          => $f->transaction->account->user->name,
                        'anomaly_score'  => $f->anomaly_score,
                        'date'           => $f->transaction->date,
                    ];
                }),
            ],
        ], 200);
    }

    // =====================
    // PRIVATE HELPER
    // =====================
    private function log($action)
    {
        AuditLog::create([
            'admin_id'  => Auth::id(),
            'action'    => $action,
            'timestamp' => now(),
        ]);
    }
}