<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations; // <--- Add this

class Product extends Model
{
    use HasTranslations; // <--- Add this

    protected $fillable = [
        'category_id', 'vendor_id', 'title', 'slug',
        'description', 'price', 'price_unit', 'location', 'photos'
    ];

    // Define which columns hold JSON translations
    public $translatable = ['title', 'description']; // <--- Add this

    protected $casts = [
        'photos' => 'array',
        'title' => 'array',       // Recommended to ensure JSON handling
        'description' => 'array', // Recommended to ensure JSON handling
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            // Since title is now an array (translations),
            // we slug the default locale's version
            $title = is_array($product->title) ? ($product->title[app()->getLocale()] ?? reset($product->title)) : $product->title;
            $product->slug = Str::slug($title) . '-' . Str::random(5);
        });
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function vendor() {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }
}
