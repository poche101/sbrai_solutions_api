<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsVerified
{
    /**
     * Handle an incoming request.
     * * This middleware ensures the user has verified both their email
     * and their phone number before proceeding.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Check if user is authenticated (Sanctum check)
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // 2. Check Email Verification
        if (!$user->email_verified_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your email address is not verified.',
                'code' => 'EMAIL_NOT_VERIFIED'
            ], 403);
        }

        // 3. Check Phone Verification
        if (!$user->phone_verified_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your phone number is not verified.',
                'code' => 'PHONE_NOT_VERIFIED'
            ], 403);
        }

        // 4. Everything is good, move to the next request
        return $next($request);
    }
}
