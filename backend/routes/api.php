<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn() => response()->json(['status' => 'ok']));

Route::get('/users/test', [UserController::class, 'getTestUser']);

Route::prefix('/users/{user}')->group(function () {
    Route::get('/', [UserController::class, 'show']);
    Route::get('/balance', [PaymentController::class, 'getBalance']);

    Route::prefix('/payments')->group(function () {
        Route::post('/', [PaymentController::class, 'initiate']);
        Route::post('/quick', [PaymentController::class, 'quickPay'])->middleware('throttle:30,1');
        Route::post('/{paymentId}/process', [PaymentController::class, 'process'])->middleware('throttle:30,1');
        Route::get('/{paymentId}', [PaymentController::class, 'show']);
        Route::post('/{paymentId}/refund', [PaymentController::class, 'refund'])->middleware('throttle:10,1');
        Route::get('/', [PaymentController::class, 'history']);
    });
});
