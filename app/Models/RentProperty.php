<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RentProperty extends Model {
    protected $fillable = [
        'rent_category_id', 'title', 'slug', 'description', 'bedroom',
        'bathroom', 'furnishing', 'sq_ft', 'price', 'price_unit', 'location'
    ];

    public function images() {
        return $this->hasMany(RentPropertyImage::class);
    }

    protected static function booted() {
        static::deleting(function ($property) {
            foreach ($property->images as $image) {
                Storage::disk('public')->delete($image->image_path);
            }
        });
    }
}
