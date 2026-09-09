<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Admin\AdminPageController;
use App\Http\Controllers\ClientPortal\MagicLinkRedirectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LandingRequestController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::middleware('set.locale')->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

    Route::get('/l/{slug}', LandingPageController::class)->name('landings.public');
    Route::post('/l/{slug}/request', LandingRequestController::class)
        ->middleware('throttle:10,1')
        ->name('landings.request');

    // Обе ссылки стоят в футере каждой страницы кабинета и вели в 404.
    foreach (['terms', 'policy'] as $document) {
        Route::get('/' . $document, function () use ($document) {
            return view('legal', [
                'title' => __('legal.' . $document . '.title'),
                'body' => __('legal.' . $document . '.body'),
                'updatedAt' => '8 сентября 2026',
                'contactEmail' => config('mail.from.address', 'hello@veloria.ru'),
            ]);
        })->name($document);
    }

    Route::view('/login', 'auth.login')->name('login');
    Route::view('/register', 'auth.register')->name('register.form');
    Route::view('/forgot-password', 'auth.forgot')->name('password.request');

    Route::prefix('auth')->group(function () {
        // Browser landing page for the client mobile app's magic-link email
        // (App\Services\ClientPortal\ClientPortalAuthService::buildMagicLink()).
        Route::get('verify', MagicLinkRedirectController::class)->name('client-portal.magic-link.redirect');

        Route::get('{provider}/redirect', [SocialAuthController::class, 'redirect'])
            ->whereIn('provider', SocialAuthController::SUPPORTED_PROVIDERS)
            ->name('social.redirect');

        Route::get('{provider}/callback', [SocialAuthController::class, 'callback'])
            ->whereIn('provider', SocialAuthController::SUPPORTED_PROVIDERS)
            ->name('social.callback');
    });

    Route::middleware('token.cookie')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
        Route::view('/profile', 'profile')->name('profile');
        Route::view('/settings', 'settings')->name('settings');
        Route::view('/integrations', 'integrations')->name('integrations');
        Route::view('/calendar', 'calendar.index')->name('calendar');
        Route::view('/analytics', 'analytics.index')->name('analytics');
        Route::redirect('/learning', '/useful')->name('learning');
        Route::redirect('/trends', '/useful')->name('trends');
        Route::view('/useful', 'useful.index')->name('useful');

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
        Route::get('/orders/{order}/start-confirmation', function ($order) {
            return view('orders.start-confirmation', ['orderId' => $order]);
        })->name('orders.start-confirmation');

        Route::get('/services', function () {
            return view('services.index');
        })->name('services.index');

        // Сайт мастера переехал в бесплатный тариф: им клиенток привлекают, а не
        // удерживают, и держать его за замком — значит закрывать вход в продукт.
        // Ограничение осталось, но по количеству сайтов, а не по тарифу —
        // LandingController::ensureWithinLandingLimit().
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/landings', function () {
                return view('landings.index');
            })->name('landings.index');

            Route::get('/landings/create', function () {
                return view('landings.create');
            })->name('landings.create');

            Route::get('/landings/{landing}/edit', function ($landing) {
                return view('landings.edit', ['landingId' => $landing]);
            })->name('landings.edit');
        });

        // Рассылки платные и остаются платными: ими клиенток возвращают.
        // Раньше на Lite экран отдавал 200 и полноценный интерфейс с нулями в
        // счётчиках, за которым API возвращал 403.
        Route::middleware(['auth:sanctum', 'plan:pro'])->group(function () {
            Route::view('/marketing', 'marketing.index')->name('marketing');
        });
        // Chat is open on every plan — no `plan:pro` gate here.
        Route::view('/messages', 'chat.index')->name('messages');
        Route::view('/help', 'help.index')->name('help');
        Route::view('/notifications', 'notifications.index')->name('notifications.index');

        Route::middleware('auth:sanctum')->group(function () {
            Route::view('/subscription', 'subscription.index')->name('subscription');
        });

        Route::prefix('admin')
            ->middleware(['auth:sanctum', 'user.active', 'admin.access'])
            ->group(function () {
                Route::redirect('/', '/admin/overview');
                Route::get('/overview', [AdminPageController::class, 'overview'])->name('admin.overview');
                Route::get('/users', [AdminPageController::class, 'users'])->name('admin.users');
                Route::get('/useful', [AdminPageController::class, 'useful'])->name('admin.useful');
                Route::get('/support', [AdminPageController::class, 'support'])->name('admin.support');
                Route::get('/audit', [AdminPageController::class, 'audit'])->name('admin.audit');
            });
    });
});

// Digital Asset Links file Android needs to auto-verify the client mobile app's
// App Link (see config/services.php's client_portal.android_* keys). Outside the
// set.locale group deliberately — it's a machine-read manifest, not a page.
Route::get('/.well-known/assetlinks.json', function () {
    $fingerprints = array_values(array_filter(array_map(
        'trim',
        explode(',', (string) config('services.client_portal.android_sha256_fingerprints')),
    )));

    return response()->json([
        [
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target' => [
                'namespace' => 'android_app',
                'package_name' => config('services.client_portal.android_package'),
                'sha256_cert_fingerprints' => $fingerprints,
            ],
        ],
    ]);
})->name('client-portal.assetlinks');
