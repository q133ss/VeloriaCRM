<?php

return [
    'default' => env('BROADCAST_CONNECTION', 'log'),

    'connections' => [
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            // The Pusher SDK checks `array_key_exists('host', $options)`, not
            // truthiness — an empty PUSHER_HOST (the normal case for a plain
            // pusher.com/cluster setup) still won an empty-string host over
            // `cluster` and produced an unreachable `https://:443`. Only add
            // host/port/scheme when a real custom host is configured (a
            // self-hosted/Pusher-compatible server), so a stock pusher.com
            // app resolves its host from `cluster` like the SDK intends.
            'options' => array_filter([
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'useTLS' => env('PUSHER_SCHEME', 'https') === 'https',
                'host' => env('PUSHER_HOST') ?: null,
                'port' => env('PUSHER_HOST') ? env('PUSHER_PORT') : null,
                'scheme' => env('PUSHER_HOST') ? env('PUSHER_SCHEME') : null,
                'encrypted' => true,
            ], fn ($value) => $value !== null),
        ],

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],
    ],
];
