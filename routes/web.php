<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register');
Route::view('/password/forgot', 'auth.forgot-password')->name('password.request');
Route::view('/password/reset', 'auth.reset-password')->name('password.reset');
