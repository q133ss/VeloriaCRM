<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\V1\ClientController as ApiClientController;
use App\Http\Controllers\Api\V1\OrderController as ApiOrderController;
use App\Http\Controllers\Api\V1\ServiceCategoryController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\UserController;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('api.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('api.logout');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('api.forgot');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('api.reset');
    Route::middleware('auth:sanctum')->get('/auth/me', [AuthController::class, 'me'])->name('api.me');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/settings', [SettingController::class, 'index']);
        Route::patch('/settings', [SettingController::class, 'update']);
        Route::delete('/user', [UserController::class, 'destroy']);
        Route::get('/clients/options', [ApiClientController::class, 'options']);
        Route::get('/clients', [ApiClientController::class, 'index']);
        Route::post('/clients', [ApiClientController::class, 'store']);
        Route::get('/clients/{client}', [ApiClientController::class, 'show']);
        Route::get('/clients/{client}/analytics', [ApiClientController::class, 'analytics']);
        Route::get('/clients/{client}/recommendations', [ApiClientController::class, 'recommendations']);
        Route::patch('/clients/{client}', [ApiClientController::class, 'update']);
        Route::delete('/clients/{client}', [ApiClientController::class, 'destroy']);
        Route::get('/orders/options', [ApiOrderController::class, 'options']);
        Route::get('/orders', [ApiOrderController::class, 'index']);
        Route::post('/orders', [ApiOrderController::class, 'store']);
        Route::get('/orders/{order}', [ApiOrderController::class, 'show']);
        Route::patch('/orders/{order}', [ApiOrderController::class, 'update']);
        Route::delete('/orders/{order}', [ApiOrderController::class, 'destroy']);
        Route::post('/orders/bulk', [ApiOrderController::class, 'bulk']);
        Route::post('/orders/quick-create', [ApiOrderController::class, 'quickStore']);
        Route::post('/orders/{order}/complete', [ApiOrderController::class, 'complete']);
        Route::post('/orders/{order}/start', [ApiOrderController::class, 'start']);
        Route::post('/orders/{order}/remind', [ApiOrderController::class, 'remind']);
        Route::post('/orders/{order}/cancel', [ApiOrderController::class, 'cancel']);
        Route::post('/orders/{order}/reschedule', [ApiOrderController::class, 'reschedule']);
        Route::get('/orders/{order}/analytics', [ApiOrderController::class, 'analytics']);
        Route::get('/services/options', [ServiceController::class, 'options']);
        Route::get('/services', [ServiceController::class, 'index']);
        Route::post('/services', [ServiceController::class, 'store']);
        Route::get('/services/{service}', [ServiceController::class, 'show']);
        Route::patch('/services/{service}', [ServiceController::class, 'update']);
        Route::delete('/services/{service}', [ServiceController::class, 'destroy']);
        Route::get('/service-categories', [ServiceCategoryController::class, 'index']);
        Route::post('/service-categories', [ServiceCategoryController::class, 'store']);
        Route::patch('/service-categories/{category}', [ServiceCategoryController::class, 'update']);
        Route::delete('/service-categories/{category}', [ServiceCategoryController::class, 'destroy']);
    });
});
