<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('rent_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rent_category_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('bedroom')->default(0);
            $table->integer('bathroom')->default(0);
            $table->string('furnishing')->nullable();
            $table->decimal('sq_ft', 10, 2)->nullable();
            $table->decimal('price', 15, 2);
            $table->string('price_unit')->default('per month');
            $table->string('location');
            $table->timestamps();
        });

        Schema::create('rent_property_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rent_property_id')->constrained()->onDelete('cascade');
            $table->string('image_path');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('rent_property_images');
        Schema::dropIfExists('rent_properties');
    }
};
