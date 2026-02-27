<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'email_otp',        // Added for verification
        'phone_otp',        // Added for verification
        'phone_verified_at' // Added for verification
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
            'phone_verified_at' => 'datetime', // Added cast
            'password' => 'hashed',
            'otp_expires_at'    => 'datetime',
        ];
    }

    /**
     * Helper to check if user has completed both verifications.
     */
    public function isFullyVerified(): bool
    {
        return !is_null($this->email_verified_at) && !is_null($this->phone_verified_at);
    }
}
