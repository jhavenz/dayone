<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductAccessController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product:slug}', [ProductController::class, 'show']);

Route::prefix('{product}')->middleware(['auth:sanctum', 'dayone.product'])->group(function () {
    Route::post('subscribe', [SubscriptionController::class, 'store']);
    Route::get('subscription', [SubscriptionController::class, 'show']);
    Route::post('subscription/cancel', [SubscriptionController::class, 'cancel']);
    Route::post('subscription/resume', [SubscriptionController::class, 'resume']);
    Route::post('subscription/pause', [SubscriptionController::class, 'pause']);

    Route::post('access/grant', [ProductAccessController::class, 'grant']);
    Route::post('access/revoke', [ProductAccessController::class, 'revoke']);
    Route::get('access/check', [ProductAccessController::class, 'check']);
});
