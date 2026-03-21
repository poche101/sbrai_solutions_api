<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SaleProperty extends Model {
    protected $fillable = [
        'sale_category_id', 'title', 'slug', 'description', 'bedroom',
        'bathroom', 'furnishing', 'sq_ft', 'price', 'price_unit', 'location'
    ];

    public function images() {
        return $this->hasMany(SalePropertyImage::class);
    }

    protected static function booted() {
        static::deleting(function ($property) {
            foreach ($property->images as $image) {
                Storage::disk('public')->delete($image->image_path);
            }
        });
    }
}
