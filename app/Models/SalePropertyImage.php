<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalePropertyImage extends Model {
    protected $fillable = ['sale_property_id', 'image_path'];

    public function getImagePathAttribute($value) {
        return asset('storage/' . $value);
    }
}
