<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Local AI Service
    |--------------------------------------------------------------------------
    |
    | ai_service is a small FastAPI microservice that drives a real browser to
    | get text out of DuckDuckGo AI Chat (with Google's AI Overview as its own
    | fallback). It costs nothing per request, which is why it goes first, but
    | it serves one request at a time and takes tens of seconds, so every call
    | is bounded by a timeout and every caller keeps its own written fallback.
    |
    */

    'local' => [
        'enabled' => env('AI_LOCAL_ENABLED', true),

        'url' => env('AI_LOCAL_URL', 'http://127.0.0.1:8100'),

        // Background work (scheduler, queue) can afford to wait.
        'timeout' => env('AI_LOCAL_TIMEOUT', 30),

        // Screens where a person is watching a spinner. Kept well under PHP's
        // and nginx's own limits so the OpenAI retry still fits afterwards.
        'sync_timeout' => env('AI_LOCAL_SYNC_TIMEOUT', 20),

        // ai_service rejects anything longer (see ai_service/models.py). A
        // prompt above this is not sent at all: the request goes to OpenAI,
        // which is the right home for the heavy analytical contexts anyway.
        'max_prompt_chars' => env('AI_LOCAL_MAX_PROMPT_CHARS', 2000),

        // After a failure the service is left alone for this many seconds.
        // Without it, every user in turn pays the full timeout while the
        // microservice is down, and they all queue behind one browser.
        'cooldown' => env('AI_LOCAL_COOLDOWN', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    |
    | Which provider is tried first, per task. One of:
    |   local_first, openai_first, local_only, openai_only, off
    |
    */

    'default_route' => env('AI_ROUTE_DEFAULT', 'local_first'),

    'routes' => [
        'outreach_message' => env('AI_ROUTE_OUTREACH', 'local_first'),
        'daily_post_idea' => env('AI_ROUTE_POST_IDEA', 'local_first'),
        'client_recommendations' => env('AI_ROUTE_CLIENT_RECS', 'local_first'),
        'client_analytics' => env('AI_ROUTE_CLIENT_ANALYTICS', 'local_first'),
        'analytics_insights' => env('AI_ROUTE_ANALYTICS', 'local_first'),

        // Order create/update blocks on this one. A slot in a shared browser
        // queue does not belong in the write path, so it stays on OpenAI until
        // the local queue is measured to be idle in practice.
        'order_recommendations' => env('AI_ROUTE_ORDER_RECS', 'openai_first'),

        // A master is looking at the create form waiting for it to fill in.
        // Twenty seconds behind a shared browser is too long to make her wait
        // for something the rules already answered most of, so the paid
        // provider goes first here and the local one is the safety net.
        'booking_intent' => env('AI_ROUTE_BOOKING_INTENT', 'openai_first'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reading a booking out of a phrase
    |--------------------------------------------------------------------------
    |
    | The model is only asked when the rules are unsure, so these budgets are
    | not a wall in front of the feature: over the limit the endpoint still
    | answers, with whatever plain PHP could work out on its own.
    |
    */

    'booking_intent' => [
        'daily_ai_calls_free' => env('AI_BOOKING_INTENT_FREE_CALLS', 40),
        'daily_ai_calls_pro' => env('AI_BOOKING_INTENT_PRO_CALLS', 400),
    ],
];
