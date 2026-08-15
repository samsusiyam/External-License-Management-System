<?php

/**
 * ELMS License SDK configuration.
 * Publish with: php artisan vendor:publish --tag=elms-config
 */

return [
    'server'    => env('ELMS_SERVER', 'https://license.example.com'),
    'api_key'   => env('ELMS_API_KEY', ''),
    'secret'    => env('ELMS_API_SECRET', ''),
    'product'   => env('ELMS_PRODUCT', ''),
    'cache_ttl' => (int) env('ELMS_CACHE_TTL', 43200),
];
