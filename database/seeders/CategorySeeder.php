<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Sharp Sand',
            'Granite',
            'Blocks',
            'Cement',
            'Iron Rods',
            'Paints',
            'Furniture',
            'Scaffolding',
        ];

        foreach ($categories as $item) {
            Category::create(['name' => $item]);
        }
    }
}
