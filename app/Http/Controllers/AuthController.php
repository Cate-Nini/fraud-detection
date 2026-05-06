<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Account;

class AuthController extends Controller
{
    // Register user
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone'    => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => Hash::make($request->password),
            'role_id'   => 2, // Customer
            'status_id' => 1, // Active
        ]);

        // Auto create bank account for new customer
        Account::create([
            'user_id'        => $user->id,
            'account_number' => 'ABC' . str_pad($user->id, 7, '0', STR_PAD_LEFT),
            'balance'        => 0.00,
            'status_id'      => 3, // Active (account category)
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role'  => $user->role->role_name,
            ],
        ], 201);
    }

    // Login user
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role'  => $user->role->role_name,
            ],
        ], 200);
    }

    // Get authenticated user profile
    public function userProfile(Request $request)
    {
        $user = $request->user()->load(['role', 'status']);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        if (!$user->role) {
            return response()->json([
                'success' => false,
                'message' => 'User has no role assigned',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'User profile retrieved successfully',
            'data'    => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'phone'  => $user->phone,
                'status' => $user->status->status_name,
                'role'   => [
                    'id'   => $user->role->id,
                    'name' => $user->role->role_name,
                ],
            ],
        ], 200);
    }

    // Get all users
    public function index()
    {
        $users = User::with(['role', 'status'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Users fetched successfully',
            'data'    => $users,
        ], 200);
    }

    // Get single user
    public function show($id)
    {
        $user = User::with(['role', 'status'])->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User fetched successfully.',
            'data'    => $user,
        ], 200);
    }

    // Update authenticated user profile
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        if (!$request->hasAny(['name', 'email', 'password', 'phone', 'role_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'At least one field must be provided for update.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|required|string|max:255',
            'email'    => 'sometimes|required|string|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|string|min:6',
            'phone'    => 'sometimes|string|max:20',
            'role_id'  => 'sometimes|integer|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if ($request->has('name'))     $user->name     = $request->name;
        if ($request->has('email'))    $user->email    = $request->email;
        if ($request->has('password')) $user->password = Hash::make($request->password);
        if ($request->has('phone'))    $user->phone    = $request->phone;
        if ($request->has('role_id'))  $user->role_id  = $request->role_id;

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user'    => $user,
        ], 200);
    }

    // Get customers only
    public function customers()
    {
        $customers = User::with(['role', 'status'])
            ->whereHas('role', fn($q) => $q->where('role_name', 'Customer'))
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Customers fetched successfully',
            'data'    => $customers,
        ], 200);
    }

    // Get admins only
    public function admins()
    {
        $admins = User::with(['role', 'status'])
            ->whereHas('role', fn($q) => $q->where('role_name', 'Admin'))
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Admins fetched successfully',
            'data'    => $admins,
        ], 200);
    }

    // Delete user
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ], 200);
    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}