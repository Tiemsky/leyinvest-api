<?php

use Illuminate\Support\Env;

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => (function () {
        $allowed = [];

        // 1. Charger les origines principales depuis CORS_ALLOWED_ORIGINS
        $mainOrigins = Env::get('CORS_ALLOWED_ORIGINS', '');
        if ($mainOrigins) {
            $allowed = array_map('trim', explode(',', $mainOrigins));
            $allowed = array_filter($allowed);
        }

        // 2. En local, ajouter les origines de développement
        if (app()->environment('local')) {
            // Lire depuis .env ou utiliser des valeurs par défaut
            $localOrigins = Env::get('CORS_LOCAL_ORIGINS', 'http://localhost:8080,http://127.0.0.1:8080');
            $localUrls = array_map('trim', explode(',', $localOrigins));
            $localUrls = array_filter($localUrls);

            // Fusionner sans doublons
            $allowed = array_values(array_unique(array_merge($allowed, $localUrls)));
        }

        // 3. Sécurité : bloquer en production si aucune origine définie
        if (empty($allowed) && ! app()->environment('local')) {
            throw new RuntimeException(
                'CORS_ALLOWED_ORIGINS must be configured in non-local environments.'
            );
        }

        return $allowed;
    })(),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'Origin',
        'X-Request-ID',
    ],

    'exposed_headers' => [
        'Authorization',
        'X-Request-ID',
    ],

    'max_age' => 86400,

    'supports_credentials' => true,
];
