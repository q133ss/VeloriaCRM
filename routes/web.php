<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/login', 'auth.login')->name('login.form');
Route::view('/register', 'auth.register')->name('register.form');
Route::view('/forgot-password', 'auth.forgot')->name('password.request');
