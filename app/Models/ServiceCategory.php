<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    // Laravel looks for "service_categories" table by default now
    protected $fillable = ['name', 'slug', 'description'];
}
