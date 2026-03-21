<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('rent_property_images', function (Blueprint $table) {
        $table->id();
        // Link to the property
        $table->foreignId('rent_property_id')->constrained('rent_properties')->onDelete('cascade');
        $table->string('image_path');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rent_property_images');
    }
};
