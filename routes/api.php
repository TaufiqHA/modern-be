<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\JastipController as AdminJastipController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\JastipController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/categories', [CatalogController::class, 'categories']);
Route::get('/collections', [CatalogController::class, 'collections']);
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');

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
    Route::patch('/user/addresses/{id}', [AddressController::class, 'update']);
    Route::delete('/user/addresses/{id}', [AddressController::class, 'destroy']);

    // Profile Management
    Route::patch('/user/me', [UserController::class, 'update']);
    Route::post('/user/change-password', [AuthController::class, 'changePassword']);

    // Order Management
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/orders/{id}/payment-proof', [OrderController::class, 'uploadPaymentProof']);

    // Shipping Management
    Route::post('/shipping/cost', [ShippingController::class, 'calculate']);

    // Jastip Request
    Route::get('/jastip/requests', [JastipController::class, 'index']);
    Route::post('/jastip/request', [JastipController::class, 'store']);
    Route::post('/jastip/{id}/convert', [JastipController::class, 'convertToOrder']);
});

Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats']);
    Route::patch('/jastip/{id}/quote', [AdminJastipController::class, 'updateQuote']);
    Route::patch('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);

    // Product Management
    Route::patch('/products/{id}', [AdminProductController::class, 'update']);
    Route::delete('/products/{id}', [AdminProductController::class, 'destroy']);
    Route::get('/products/{id}/stock-logs', [AdminProductController::class, 'stockLogs']);
});
