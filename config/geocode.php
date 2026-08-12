<?php

return [
    'api_key' => env('GEOCODE_API_KEY', ''),
    'language' => env('GEOCODE_LANGUAGE', 'en'),
    'timeout' => (int) env('GEOCODE_TIMEOUT', 10),
    'retry' => [
        'times' => (int) env('GEOCODE_RETRY_TIMES', 2),
        'sleep' => (int) env('GEOCODE_RETRY_SLEEP', 100),
    ],
];
