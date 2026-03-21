<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentPropertyImage extends Model
{
    protected $fillable = ['rent_property_id', 'image_path'];

    /**
     * Automatically prepend the full URL when retrieving the image path.
     */
    public function getImagePathAttribute($value)
    {
        return asset('storage/' . $value);
    }
}
