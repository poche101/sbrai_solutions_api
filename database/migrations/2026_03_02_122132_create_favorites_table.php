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
    Schema::create('favorites', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');

        /**
         * This single line replaces 'product_id'.
         * It creates 'favoritable_id' (unsignedBigInteger)
         * and 'favoritable_type' (string).
         */
        $table->morphs('favoritable');

        $table->timestamps();

        // Optional: Prevent a user from favoriting the same item twice
        $table->unique(['user_id', 'favoritable_id', 'favoritable_type']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
