<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class FcmService
{
    protected $messaging;

    // app/Services/FcmService.php

  public function __construct()
{
    try {
        // 1. Correct the path
        $serviceAccountPath = storage_path('app/firebase/celz5-firebase-adminsdk.json');

        // 2. Simple initialization
        if (file_exists($serviceAccountPath)) {
            // FIX: Removed the (Factory)::class syntax error
            $factory = (new Factory)->withServiceAccount($serviceAccountPath);
            $this->messaging = $factory->createMessaging();
        } else {
            \Log::error("Firebase JSON file missing at: " . $serviceAccountPath);
            $this->messaging = null;
        }
    } catch (\Exception $e) {
        \Log::error("FcmService Init Error: " . $e->getMessage());
        $this->messaging = null;
    }
}
   /**
 * Send a Push Notification
 * * @param string|null $token The user's FCM device token
 * @param string $title Notification title
 * @param string $body Notification message
 * @param array $data Extra data (e.g., ['order_id' => '45'])
 * @return bool
 */
public function sendPush($token, $title, $body, array $data = [])
{
    // 1. Critical Check: Ensure we have a token and the Firebase service initialized
    if (!$token || !$this->messaging) {
        \Log::warning("FCM Skip: Token missing or Messaging Service not initialized.");
        return false;
    }

    try {
        // 2. Create the Notification object
        $notification = FirebaseNotification::create($title, $body);

        // 3. Build the Cloud Message
        $message = CloudMessage::withTarget('token', $token)
            ->withNotification($notification)
            ->withData($data)
            ->withLowestBackoffStrategy(); // Optional: Helps with temporary network blips

        // 4. Send the message
        $this->messaging->send($message);

        return true;

    } catch (\Kreait\Firebase\Exception\Messaging\InvalidMessage $e) {
        // Specific catch for bad tokens (expired or wrong app)
        \Log::error("FCM Invalid Token: " . $e->getMessage());
        return false;

    } catch (\Exception $e) {
        // Generic catch for network or configuration issues
        \Log::error("FCM Send Error: " . $e->getMessage());
        return false;
    }
}
}
