<?php

return [
    /*
     * Chemins autorisés pour CORS
     */
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'api/v1/auth/*',
    ],

  'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL_LOCAL', 'http://localhost:8000'),
        env('FRONTEND_URL_PROD', 'https://app.yks-ci.com'),
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => false,

    'max_age' => 0,

    'supports_credentials' => true,
];
