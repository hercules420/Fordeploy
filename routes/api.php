<?php

use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileMarketplaceController;
use App\Http\Controllers\Api\MobileProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function () {
    Route::post('/auth/login', [MobileAuthController::class, 'login']);
    Route::get('/products', [MobileProductController::class, 'index']);

    Route::middleware('mobile.auth')->group(function () {
        Route::post('/auth/logout', [MobileAuthController::class, 'logout']);

        Route::get('/profile', [MobileMarketplaceController::class, 'profile']);
        Route::patch('/profile', [MobileMarketplaceController::class, 'updateProfile']);

        Route::get('/orders', [MobileMarketplaceController::class, 'orders']);
        Route::post('/orders', [MobileMarketplaceController::class, 'placeOrder']);
        Route::post('/orders/{order}/cancel', [MobileMarketplaceController::class, 'cancelOrder']);
        Route::post('/orders/{order}/retry-payment', [MobileMarketplaceController::class, 'retryPayment']);

        Route::get('/notifications', [MobileMarketplaceController::class, 'notifications']);
        Route::post('/complaints', [MobileMarketplaceController::class, 'submitComplaint']);

        Route::get('/ratings', [MobileMarketplaceController::class, 'ratings']);
        Route::post('/ratings/{delivery}', [MobileMarketplaceController::class, 'submitRating']);
    });
});
