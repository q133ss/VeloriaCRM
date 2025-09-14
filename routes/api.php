<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
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
    });
});
