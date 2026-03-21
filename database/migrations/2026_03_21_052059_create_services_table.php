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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_category_id')->constrained()->onDelete('cascade');

            // Updated Fields
            $table->string('title'); // Changed from 'name'
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Price and Unit (e.g., 50000 per "day", "project", or "meter")
            $table->decimal('price', 15, 2)->nullable();
            $table->string('price_unit')->nullable()->comment('e.g., per day, per borehole');

            // Geographic location
            $table->string('location')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
