<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;

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

    Route::resource('clients', ClientController::class);

    Route::get('/orders', function () {
        return view('orders.index');
    })->name('orders.index');
    Route::get('/orders/create', function () {
        return view('orders.create');
    })->name('orders.create');
    Route::get('/orders/{order}', function ($order) {
        return view('orders.show', ['orderId' => $order]);
    })->name('orders.show');
    Route::get('/orders/{order}/edit', function ($order) {
        return view('orders.edit', ['orderId' => $order]);
    })->name('orders.edit');
});
