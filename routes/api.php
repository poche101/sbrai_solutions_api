<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Buyers\AuthController;


//BUYERS ROUTE
Route::prefix('buyers')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/social-auth', [AuthController::class, 'socialSignup']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/buyers/update-profile', [AuthController::class, 'updateProfile']);
});

// Example Protected Route
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


