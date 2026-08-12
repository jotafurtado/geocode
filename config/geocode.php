<?php

return [
    'api_key' => env('GEOCODE_API_KEY', ''),
    'language' => env('GEOCODE_LANGUAGE', 'en'),
    'timeout' => (int) env('GEOCODE_TIMEOUT', 10),
    'retry' => [
        'times' => (int) env('GEOCODE_RETRY_TIMES', 2),
        'sleep' => (int) env('GEOCODE_RETRY_SLEEP', 100),
    ],
    'cache' => [
        'enabled' => (bool) env('GEOCODE_CACHE_ENABLED', false),
        'store' => env('GEOCODE_CACHE_STORE'),
        'ttl' => (int) env('GEOCODE_CACHE_TTL', 86400),
    ],
];
