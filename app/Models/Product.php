<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'vendor_id', 'title', 'slug',
        'description', 'price', 'price_unit', 'location', 'photos'
    ];

    protected $casts = [
        'photos' => 'array', // Automatically converts JSON to PHP Array
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($product) => $product->slug = Str::slug($product->title) . '-' . Str::random(5));
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }
}
