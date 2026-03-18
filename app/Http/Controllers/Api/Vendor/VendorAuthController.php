<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Vendor\RegisterRequest;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class VendorAuthController extends Controller
{
    /**
     * Register a new vendor
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $vendor = Vendor::create([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'business_name' => $request->business_name,
                'nin' => $request->nin,
                'business_address' => $request->business_address,
                'password' => Hash::make($request->password),
            ]);

            // Create token for the vendor
            $token = $vendor->createToken('vendor_auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Vendor account created successfully',
                'data' => [
                    'vendor' => $vendor,
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login vendor
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Find the vendor by email
        $vendor = Vendor::where('email', $request->email)->first();

        // Check if vendor exists and password is correct
        if (!$vendor || !Hash::check($request->password, $vendor->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid login credentials'
            ], 401);
        }

        // Create new token
        $token = $vendor->createToken('vendor_auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'vendor' => $vendor,
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    /**
     * Logout vendor
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get vendor profile
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()
        ]);
    }

    /**
     * Update vendor profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $vendor = $request->user();

        $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:20',
            'business_address' => 'sometimes|string',
        ]);

        try {
            $vendor->update($request->only([
                'full_name',
                'phone_number',
                'business_address'
            ]));

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => $vendor
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profile update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
