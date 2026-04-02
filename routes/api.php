<?php

use App\Http\Controllers\Api\Buyers\AuthController;
use App\Http\Controllers\Api\Buyers\FavoriteController;
use App\Http\Controllers\Api\Buyers\NotificationController;
use App\Http\Controllers\Api\Buyers\ProfileController; // Added this
use App\Http\Controllers\Api\Vendor\CategoryController;
use App\Http\Controllers\Api\Vendor\NINVerificationController;
use App\Http\Controllers\Api\Vendor\ProductController;
use App\Http\Controllers\Api\Vendor\PropertyCategoryController;
use App\Http\Controllers\Api\Vendor\RentPropertyController;
use App\Http\Controllers\Api\Vendor\SalePropertyController;
use App\Http\Controllers\Api\Vendor\ServiceCategoryController;
use App\Http\Controllers\Api\Vendor\ServiceController;
use App\Http\Controllers\Api\Vendor\VendorAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

    // This creates the URL: /api/v1/buyers/social-signup
    Route::post('social-signup', [AuthController::class, 'socialSignup']);

    // Protected Routes (Require Sanctum Token)
    Route::middleware('auth:sanctum')->group(function () {

        // --- Notification Settings (Buyer) ---
        Route::post('/notifications/settings', [NotificationController::class, 'updateSettings']);

        // Verification & OTP (Throttled to 3 requests per minute)
        Route::middleware('throttle:3,1')->group(function () {
            Route::post('/verify/email/send', [AuthController::class, 'sendEmailOtp']);
            Route::post('/verify/phone/send', [AuthController::class, 'sendPhoneOtp']);
        });

        // Favourites
        Route::get('/favorites', [FavoriteController::class, 'index']);
        Route::post('/favorites/toggle', [FavoriteController::class, 'toggle']);

        // Profile
        Route::apiResource('profile', ProfileController::class);

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
    // Service Category
    Route::apiResource('service-categories', ServiceCategoryController::class);
    // Services API
    Route::apiResource('services', ServiceController::class);

    // Property Categories
    Route::apiResource('categories/property', PropertyCategoryController::class);
    // Vendor Rent Property Routes
    Route::apiResource('rent-properties', RentPropertyController::class);
    // Vendor Property for sale
    Route::apiResource('sale-properties', SalePropertyController::class);

    // Protected Vendor Routes
    Route::middleware('auth:sanctum')->group(function () {

        // --- Notification Settings (Vendor) ---
        Route::post('/notifications/settings', [NotificationController::class, 'updateSettings']);

        Route::post('/logout', [VendorAuthController::class, 'logout']);

        // Verified Vendor Access
        Route::middleware('verified_api')->group(function () {
            Route::get('/profile', [VendorAuthController::class, 'profile']);
            Route::post('/update-profile', [VendorAuthController::class, 'updateProfile']);
        });
    });
});

// --- 3. GLOBAL USER ROUTE ---
Route::middleware(['auth:sanctum', 'verified_api'])->get('/user', function (Request $request) {
    return $request->user();
});
