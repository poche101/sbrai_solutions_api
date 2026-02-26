<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Buyers\AuthController;
use App\Http\Controllers\Api\Buyer\AuthController as BuyerAuth;
use App\Http\Controllers\Api\Vendor\AuthController as VendorAuth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- BUYER ROUTES ---
Route::prefix('v1/buyers')->group(function () {

    // 1. Public Auth Routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/social-auth', [AuthController::class, 'socialSignup']);

    // 2. Protected Routes (Require Sanctum Token)
    Route::middleware('auth:sanctum')->group(function () {

        // Profile Management
        Route::post('/update-profile', [AuthController::class, 'updateProfile']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // User Data
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
    });
});


// VENDORS ROUTES
Route::prefix('v1/vendors')->group(function () {
    Route::post('/register', [VendorAuthController::class, 'register']);
    Route::post('/login', [VendorAuthController::class, 'login']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Buyer protected routes
    Route::post('/buyers/update-profile', [AuthController::class, 'updateProfile']);

    // Vendor protected routes
    Route::prefix('vendors')->group(function () {
        Route::post('/logout', [VendorAuthController::class, 'logout']);
        Route::get('/profile', [VendorAuthController::class, 'profile']);
        Route::post('/update-profile', [VendorAuthController::class, 'updateProfile']);
    });
});

// Example Protected Route
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
