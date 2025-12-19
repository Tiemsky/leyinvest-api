<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    // On inclut sanctum/csrf-cookie pour React et toutes les routes API
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | SÉCURITÉ : Origins dynamiques (Local vs Prod)
    |--------------------------------------------------------------------------
    */
    'allowed_origins' => (function () {
        // 1. On récupère les URLs définies dans le .env (séparées par des virgules)
        $allowed = env('ALLOWED_FRONTEND_URLS')
            ? explode(',', env('ALLOWED_FRONTEND_URLS'))
            : [];

        // 2. Si on est en environnement local, on ajoute les URLs de dev courantes
        // pour éviter d'être bloqué si on change de port
        if (env('APP_ENV') === 'local') {
            $localUrls = [
                'http://localhost:3000',
                'http://localhost:5173', // Port par défaut de Vite (React)
                'http://localhost:8080',
                'http://127.0.0.1:3000',
                'http://127.0.0.1:5173',
                'http://127.0.0.1:8080',
            ];
            $allowed = array_unique(array_merge($allowed, $localUrls));
        }

        return $allowed;
    })(),

    // Note pour le Mobile : Les applications mobiles natives (Flutter/React Native)
    // n'envoient pas de header "Origin". Elles ne sont donc pas bloquées par le CORS.

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'], // Permet headers Authorization, X-XSRF-TOKEN, etc.

    'exposed_headers' => ['Authorization'],

    'max_age' => 86400, // Cache les pré-requêtes OPTIONS pendant 24h pour la performance

    /*
    |--------------------------------------------------------------------------
    | Support des Credentials (Cookies HTTP-only)
    |--------------------------------------------------------------------------
    */
    'supports_credentials' => true,

];
