<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SearchJet API Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your SearchJet API settings. You can get your
    | API key from your SearchJet dashboard at https://searchjetengine.com
    |
    */

    'api_key' => env('SEARCHJET_API_KEY'),
    'base_url' => env('SEARCHJET_BASE_URL', 'https://api.searchjetengine.com'),
    'site_id' => env('SEARCHJET_SITE_ID'),

    /*
    |--------------------------------------------------------------------------
    | Auto Sync Configuration
    |--------------------------------------------------------------------------
    |
    | Automatically sync model changes to SearchJet when models are created,
    | updated, or deleted. This uses Laravel model observers.
    |
    */

    'auto_sync' => env('SEARCHJET_AUTO_SYNC', true),
    'sync_errors_throw' => env('SEARCHJET_SYNC_ERRORS_THROW', false),

    /*
    |--------------------------------------------------------------------------
    | Default Search Settings
    |--------------------------------------------------------------------------
    |
    | These are the default settings that will be used for search operations
    | unless overridden in the search call.
    |
    */

    'defaults' => [
        'limit' => 20,
        'offset' => 0,
        'attributes_to_retrieve' => ['*'],
        'attributes_to_crop' => [],
        'crop_length' => 200,
        'highlight_pre_tag' => '<mark>',
        'highlight_post_tag' => '</mark>',
        'show_matches_position' => false,
        'facets' => [],
        'filter' => null,
        'sort' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Configuration
    |--------------------------------------------------------------------------
    |
    | Configure analytics tracking for search queries and user behavior.
    |
    */

    'analytics' => [
        'enabled' => env('SEARCHJET_ANALYTICS_ENABLED', true),
        'track_queries' => env('SEARCHJET_TRACK_QUERIES', true),
        'track_clicks' => env('SEARCHJET_TRACK_CLICKS', true),
        'track_zero_results' => env('SEARCHJET_TRACK_ZERO_RESULTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for search results to improve performance.
    |
    */

    'cache' => [
        'enabled' => env('SEARCHJET_CACHE_ENABLED', true),
        'ttl' => env('SEARCHJET_CACHE_TTL', 300), // 5 minutes
        'prefix' => 'searchjet:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for API requests to prevent abuse.
    |
    */

    'rate_limiting' => [
        'enabled' => env('SEARCHJET_RATE_LIMITING_ENABLED', true),
        'max_requests_per_minute' => env('SEARCHJET_MAX_REQUESTS_PER_MINUTE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the HTTP client used for API requests.
    |
    */

    'http' => [
        'timeout' => env('SEARCHJET_HTTP_TIMEOUT', 30),
        'retry_attempts' => env('SEARCHJET_HTTP_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('SEARCHJET_HTTP_RETRY_DELAY', 1000), // milliseconds
    ],
];
