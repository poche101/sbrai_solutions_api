<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ServicePhoto extends Model
{
    /**
     * Fields that are mass-assignable.
     */
    protected $fillable = ['service_id', 'image_path'];

    /**
     * The attributes that should be visible in your JSON response for Flutter.
     * Adding 'image_url' here ensures it's always included in the API.
     */
    protected $appends = ['image_url'];

    /**
     * Relationship: A photo belongs to one service.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Accessor: Returns the raw path stored in the database.
     * This is kept clean so backend file deletion still works.
     */
    public function getImagePathAttribute($value)
    {
        return $value;
    }

    /**
     * Accessor: Generates a full, clickable URL for your Flutter frontend.
     * Usage in Flutter: product['image_url']
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image_path) {
            return null;
        }

        // Using Storage::url is safer than asset() if you move to S3/Cloudinary later.
        // It automatically handles the /storage/ prefix if your disk is set to 'public'.
        return asset('storage/' . $this->image_path);
    }
}
