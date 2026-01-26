<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // On transforme la chaîne du .env en tableau directement ici
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin', 'X-Request-ID'],

    'exposed_headers' => ['Authorization', 'X-Request-ID'],

    'max_age' => 86400,

    'supports_credentials' => true,

];
