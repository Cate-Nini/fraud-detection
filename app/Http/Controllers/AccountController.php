<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Account;
use App\Models\User;

class AccountController extends Controller
{
    // Get own account details (customer)
    public function myAccount()
    {
        $user    = Auth::user();
        $account = Account::with('status')
            ->where('user_id', $user->id)
            ->first();

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'No account found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Account retrieved successfully.',
            'data'    => [
                'account_number' => $account->account_number,
                'balance'        => $account->balance,
                'status'         => $account->status->status_name,
                'created_at'     => $account->created_at,
            ],
        ], 200);
    }

    // Get all accounts (admin)
    public function index()
    {
        $accounts = Account::with(['user', 'status'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Accounts fetched successfully.',
            'data'    => $accounts->map(function ($account) {
                return [
                    'account_number' => $account->account_number,
                    'balance'        => $account->balance,
                    'status'         => $account->status->status_name,
                    'owner'          => [
                        'id'    => $account->user->id,
                        'name'  => $account->user->name,
                        'email' => $account->user->email,
                    ],
                    'created_at' => $account->created_at,
                ];
            }),
        ], 200);
    }

    // Get single account by id (admin)
    public function show($id)
    {
        $account = Account::with(['user', 'status'])->find($id);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Account fetched successfully.',
            'data'    => [
                'account_number' => $account->account_number,
                'balance'        => $account->balance,
                'status'         => $account->status->status_name,
                'owner'          => [
                    'id'    => $account->user->id,
                    'name'  => $account->user->name,
                    'email' => $account->user->email,
                ],
                'created_at' => $account->created_at,
            ],
        ], 200);
    }

    // Freeze account (admin)
    public function freeze($id)
    {
        $account = Account::find($id);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
            ], 404);
        }

        $account->status_id = 4; // Frozen
        $account->save();

        return response()->json([
            'success' => true,
            'message' => 'Account frozen successfully.',
        ], 200);
    }

    // Activate account (admin)
    public function activate($id)
    {
        $account = Account::find($id);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
            ], 404);
        }

        $account->status_id = 3; // Active (account category)
        $account->save();

        return response()->json([
            'success' => true,
            'message' => 'Account activated successfully.',
        ], 200);
    }

    // Close account (admin)
    public function close($id)
    {
        $account = Account::find($id);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
            ], 404);
        }

        $account->status_id = 5; // Closed
        $account->save();

        return response()->json([
            'success' => true,
            'message' => 'Account closed successfully.',
        ], 200);
    }
}