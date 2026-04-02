<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthenticateVendor
{
    public function handle(Request $request, Closure $next)
    {
        // First try the vendor guard
        if (auth('vendor')->check()) {
            return $next($request);
        }

        // If not vendor, try the api guard (for backwards compatibility)
        if (auth('api')->check()) {
            return $next($request);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Unauthenticated',
        ], 401);
    }
}
