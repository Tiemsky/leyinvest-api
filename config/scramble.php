<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    /*
     * On cible uniquement le préfixe API pour éviter de polluer la doc
     * avec d'éventuelles routes web/Inertia.
     */
    'api_path' => 'api',

    'api_domain' => null,

    /*
     * Très important pour ton intégration Nuxt : le chemin du JSON
     * que ton script de synchronisation viendra lire.
     */
    'export_path' => 'api.json',

    'info' => [
        'version' => env('API_VERSION', '1.0.0'),
        'description' => 'Documentation technique de l\'API LeyInvest.',
    ],

    'ui' => [
        'title' => 'LeyInvest API Docs',

        // Mode sombre par défaut : plus confortable pour les devs
        'theme' => 'dark',

        // Indispensable en staging pour tester sans Postman
        'hide_try_it' => false,

        'hide_schemas' => false,

        // Tu peux ajouter l'URL de ton logo ici pour un look ultra pro
        'logo' => '',

        'try_it_credentials_policy' => 'include',

        /*
         * Layout 'sidebar' : c'est le standard des API modernes (comme Stripe).
         * Plus efficace pour naviguer dans une stack backend heavy.
         */
        'layout' => 'sidebar',
    ],

    /*
     * Configuration des serveurs pour Dokploy.
     * On définit clairement le staging et la prod.
     */
    'servers' => [
        'Local' => config('app.url') . '/api',
        'Staging' => 'https://staging.api.leyinvest.com/api',
        'Production' => 'https://api.leyinvest.com/api',
    ],

    /*
     * Stratégies pour les Enums PHP 8.2+
     * Scramble va extraire les valeurs pour que ton front Nuxt sache exactement
     * quelles chaînes de caractères envoyer.
     */
    'enum_cases_description_strategy' => 'description',
    'enum_cases_names_strategy' => 'names',

    /*
     * On garde à TRUE pour Laravel.
     * Laravel traite les tableaux de requête sous la forme 'foo[bar]'.
     */
    'flatten_deep_query_parameters' => true,

    'middleware' => [
        'web',
        RestrictedDocsAccess::class, // Ta Gate définie dans AppServiceProvider
    ],

    'extensions' => [
        // Ici tu pourras ajouter des extensions pour documenter
        // automatiquement tes fichiers Spatie Media Library ou Excel
    ],
];
