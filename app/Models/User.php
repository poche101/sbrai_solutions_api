<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'password',
        'provider_id',
        'provider_name',
        'email_otp',
        'phone_otp',
        'phone_verified_at',
        'otp_expires_at',
        'fcm_token',                // Added for Notifications
        'notification_preferences', // Added for Notifications
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'provider_id',
        'email_otp',
        'phone_otp',
        'otp_expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password'          => 'hashed',
            'otp_expires_at'    => 'datetime',
            'notification_preferences' => 'array', // Added array cast
        ];
    }

    /**
     * Get the profile associated with the user.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class, 'user_id');
    }

    /**
     * Polymorphic Favorites Relationship.
     * This links the user to all their favorites (Products, Services, Properties).
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Helper to check if user has completed both verifications.
     */
    public function isFullyVerified(): bool
    {
        return !is_null($this->email_verified_at) && !is_null($this->phone_verified_at);
    }

    public function favoriteProducts()
{
    return $this->morphedByMany(Product::class, 'favoritable', 'favorites');
}
}
