<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Transaction;
use App\Models\Account;

class TransactionController extends Controller
{
    // Deposit
    public function deposit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount'      => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $account = Account::where('user_id', Auth::id())->first();

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
            ], 404);
        }

        // Check account is active
        if ($account->status_id != 3) {
            return response()->json([
                'success' => false,
                'message' => 'Account is not active.',
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Update balance
            $account->balance += $request->amount;
            $account->save();

            // Record transaction
            $transaction = Transaction::create([
                'account_id'  => $account->id,
                'type'        => 'deposit',
                'amount'      => $request->amount,
                'status_id'   => 6, // Completed
                'description' => $request->description ?? 'Deposit',
                'date'        => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Deposit successful.',
                'data'    => [
                    'transaction_id' => $transaction->id,
                    'type'           => $transaction->type,
                    'amount'         => $transaction->amount,
                    'balance'        => $account->balance,
                    'description'    => $transaction->description,
                    'date'           => $transaction->date,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Transaction failed. Please try again.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // Withdrawal
    public function withdraw(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount'      => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $account = Account::where('user_id', Auth::id())->first();

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
            ], 404);
        }

        // Check account is active
        if ($account->status_id != 3) {
            return response()->json([
                'success' => false,
                'message' => 'Account is not active.',
            ], 403);
        }

        // Check sufficient balance
        if ($account->balance < $request->amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Deduct balance
            $account->balance -= $request->amount;
            $account->save();

            // Record transaction
            $transaction = Transaction::create([
                'account_id'  => $account->id,
                'type'        => 'withdrawal',
                'amount'      => $request->amount,
                'status_id'   => 6, // Completed
                'description' => $request->description ?? 'Withdrawal',
                'date'        => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal successful.',
                'data'    => [
                    'transaction_id' => $transaction->id,
                    'type'           => $transaction->type,
                    'amount'         => $transaction->amount,
                    'balance'        => $account->balance,
                    'description'    => $transaction->description,
                    'date'           => $transaction->date,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Transaction failed. Please try again.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // Transfer
    public function transfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_number' => 'required|string|exists:accounts,account_number',
            'amount'         => 'required|numeric|min:1',
            'description'    => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $senderAccount   = Account::where('user_id', Auth::id())->first();
        $receiverAccount = Account::where('account_number', $request->account_number)->first();

        if (!$senderAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Your account not found.',
            ], 404);
        }

        // Check not transferring to own account
        if ($senderAccount->account_number === $request->account_number) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot transfer to your own account.',
            ], 400);
        }

        // Check sender account is active
        if ($senderAccount->status_id != 3) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not active.',
            ], 403);
        }

        // Check receiver account is active
        if ($receiverAccount->status_id != 3) {
            return response()->json([
                'success' => false,
                'message' => 'Recipient account is not active.',
            ], 403);
        }

        // Check sufficient balance
        if ($senderAccount->balance < $request->amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Deduct from sender
            $senderAccount->balance -= $request->amount;
            $senderAccount->save();

            // Add to receiver
            $receiverAccount->balance += $request->amount;
            $receiverAccount->save();

            // Record transaction
            $transaction = Transaction::create([
                'account_id'  => $senderAccount->id,
                'type'        => 'transfer',
                'amount'      => $request->amount,
                'status_id'   => 6, // Completed
                'description' => $request->description ?? 'Transfer to ' . $request->account_number,
                'date'        => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transfer successful.',
                'data'    => [
                    'transaction_id'  => $transaction->id,
                    'type'            => $transaction->type,
                    'amount'          => $transaction->amount,
                    'balance'         => $senderAccount->balance,
                    'transferred_to'  => $request->account_number,
                    'description'     => $transaction->description,
                    'date'            => $transaction->date,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Transaction failed. Please try again.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // Get own transaction history (customer)
    public function myTransactions()
    {
        $account = Account::where('user_id', Auth::id())->first();

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
            ], 404);
        }

        $transactions = Transaction::with('status')
            ->where('account_id', $account->id)
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
                ];
            }),
        ], 200);
    }

    // Get all transactions (admin)
    public function index()
    {
        $transactions = Transaction::with(['account.user', 'status'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'All transactions fetched successfully.',
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
                    ],
                ];
            }),
        ], 200);
    }

    // Get single transaction (admin)
    public function show($id)
    {
        $transaction = Transaction::with(['account.user', 'status'])->find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaction fetched successfully.',
            'data'    => [
                'transaction_id' => $transaction->id,
                'type'           => $transaction->type,
                'amount'         => $transaction->amount,
                'status'         => $transaction->status->status_name,
                'description'    => $transaction->description,
                'date'           => $transaction->date,
                'account'        => [
                    'account_number' => $transaction->account->account_number,
                    'owner'          => $transaction->account->user->name,
                ],
            ],
        ], 200);
    }
}