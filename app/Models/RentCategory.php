<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentCategory extends Model
{
    protected $fillable = ['name', 'slug'];

    public function properties() {
        return $this->hasMany(Property::class, 'rent_category_id');
    }
}
