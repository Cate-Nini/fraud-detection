<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'Welcome to ABC Microfinance Banking System API',
    ]);
});