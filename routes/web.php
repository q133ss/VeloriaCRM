<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/l/{slug}', LandingPageController::class)->name('landings.public');

# TODO Дизайн страниц делаем сами, остальное за нейронкой!

# TODO Мидлвар: редирект если авторизован
Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register.form');
Route::view('/forgot-password', 'auth.forgot')->name('password.request');

# TODO Мидлвар: редирект на login если не авторизован
Route::middleware('token.cookie')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
    Route::view('/profile', 'profile')->name('profile');
    # TODO Даты выходных сделать календарем! Что бы выбирать период было удобнее
    Route::view('/settings', 'settings')->name('settings');
    Route::view('/calendar', 'calendar.index')->name('calendar');
    Route::view('/analytics', 'analytics.index')->name('analytics');
    Route::view('/learning', 'learning.index')->name('learning');

    Route::get('/clients', function () {
        return view('clients.index');
    })->name('clients.index');

    Route::get('/clients/create', function () {
        return view('clients.create');
    })->name('clients.create');

    Route::get('/clients/{client}', function ($client) {
        return view('clients.show', ['clientId' => $client]);
    })->name('clients.show');

    Route::get('/clients/{client}/edit', function ($client) {
        return view('clients.edit', ['clientId' => $client]);
    })->name('clients.edit');

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

    Route::get('/services', function () {
        return view('services.index');
    })->name('services.index');

    Route::get('/landings', function () {
        return view('landings.index');
    })->name('landings.index');

    Route::get('/landings/create', function () {
        return view('landings.create');
    })->name('landings.create');

    Route::get('/landings/{landing}/edit', function ($landing) {
        return view('landings.edit', ['landingId' => $landing]);
    })->name('landings.edit');

    Route::view('/marketing', 'marketing.index')->name('marketing');
    Route::view('/help', 'help.index')->name('help');
});
