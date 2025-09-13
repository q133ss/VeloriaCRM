<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register'])->name('api.register');
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('api.logout');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('api.forgot');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('api.reset');
