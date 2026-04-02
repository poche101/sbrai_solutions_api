<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            // Product categories
            ['name' => 'Sharp Sand', 'type' => 'product', 'description' => 'Construction sharp sand'],
            ['name' => 'Granite', 'type' => 'product', 'description' => 'Granite stones for construction'],
            ['name' => 'Blocks', 'type' => 'product', 'description' => 'Building blocks'],
            ['name' => 'Cement', 'type' => 'product', 'description' => 'Cement bags'],
            ['name' => 'Iron Rods', 'type' => 'product', 'description' => 'Reinforcement iron rods'],
            ['name' => 'Paints', 'type' => 'product', 'description' => 'Paint products'],
            ['name' => 'Furniture', 'type' => 'product', 'description' => 'Furniture items'],
            ['name' => 'Scaffolding', 'type' => 'product', 'description' => 'Scaffolding materials'],

            // Service categories
            ['name' => 'Logistics', 'type' => 'service', 'description' => 'Logistics and transportation services'],
            ['name' => 'Borehole', 'type' => 'service', 'description' => 'Borehole drilling services'],
            ['name' => 'Cleaning', 'type' => 'service', 'description' => 'Cleaning services'],
            ['name' => 'Fumigation', 'type' => 'service', 'description' => 'Fumigation and pest control services'],

            // Property categories
            ['name' => 'Apartment', 'type' => 'property', 'description' => 'Apartment units'],
            ['name' => 'House', 'type' => 'property', 'description' => 'Residential houses'],
            ['name' => 'Commercial', 'type' => 'property', 'description' => 'Commercial properties'],
            ['name' => 'Land', 'type' => 'property', 'description' => 'Land plots'],
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'type' => $category['type'],
                'description' => $category['description'],
                'is_active' => true,
            ]);
        }
    }
}
