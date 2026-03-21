<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Buyers\AuthController;
use App\Http\Controllers\Api\Vendor\VendorAuthController;
use App\Http\Controllers\Api\Buyers\FavoriteController;
use App\Http\Controllers\Api\Vendor\NINVerificationController;
use App\Http\Controllers\Api\Vendor\CategoryController;
use App\Http\Controllers\Api\Vendor\ProductController;
use App\Http\Controllers\Api\Vendor\ServiceCategoryController;
use App\Http\Controllers\Api\Vendor\ServiceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- 1. BUYER ROUTES (v1/buyers) ---
Route::prefix('v1/buyers')->group(function () {

    // Public Auth Routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/social-auth', [AuthController::class, 'socialSignup']);

    // Protected Routes (Require Sanctum Token)
    Route::middleware('auth:sanctum')->group(function () {

        // Verification & OTP (Throttled to 3 requests per minute)
        Route::middleware('throttle:3,1')->group(function () {
            Route::post('/verify/email/send', [AuthController::class, 'sendEmailOtp']);
            Route::post('/verify/phone/send', [AuthController::class, 'sendPhoneOtp']);
        });

        // Favourites - Cleaned up redundant middleware
        Route::get('/favorites', [FavoriteController::class, 'index']);
        Route::post('/favorites/toggle', [FavoriteController::class, 'toggle']);

        // OTP Confirmation
        Route::post('/verify/email/confirm', [AuthController::class, 'verifyEmail']);
        Route::post('/verify/phone/confirm', [AuthController::class, 'verifyPhone']);

        // Authenticated Session Management
        Route::post('/logout', [AuthController::class, 'logout']);

        // Routes requiring Email/Phone Verification
        Route::middleware('verified_api')->group(function () {
            Route::get('/user', function (Request $request) {
                return $request->user();
            });
            Route::post('/update-profile', [AuthController::class, 'updateProfile']);
        });
    });
});

// --- 2. VENDOR ROUTES (v1/vendors) ---
Route::prefix('v1/vendor')->group(function () {

    // Public Vendor Auth
    Route::post('/register', [VendorAuthController::class, 'register']);
    Route::post('/login', [VendorAuthController::class, 'login']);


    Route::prefix('nin')->group(function () {
        Route::post('/verify', [NINVerificationController::class, 'verify']);
        Route::get('/{nin}/status', [NINVerificationController::class, 'checkStatus']);
        Route::get('/{nin}/details', [NINVerificationController::class, 'getVerificationDetails']);
        Route::get('/{nin}/history', [NINVerificationController::class, 'getHistory']);
    });

    // Category API
    Route::apiResource('categories', CategoryController::class);
    // Products API
    Route::apiResource('products', ProductController::class);
    //Service Category
    Route::apiResource('service-categories', ServiceCategoryController::class);
    Route::apiResource('services', ServiceController::class);
});

    // Protected Vendor Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [VendorAuthController::class, 'logout']);

        // Verified Vendor Access
        Route::middleware('verified_api')->group(function () {
            Route::get('/profile', [VendorAuthController::class, 'profile']);
            Route::post('/update-profile', [VendorAuthController::class, 'updateProfile']);
        });
    });

// --- 3. GLOBAL USER ROUTE ---
// Shared route for any authenticated user to check identity
Route::middleware(['auth:sanctum', 'verified_api'])->get('/user', function (Request $request) {
    return $request->user();
});
