<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\FraudController;

// =====================
// Public Routes
// =====================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// =====================
// Protected Routes
// =====================
Route::middleware('auth:sanctum')->group(function () {

    // --- Auth ---
    Route::get('/me',             [AuthController::class, 'userProfile']);
    Route::put('/profile/update', [AuthController::class, 'updateProfile']);
    Route::get('/users',          [AuthController::class, 'index']);
    Route::get('/users/{id}',     [AuthController::class, 'show']);
    Route::delete('/users/{id}',  [AuthController::class, 'destroy']);
    Route::get('/customers',      [AuthController::class, 'customers']);
    Route::get('/admins',         [AuthController::class, 'admins']);
    Route::post('/logout',        [AuthController::class, 'logout']);

    // --- Accounts ---
    Route::get('/account/my',             [AccountController::class, 'myAccount']);
    Route::get('/accounts',               [AccountController::class, 'index']);
    Route::get('/accounts/{id}',          [AccountController::class, 'show']);
    Route::put('/accounts/{id}/freeze',   [AccountController::class, 'freeze']);
    Route::put('/accounts/{id}/activate', [AccountController::class, 'activate']);
    Route::put('/accounts/{id}/close',    [AccountController::class, 'close']);

    // --- Transactions ---
    Route::post('/transactions/deposit',  [TransactionController::class, 'deposit']);
    Route::post('/transactions/withdraw', [TransactionController::class, 'withdraw']);
    Route::post('/transactions/transfer', [TransactionController::class, 'transfer']);
    Route::get('/transactions/my',        [TransactionController::class, 'myTransactions']);
    Route::get('/transactions',           [TransactionController::class, 'index']);
    Route::get('/transactions/{id}',      [TransactionController::class, 'show']);

    // --- Fraud ---
    Route::post('/fraud/analyze/{transaction_id}', [FraudController::class, 'analyze']);
    Route::get('/fraud',                           [FraudController::class, 'index']);
    Route::get('/fraud/flagged',                   [FraudController::class, 'flagged']);
    Route::get('/fraud/{id}',                      [FraudController::class, 'show']);

    // --- Admin ---
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard',           [AdminController::class, 'dashboard']);
        Route::get('/users',               [AdminController::class, 'users']);
        Route::put('/users/{id}/suspend',  [AdminController::class, 'suspendUser']);
        Route::put('/users/{id}/activate', [AdminController::class, 'activateUser']);
        Route::get('/transactions',        [AdminController::class, 'transactions']);
        Route::get('/flagged',             [AdminController::class, 'flagged']);
        Route::get('/audit-logs',          [AdminController::class, 'auditLogs']);
        Route::get('/report',              [AdminController::class, 'report']);
    });
});