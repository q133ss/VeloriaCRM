<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'yookassa' => [
        'shop_id' => env('YOOKASSA_SHOP_ID'),
        'secret_key' => env('YOOKASSA_SECRET_KEY'),
        'return_url' => env('YOOKASSA_RETURN_URL', env('APP_URL').'/subscription'),
        'currency' => env('YOOKASSA_CURRENCY', 'RUB'),
    ],

    'vkontakte' => [
        'client_id' => env('VKONTAKTE_CLIENT_ID'),
        'client_secret' => env('VKONTAKTE_CLIENT_SECRET'),
        'redirect' => env('VKONTAKTE_REDIRECT_URI'),
        'scopes' => ['email'],
        'version' => env('VKONTAKTE_API_VERSION', '5.131'),
    ],

    'client_portal' => [
        'mobile_scheme' => env('CLIENT_PORTAL_MOBILE_SCHEME', 'veloriaclient'),
        // Android App Link host for the magic-link email. Must match the domain
        // verified in the mobile app's intentFilters (Client Mobile App/app.json)
        // and the fingerprints below, or Android will only fall back to the
        // in-browser redirect page instead of opening the app directly.
        'app_link_host' => env('CLIENT_PORTAL_APP_LINK_HOST', parse_url((string) env('APP_URL', ''), PHP_URL_HOST) ?: 'veloria.io'),
        'android_package' => env('CLIENT_PORTAL_ANDROID_PACKAGE', 'ru.veloria.client'),
        // Comma-separated SHA-256 signing certificate fingerprints, required for
        // Android to verify the App Link via /.well-known/assetlinks.json. Empty
        // until a real release keystore exists — the link still works via the
        // browser-redirect fallback, just without the seamless one-tap open.
        'android_sha256_fingerprints' => env('CLIENT_PORTAL_ANDROID_SHA256_FINGERPRINTS', ''),
    ],

    'yandex' => [
        'client_id' => env('YANDEX_CLIENT_ID'),
        'client_secret' => env('YANDEX_CLIENT_SECRET'),
        'redirect' => env('YANDEX_REDIRECT_URI'),
        'scopes' => ['login:email'],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'scopes' => ['openid', 'profile', 'email'],
    ],

];
