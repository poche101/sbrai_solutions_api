<?php

namespace App\Http\Controllers\api\buyers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use App\Notifications\SendOtpNotification;
use Exception;
use Twilio\Rest\Client as TwilioClient;

class AuthController extends Controller
{
    /**
     * Handle Standard User Registration
     */
    public function register(Request $request)
    {
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

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'address'  => $request->address,
            'password' => Hash::make($request->password),
        ]);

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
     * Handle Standard User Login (Email & Password)
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials provided.'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
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
            if (app()->environment('local')) {
                config(["services.$provider.guzzle.verify" => false]);
            }

            $socialUser = Socialite::driver($provider)->stateless()->userFromToken($token);

            if (!$socialUser->getEmail()) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Could not retrieve email from $provider."
                ], 422);
            }

            $user = User::updateOrCreate(
                ['email' => $socialUser->getEmail()],
                [
                    'name'              => $socialUser->getName(),
                    'provider_id'       => $socialUser->getId(),
                    'provider_name'     => $provider,
                    'email_verified_at' => now(), // Social users are pre-verified
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
     * EMAIL VERIFICATION METHODS
     */
    public function sendEmailOtp(Request $request)
    {
        $user = $request->user();
        $otp = rand(100000, 999999);

        // FIXED: Combined the OTP and Expiry into the update array correctly
        $user->update([
            'email_otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10)
        ]);

        $user->notify(new SendOtpNotification($otp));

        return response()->json([
            'status' => 'success',
            'message' => 'Verification code sent to your email.'
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate(['otp' => 'required|string']);
        $user = $request->user();

        // Optional: Check if OTP is expired if you use the otp_expires_at column
        if ($user->email_otp === $request->otp) {
            $user->update([
                'email_verified_at' => now(),
                'email_otp' => null,
                'otp_expires_at' => null
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Email verified successfully.'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Invalid or expired OTP.'
        ], 422);
    }

    /**
     * PHONE VERIFICATION METHODS
     */
    public function sendPhoneOtp(Request $request)
    {
        $user = $request->user();
        if (!$user->phone) {
            return response()->json(['status' => 'error', 'message' => 'Update your phone number first.'], 400);
        }

        $otp = rand(100000, 999999);
        $user->update(['phone_otp' => $otp]);

        try {
            $sid = env('TWILIO_SID');
            $token = env('TWILIO_AUTH_TOKEN');
            $twilioNumber = env('TWILIO_NUMBER');

            $client = new TwilioClient($sid, $token);
            $client->messages->create(
                $user->phone,
                [
                    'from' => $twilioNumber,
                    'body' => "Your verification code is: $otp"
                ]
            );

            return response()->json(['status' => 'success', 'message' => 'Code sent via SMS.']);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'SMS failed: ' . $e->getMessage()], 500);
        }
    }

    public function verifyPhone(Request $request)
    {
        $request->validate(['otp' => 'required|string']);
        $user = $request->user();

        if ($user->phone_otp === $request->otp) {
            $user->update([
                'phone_verified_at' => now(),
                'phone_otp' => null
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Phone verified successfully.'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Invalid or expired OTP.'
        ], 422);
    }

    /**
     * Update User Profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

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

    /**
     * Handle User Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }
}
