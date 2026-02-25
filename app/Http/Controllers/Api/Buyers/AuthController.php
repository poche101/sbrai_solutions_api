<?php

namespace App\Http\Controllers\api\buyers;

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
            'address'  => 'nullable|string',
            'password' => 'required|string|min:8|confirmed',
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
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $provider = $request->provider;
        $token = $request->access_token;

        try {
            // Force SSL bypass for local development if needed
            if (app()->environment('local')) {
                config(["services.$provider.guzzle.verify" => false]);
            }

            // Get user info using stateless mode (required for APIs)
            $socialUser = Socialite::driver($provider)->stateless()->userFromToken($token);

            // Validation: Ensure we actually got an email back from the provider
            if (!$socialUser->getEmail()) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Could not retrieve email from $provider. Please ensure your account has a verified email."
                ], 422);
            }

            // Check if user exists, or create a new one
            $user = User::updateOrCreate(
                ['email' => $socialUser->getEmail()],
                [
                    'name'          => $socialUser->getName(),
                    'provider_id'   => $socialUser->getId(),
                    'provider_name' => $provider,
                    // Note: Phone and Address aren't provided by OAuth.
                    // If your DB requires phone, you might need to make it nullable
                    // in your migration or prompt the user to add it later.
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

    /**
 * Update User Profile (for adding phone/address after social login)
 */
public function updateProfile(Request $request)
{
    $user = $request->user(); // Gets the authenticated user via Sanctum

    $validator = Validator::make($request->all(), [
        'phone'   => 'required|string|max:20',
        'address' => 'required|string|max:500',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors()
        ], 422);
    }

    $user->update([
        'phone'   => $request->phone,
        'address' => $request->address,
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Profile updated successfully',
        'data' => $user
    ]);
}
}
