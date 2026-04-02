<?php

namespace App\Http\Controllers\api\buyers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;
use App\Notifications\SendOtpNotification;
use Exception;
use Twilio\Rest\Client as TwilioClient;
use Illuminate\Database\QueryException;

class AuthController extends Controller
{
    /**
     * Handle Standard User Registration
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name'     => 'required|string|max:255',
                'email'    => 'required|string|email|max:255|unique:users',
                'phone'    => 'required|string|max:20',
                'address'  => 'nullable|string',
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(8)
                        ->letters()
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                        ->uncompromised(), // Security: Checks if password was leaked in data breaches
                ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
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

        } catch (QueryException $e) {
            return response()->json(['status' => 'error', 'message' => 'Database error: Could not complete registration.'], 500);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An unexpected error occurred during registration.'], 500);
        }
    }

    /**
     * Handle Standard User Login (Email & Password)
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'    => 'required|string|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            // Specific check for credentials
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Incorrect email or password.'
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

        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Login service temporarily unavailable.'], 500);
        }
    }

    /**
     * Handle Social Signup/Login
     */
    public function socialSignup(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'provider'     => 'required|string|in:google,facebook',
                'access_token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
            }

            $provider = $request->provider;
            $token = $request->access_token;

            if (app()->environment('local')) {
                config(["services.$provider.guzzle.verify" => false]);
            }

            $socialUser = Socialite::driver($provider)->stateless()->userFromToken($token);

            if (!$socialUser->getEmail()) {
                return response()->json(['status' => 'error', 'message' => "Email not provided by $provider."], 422);
            }

            $user = User::updateOrCreate(
                ['email' => $socialUser->getEmail()],
                [
                    'name'              => $socialUser->getName(),
                    'provider_id'       => $socialUser->getId(),
                    'provider_name'     => $provider,
                    'email_verified_at' => now(),
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
            return response()->json(['status' => 'error', 'message' => 'Social authentication failed: ' . $e->getMessage()], 401);
        }
    }

    /**
     * EMAIL VERIFICATION METHODS
     */
    public function sendEmailOtp(Request $request)
    {
        try {
            $user = $request->user();
            $otp = rand(100000, 999999);

            $user->update([
                'email_otp' => $otp,
                'otp_expires_at' => now()->addMinutes(15)
            ]);

            $user->notify(new SendOtpNotification($otp));

            return response()->json(['status' => 'success', 'message' => 'Verification code sent to your email.']);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to send OTP. Please try again.'], 500);
        }
    }

    public function verifyEmail(Request $request)
    {
        try {
            $request->validate(['otp' => 'required|string']);
            $user = $request->user();

            if (!$user->email_otp || !$user->otp_expires_at || $user->otp_expires_at < now()) {
                return response()->json(['status' => 'error', 'message' => 'OTP has expired or does not exist.'], 422);
            }

            if ($user->email_otp === $request->otp) {
                $user->update([
                    'email_verified_at' => now(),
                    'email_otp' => null,
                    'otp_expires_at' => null
                ]);
                return response()->json(['status' => 'success', 'message' => 'Email verified successfully.']);
            }

            return response()->json(['status' => 'error', 'message' => 'Invalid OTP code.'], 422);

        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Verification process failed.'], 500);
        }
    }

    /**
     * PHONE VERIFICATION METHODS
     */
    public function sendPhoneOtp(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user->phone) {
                return response()->json(['status' => 'error', 'message' => 'Please update your phone number in your profile first.'], 400);
            }

            $otp = rand(100000, 999999);
            $user->update(['phone_otp' => $otp]);

            $sid = env('TWILIO_SID');
            $token = env('TWILIO_AUTH_TOKEN');
            $twilioNumber = env('TWILIO_NUMBER');

            if (!$sid || !$token || !$twilioNumber) {
                throw new Exception("SMS service configuration is missing.");
            }

            $client = new TwilioClient($sid, $token);
            $client->messages->create(
                $user->phone,
                ['from' => $twilioNumber, 'body' => "Your verification code is: $otp"]
            );

            return response()->json(['status' => 'success', 'message' => 'Code sent via SMS.']);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'SMS Gateway error: ' . $e->getMessage()], 500);
        }
    }

    public function verifyPhone(Request $request)
    {
        try {
            $request->validate(['otp' => 'required|string']);
            $user = $request->user();

            if ($user->phone_otp === $request->otp) {
                $user->update([
                    'phone_verified_at' => now(),
                    'phone_otp' => null
                ]);
                return response()->json(['status' => 'success', 'message' => 'Phone verified successfully.']);
            }

            return response()->json(['status' => 'error', 'message' => 'Invalid or expired phone OTP.'], 422);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Phone verification failed.'], 500);
        }
    }

    /**
     * Update User Profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'phone'   => 'required|string|max:20',
                'address' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
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
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Profile update failed.'], 500);
        }
    }

    /**
     * Handle User Logout
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Logged out successfully'
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Logout failed.'], 500);
        }
    }
}
