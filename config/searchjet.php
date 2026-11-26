<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site API key (private)
    |--------------------------------------------------------------------------
    |
    | The site's API key from the SearchJet dashboard. Used for indexing,
    | analytics and client info. Keep it on the server; never send it to a browser.
    |
    */

    'api_key' => env('SEARCHJET_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Public search key (optional)
    |--------------------------------------------------------------------------
    |
    | The key GET /v1/search uses. Leave it empty and the package fetches it from
    | GET /v1/client-info with the API key above, then caches it.
    |
    */

    'search_key' => env('SEARCHJET_SEARCH_KEY'),

    /*
    |--------------------------------------------------------------------------
    | API base URL
    |--------------------------------------------------------------------------
    */

    'base_url' => env('SEARCHJET_BASE_URL', 'https://app.searchjetengine.com/api'),

    /*
    |--------------------------------------------------------------------------
    | HTTP
    |--------------------------------------------------------------------------
    |
    | Retries happen only when the connection fails, never after an API error.
    |
    */

    'http' => [
        'timeout' => (int) env('SEARCHJET_HTTP_TIMEOUT', 10),
        'retries' => (int) env('SEARCHJET_HTTP_RETRIES', 2),
        'retry_sleep_ms' => (int) env('SEARCHJET_HTTP_RETRY_SLEEP_MS', 200),
    ],

    /*
    |--------------------------------------------------------------------------
    | Search key cache
    |--------------------------------------------------------------------------
    |
    | How long a search key fetched from /v1/client-info is cached, and in which
    | cache store (null = the default store).
    |
    */

    'search_key_cache' => [
        'store' => env('SEARCHJET_CACHE_STORE'),
        'ttl' => (int) env('SEARCHJET_SEARCH_KEY_TTL', 3600),
    ],

];
