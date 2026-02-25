<?php

namespace App\Http\Controllers\api\buyers;;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class AuthController extends Controller
{
    /**
     * Handle Standard User Registration
     */
    public function register(Request $request)
    {
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'phone'    => 'required|string|max:20',
            'address'  => 'nullable|string', // Optional as requested
            'password' => 'required|string|min:8|confirmed', // Requires 'password_confirmation'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Create User
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'address'  => $request->address,
            'password' => Hash::make($request->password),
        ]);

        // 3. Issue Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 201);
    }

    /**
     * Handle Social Signup/Login (Google & Facebook)
     */
    public function socialSignup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider'     => 'required|string|in:google,facebook',
            'access_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $provider = $request->provider;
        $token = $request->access_token;

        try {
            // Get user info from Socialite using the token from frontend
            $socialUser = Socialite::driver($provider)->userFromToken($token);

            // Check if user exists, or create a new one
            $user = User::updateOrCreate(
                ['email' => $socialUser->getEmail()],
                [
                    'name'          => $socialUser->getName(),
                    'provider_id'   => $socialUser->getId(),
                    'provider_name' => $provider,
                    // Note: Phone and Address aren't usually provided by Google/FB
                    // and would need to be updated by the user later.
                ]
            );

            $apiToken = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'user' => $user,
                    'access_token' => $apiToken,
                    'token_type' => 'Bearer',
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authentication failed: ' . $e->getMessage()
            ], 401);
        }
    }
}
