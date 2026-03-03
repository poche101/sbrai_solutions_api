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
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_otp')->after('email')->nullable();
            $table->string('phone_otp')->after('phone')->nullable();
            $table->timestamp('phone_verified_at')->after('email_verified_at')->nullable();
            $table->timestamp('otp_expires_at')->after('phone_otp')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the columns in reverse order if the migration is rolled back
            $table->dropColumn(['email_otp', 'phone_otp', 'phone_verified_at']);
        });
    }
};
