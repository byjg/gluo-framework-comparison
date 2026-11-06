<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/refreshtoken', [AuthController::class, 'refresh']);

    Route::apiResource('product', ProductController::class)->only(['index', 'show']);
    Route::apiResource('product', ProductController::class)->only(['store', 'update', 'destroy'])
        ->middleware('can:admin');
});
