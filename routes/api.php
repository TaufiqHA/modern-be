<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // OTP Methods
    Route::post('/otp/send', [AuthController::class, 'sendOtp']);

    // Social Auth
    Route::get('/google/redirect', [AuthController::class, 'redirectToGoogle']);
    Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user/me', [UserController::class, 'me']);

    // Address Management
    Route::get('/user/addresses', [AddressController::class, 'index']);
    Route::post('/user/addresses', [AddressController::class, 'store']);
});
