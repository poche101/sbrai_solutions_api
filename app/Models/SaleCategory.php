<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleCategory extends Model
{
    protected $fillable = ['name', 'slug'];

    public function properties() {
        return $this->hasMany(Property::class, 'sale_category_id');
    }
}
