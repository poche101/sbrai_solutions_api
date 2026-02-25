<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Buyers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- BUYER ROUTES ---
Route::prefix('buyers')->group(function () {

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
