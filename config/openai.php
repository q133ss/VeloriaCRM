<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key
    |--------------------------------------------------------------------------
    |
    | The API key used to authenticate against the OpenAI API. This value is
    | required and should be set in the environment configuration.
    |
    */

    'api_key' => env('OPENAI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the OpenAI API. This may be overridden if you proxy the
    | requests or rely on a compatible provider.
    |
    */

    'base_url' => env('OPENAI_API_BASE', 'https://api.openai.com/v1'),

    /*
    |--------------------------------------------------------------------------
    | Default Model & Settings
    |--------------------------------------------------------------------------
    |
    | The default model, temperature and maximum tokens that will be used for
    | chat completions unless explicitly overridden when making a request.
    |
    */

    'default_model' => env('OPENAI_DEFAULT_MODEL', 'gpt-4o-mini'),

    'default_temperature' => env('OPENAI_DEFAULT_TEMPERATURE', 0.2),

    'max_tokens' => env('OPENAI_MAX_TOKENS'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout (in seconds) for HTTP requests made to OpenAI.
    |
    */

    'timeout' => env('OPENAI_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Messaging Defaults
    |--------------------------------------------------------------------------
    |
    | Sensible defaults used when building chat prompts. You may override these
    | values per-request to tailor behaviour for specific tasks.
    |
    */

    'system_message' => env('OPENAI_SYSTEM_MESSAGE'),

    'context_prefix' => env('OPENAI_CONTEXT_PREFIX', 'Context data for this request:'),

    'response_format' => env('OPENAI_RESPONSE_FORMAT'),
];
