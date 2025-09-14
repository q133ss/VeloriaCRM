<?php

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
});
