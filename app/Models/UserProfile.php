<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model {
    // Add 'profile_photo' to this list
    protected $fillable = [
        'user_id',
        'full_name',
        'profile_photo',
        'phone',
        'address'
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
