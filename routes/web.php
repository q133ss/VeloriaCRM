<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

# TODO Дизайн страниц делаем сами, остальное за нейронкой!

# TODO Мидлвар: редирект если авторизован
Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register.form');
Route::view('/forgot-password', 'auth.forgot')->name('password.request');

# TODO Мидлвар: редирект на login если не авторизован
Route::middleware('token.cookie')->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::view('/profile', 'profile')->name('profile');
    # TODO Даты выходных сделать календарем! Что бы выбирать период было удобнее
    Route::view('/settings', 'settings')->name('settings');

    Route::post('/orders/bulk-action', [OrderController::class, 'bulkAction'])->name('orders.bulk-action');
    Route::post('/orders/quick-create', [OrderController::class, 'quickStore'])->name('orders.quick-store');
    Route::post('/orders/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
    Route::post('/orders/{order}/start', [OrderController::class, 'start'])->name('orders.start');
    Route::post('/orders/{order}/remind', [OrderController::class, 'remind'])->name('orders.remind');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/reschedule', [OrderController::class, 'reschedule'])->name('orders.reschedule');
    Route::resource('orders', OrderController::class);
});
