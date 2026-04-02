<?php

namespace App\Observers;

use App\Models\User;
use App\Services\FcmService;
use Illuminate\Database\Eloquent\Model;

class ProductObserver
{
    protected $fcm;

    public function __construct(FcmService $fcm)
    {
        $this->fcm = $fcm;
    }

    /**
     * Handle "created" events for Products, Services, and Properties.
     */
    public function created(Model $model): void
    {
        $className = class_basename($model);

        // Resolve the name properly based on which model it is
        $displayName = $model->name ?? $model->title ?? 'a new item';

        $title = "New $className Available! 🏗️";
        $body = "A new {$className} listed as '{$displayName}' was just posted.";

        $users = User::whereNotNull('fcm_token')
            ->where('notification_preferences->new_listings', true)
            ->get();

        foreach ($users as $user) {
            $this->fcm->sendPush(
                $user->fcm_token,
                $title,
                $body,
                [
                    'id' => (string)$model->id,
                    'type' => 'new_listing',
                    'category' => strtolower($className)
                ]
            );
        }
    }

    /**
     * Handle "updated" events (Specifically for Price Drops).
     */
    public function updated(Model $model): void
    {
        // Check if the model has a price attribute and if it decreased
        if (isset($model->price) && $model->isDirty('price') && $model->price < $model->getOriginal('price')) {

            $displayName = $model->name ?? $model->title ?? 'item';

            // Find users who favorited this specific item
            $users = $model->favoritedBy()
                ->whereNotNull('fcm_token')
                ->where('notification_preferences->price_drops', true)
                ->get();

            foreach ($users as $user) {
                $this->fcm->sendPush(
                    $user->fcm_token,
                    "Price Drop! 📉",
                    "Good news! The price for {$displayName} just dropped to ₦" . number_format($model->price),
                    [
                        'id' => (string)$model->id,
                        'type' => 'price_drop',
                        'category' => strtolower(class_basename($model))
                    ]
                );
            }
        }
    }

    public function deleted(Model $model): void {}
    public function restored(Model $model): void {}
    public function forceDeleted(Model $model): void {}
}
