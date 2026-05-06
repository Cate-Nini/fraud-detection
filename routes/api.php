<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;

Route::post('/register',  [AuthController::class, 'register']);
Route::post('/login',     [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me',               [AuthController::class, 'userProfile']);
    Route::put('/profile/update',   [AuthController::class, 'updateProfile']);
    Route::get('/users',            [AuthController::class, 'index']);
    Route::get('/users/{id}',       [AuthController::class, 'show']);
    Route::delete('/users/{id}',    [AuthController::class, 'destroy']);
    Route::get('/customers',        [AuthController::class, 'customers']);
    Route::get('/admins',           [AuthController::class, 'admins']);
    Route::post('/logout',          [AuthController::class, 'logout']);

      // Customer - own account
    Route::get('/account/my',          [AccountController::class, 'myAccount']);

    // Admin - manage all accounts
    Route::get('/accounts',            [AccountController::class, 'index']);
    Route::get('/accounts/{id}',       [AccountController::class, 'show']);
    Route::put('/accounts/{id}/freeze',   [AccountController::class, 'freeze']);
    Route::put('/accounts/{id}/activate', [AccountController::class, 'activate']);
    Route::put('/accounts/{id}/close',    [AccountController::class, 'close']);

    Route::middleware('auth:sanctum')->group(function () {
    // Customer transaction routes
    Route::post('/transactions/deposit',  [TransactionController::class, 'deposit']);
    Route::post('/transactions/withdraw', [TransactionController::class, 'withdraw']);
    Route::post('/transactions/transfer', [TransactionController::class, 'transfer']);
    Route::get('/transactions/my',        [TransactionController::class, 'myTransactions']);

    // Admin transaction routes
    Route::get('/transactions',           [TransactionController::class, 'index']);
    Route::get('/transactions/{id}',      [TransactionController::class, 'show']);
    });
});