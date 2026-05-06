<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || !Auth::user()->isCustomer()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Customer access only.',
            ], 403);
        }

        return $next($request);
    }
}