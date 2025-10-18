<?php

return [
    /*
     * Chemins autorisés pour CORS
     */
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'login',
        'logout',
        'register',
    ],

    /*
     * Méthodes HTTP autorisées
     */
    'allowed_methods' => ['*'],

    /*
     * Origines autorisées (PHASE TEST - À RESTREINDRE EN PRODUCTION)
     */
    'allowed_origins' => [
        'http://localhost:3000',      // React/Next.js
        'http://localhost:8080',      // Vue.js
        'http://localhost:5173',      // Vite
        'http://localhost:4200',      // Angular
        'http://127.0.0.1:3000',
        'http://127.0.0.1:8080',
        'http://127.0.0.1:5173',
    ],

    /*
     * Patterns d'origines (alternative aux origines fixes)
     * Utile pour les environnements dynamiques
     */
    'allowed_origins_patterns' => [
        // Décommentez pour permettre tous les localhost en dev
        // '/^http:\/\/localhost:\d+$/',
        // '/^http:\/\/127\.0\.0\.1:\d+$/',
    ],

    /*
     * Headers autorisés
     */
    'allowed_headers' => ['*'],

    /*
     * Headers exposés au client
     * IMPORTANT: Nécessaire si vous renvoyez des headers custom
     */
    'exposed_headers' => [
        'Authorization',
        'X-Total-Count',
        'X-Page-Count',
    ],

    /*
     * Durée de cache des preflight requests (en secondes)
     * 0 = pas de cache (utile en dev pour éviter les problèmes)
     * 86400 = 24h (recommandé en production)
     */
    'max_age' => 0,

    /*
     * Support des credentials (cookies, authorization headers)
     * DOIT ÊTRE TRUE pour Sanctum avec cookies
     */
    'supports_credentials' => true,
];
