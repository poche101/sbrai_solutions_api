<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Notifications\SendOtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerificationController extends Controller
{
    /**
     * EMAIL VERIFICATION
     */
    public function sendEmailOtp(Request $request)
    {
        $user = $request->user();
        $otp = rand(100000, 999999);

        $user->update([
            'email_otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);
        $user->notify(new SendOtpNotification($otp));

        return response()->json(['message' => 'Email verification code sent.']);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate(['otp' => 'required|string']);
        $user = $request->user();

        if ($user->email_otp === $request->otp) {
            $user->update([
                'email_verified_at' => now(),
                'email_otp' => null,
            ]);

            return response()->json(['message' => 'Email verified successfully.']);
        }

        return response()->json(['error' => 'Invalid or expired OTP.'], 422);

    }

    /**
     * PHONE VERIFICATION
     */
    public function sendPhoneOtp(Request $request)
    {
        $user = $request->user();

        if (! $user->phone) {
            return response()->json(['error' => 'No phone number found in profile.'], 400);
        }

        $otp = rand(100000, 999999);
        $user->update(['phone_otp' => $otp]);

        // Integrate SMS Provider here (Twilio, Termii, etc.)
        // Example: Log::info("Sending SMS to {$user->phone}: Your code is {$otp}");

        return response()->json(['message' => 'Phone verification code sent via SMS.']);
    }

    public function verifyPhone(Request $request)
    {
        $request->validate(['otp' => 'required|string']);
        $user = $request->user();

        if ($user->phone_otp === $request->otp) {
            $user->update([
                'phone_verified_at' => now(),
                'phone_otp' => null,
            ]);

            return response()->json(['message' => 'Phone verified successfully.']);
        }

        return response()->json(['error' => 'Invalid or expired OTP.'], 422);
    }
}
