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
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | SÉCURITÉ : Origins autorisées
    |--------------------------------------------------------------------------
    |
    | Avec supports_credentials=true, il est CRITIQUE de spécifier les origins
    | autorisées plutôt que d'utiliser '*'. Configurez FRONTEND_URL dans .env
    |
    */
    // 'allowed_origins' => array_filter([
    //     env('FRONTEND_URL', 'http://localhost:3000'),
    //     env('APP_URL', 'http://localhost:8000'),
    //     'https://leyinvest.vercel.app'
    //     // Ajouter d'autres origins autorisées si nécessaire
    // ]),

    'allowed_origins' => ['*'],


    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
    |--------------------------------------------------------------------------
    | Support des Credentials (Cookies HTTP-only)
    |--------------------------------------------------------------------------
    |
    | Activé pour permettre l'envoi de cookies HTTP-only entre le frontend et l'API.
    | Requis pour la sécurité des refresh tokens contre les attaques XSS.
    |
    */
    'supports_credentials' => true,

];
