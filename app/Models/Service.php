<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Service extends Model
{
    protected $fillable = [
    'service_category_id',
    'title',
    'slug',
    'description',
    'price',
    'price_unit',
    'location'
];

    /**
     * Automatically load photos whenever you fetch a service.
     */
    protected $with = ['photos'];

    /**
     * Relationship: A service has many photos.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ServicePhoto::class);
    }

    /**
     * Relationship: A service belongs to a category.
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /**
     * Boot method to handle automatic deletion of physical image files
     * from the storage when a service is deleted.
     */
    protected static function booted()
    {
        static::deleting(function ($service) {
            foreach ($service->photos as $photo) {
                Storage::disk('public')->delete($photo->getRawOriginal('image_path'));
            }
        });
    }
}
