<?php

namespace App\Http\Controllers\Api\Buyers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FcmService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    protected $fcmService;

public function __construct(FcmService $fcmService = null)
{
    $this->fcmService = $fcmService;
}

    /**
     * Update user notification preferences and FCM token
     */
  public function updateSettings(Request $request)
{
    try {
        // 1. Check if the user is even logged in
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated. The Bearer token is missing or invalid.'
            ], 401);
        }

        // 2. Explicit Validation
        $validated = $request->validate([
            'fcm_token' => 'required|string',
            'preferences' => 'required|array',
        ]);

        // 3. Database Update
        $user->update([
            'fcm_token' => $validated['fcm_token'],
            'notification_preferences' => $validated['preferences'],
        ]);

        // 4. Safe Firebase Call
        if ($this->fcmService) {
            try {
                $this->fcmService->sendPush(
                    $user->fcm_token,
                    "Settings Updated! ✅",
                    "You will now receive alerts for Sbrai Hub updates."
                );
            } catch (\Exception $fcmError) {
                \Log::warning("FCM Push failed: " . $fcmError->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notification preferences updated successfully',
            'data' => [
                'fcm_token' => $user->fcm_token,
                'preferences' => $user->notification_preferences
            ]
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        // THIS PART IS KEY: It will tell you the EXACT PHP error in Postman
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
}
}
