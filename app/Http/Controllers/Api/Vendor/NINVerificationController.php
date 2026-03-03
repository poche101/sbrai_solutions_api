<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Vendor\NINVerificationRequest;
use App\Services\MonoNINService;
use App\Models\NINVerificationLog;
use Illuminate\Http\JsonResponse;

class NINVerificationController extends Controller
{
    protected $monoNINService;

    public function __construct(MonoNINService $monoNINService)
    {
        $this->monoNINService = $monoNINService;
    }

    /**
     * Verify NIN
     */
    public function verify(NINVerificationRequest $request): JsonResponse
    {
        $result = $this->monoNINService->verifyNIN($request->nin);

        if ($result['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'data' => [
                    'verified' => true,
                    'nin_data' => $result['data'],
                    'tracking_id' => $result['tracking_id'] ?? null,
                    'from_cache' => $result['from_cache'] ?? false
                ]
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $result['message'],
            'error' => $result['error'] ?? null
        ], 400);
    }

    /**
     * Check NIN verification status
     */
    public function checkStatus(string $nin): JsonResponse
    {
        $latestVerification = NINVerificationLog::where('nin', $nin)
            ->latest()
            ->first();

        if (!$latestVerification) {
            return response()->json([
                'status' => 'error',
                'message' => 'No verification found for this NIN'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'nin' => $nin,
                'verification_status' => $latestVerification->verification_status,
                'verified_at' => $latestVerification->created_at,
                'is_verified' => $latestVerification->verification_status === 'verified'
            ]
        ]);
    }

    /**
     * Get verification details
     */
    public function getVerificationDetails(string $nin): JsonResponse
    {
        $verification = NINVerificationLog::where('nin', $nin)
            ->where('verification_status', 'verified')
            ->latest()
            ->first();

        if (!$verification) {
            return response()->json([
                'status' => 'error',
                'message' => 'No verified NIN found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'nin' => $verification->nin,
                'firstname' => $verification->firstname,
                'middlename' => $verification->middlename,
                'surname' => $verification->surname,
                'birthdate' => $verification->birthdate,
                'gender' => $verification->gender,
                'phone_number' => $verification->phone_number,
                'email' => $verification->email,
                'profession' => $verification->profession,
                'residence_address' => $verification->residence_address,
                'tracking_id' => $verification->tracking_id,
                'verified_at' => $verification->created_at
            ]
        ]);
    }

    /**
     * Get verification history
     */
    public function getHistory(string $nin): JsonResponse
    {
        $history = $this->monoNINService->getVerificationHistory($nin);

        return response()->json([
            'status' => 'success',
            'data' => $history
        ]);
    }
}