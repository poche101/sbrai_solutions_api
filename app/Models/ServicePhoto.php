<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePhoto extends Model
{
    // 1. Allow these fields to be filled by the Controller
    protected $fillable = ['service_id', 'image_path'];

    /**
     * Relationship: A photo belongs to one service.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Accessor: Automatically turns 'services/photos/abc.jpg'
     * into 'http://localhost:8000/storage/services/photos/abc.jpg'
     */
    public function getImagePathAttribute($value)
    {
        if (!$value) return null;

        // This ensures the URL is correct for your API response
        return asset('storage/' . $value);
    }
}
