<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nin_verification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('nin')->index();
            $table->string('status');
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->string('verification_status')->default('pending');
            $table->string('tracking_id')->nullable();
            $table->string('central_id')->nullable();
            $table->string('firstname')->nullable();
            $table->string('middlename')->nullable();
            $table->string('surname')->nullable();
            $table->string('photo')->nullable();
            $table->string('signature')->nullable();
            $table->string('birthdate')->nullable();
            $table->string('gender')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('profession')->nullable();
            $table->text('residence_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nin_verification_logs');
    }
};