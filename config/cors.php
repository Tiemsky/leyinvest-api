<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],

    'allowed_origins' => (function () {
        $allowed = [];

        // 1. Charger les origines principales
        $mainOrigins = $_ENV['CORS_ALLOWED_ORIGINS'] ?? '';
        if ($mainOrigins) {
            $allowed = array_filter(array_map('trim', explode(',', $mainOrigins)));
        }

        // 2. Détecter l'environnement via $_ENV
        $appEnv = $_ENV['APP_ENV'] ?? 'production';

        // En local, ajouter les origines de dev
        if ($appEnv === 'local') {
            $localOrigins = $_ENV['CORS_LOCAL_ORIGINS'] ?? 'http://localhost:8080,http://127.0.0.1:8080';
            $localUrls = array_filter(array_map('trim', explode(',', $localOrigins)));
            $allowed = array_values(array_unique(array_merge($allowed, $localUrls)));
        }

        // 3. Sécurité : exiger CORS_ALLOWED_ORIGINS en production/staging
        if (empty($allowed) && $appEnv !== 'local') {
            throw new RuntimeException(
                'CORS_ALLOWED_ORIGINS must be configured in non-local environments.'
            );
        }

        return $allowed;
    })(),

    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin', 'X-Request-ID'],
    'exposed_headers' => ['Authorization', 'X-Request-ID'],
    'max_age' => 86400,
    'supports_credentials' => true,
];
