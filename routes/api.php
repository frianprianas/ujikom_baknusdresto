<?php

use App\Http\Controllers\Api\MobileApiController;
use App\Http\Controllers\Api\MobileAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'Baknus Dresto Mobile API',
        'timestamp' => now()->toIso8601String()
    ]);
});

Route::prefix('mobile')->group(function () {
    // 1. Auth Pelanggan & Registrasi OTP
    Route::prefix('auth')->group(function () {
        Route::post('/register-otp', [MobileAuthController::class, 'requestRegisterOtp']);
        Route::post('/verify-otp-password', [MobileAuthController::class, 'verifyOtpAndSetPassword']);
        Route::post('/login', [MobileAuthController::class, 'login']);
        Route::post('/update-address', [MobileAuthController::class, 'updateAddress']);
    });

    // 2. Katalog & Meja
    Route::get('/categories', [MobileApiController::class, 'categories']);
    Route::get('/products', [MobileApiController::class, 'products']);
    Route::get('/tables', [MobileApiController::class, 'tables']);

    // 3. Pemesanan & Pembayaran Mandiri (Self Order & Delivery)
    Route::post('/orders', [MobileApiController::class, 'storeOrder']);
    Route::post('/orders/{id}/pay', [MobileApiController::class, 'payOrder']);
    Route::get('/orders/{id}', [MobileApiController::class, 'showOrder']);

    // 4. Simulasi Pengantaran (Delivery Tracking)
    Route::post('/orders/{id}/simulate-delivery', [MobileApiController::class, 'advanceDeliverySimulation']);
});
