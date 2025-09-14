<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register.form');
Route::view('/forgot-password', 'auth.forgot')->name('password.request');

Route::middleware('token.cookie')->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::view('/profile', 'profile')->name('profile');
    Route::view('/settings', 'settings')->name('settings');
});
