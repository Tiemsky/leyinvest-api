<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    | Ces valeurs ne sont utilisées QUE si le middleware
    | EnsureFrontendRequestsAreStateful est activé (ce qui n'est PAS le cas).
    | Ici, on reste en mode API stateless → donc ignoré.
    */
    'stateful' => [], // Token based via cookies → pas de stateful

    // Durées personnalisées (utilisées par RefreshTokenService)
    'access_token_expiration' => env('SANCTUM_ACCESS_TOKEN_EXPIRATION', 60),
    'refresh_token_expiration' => env('SANCTUM_REFRESH_TOKEN_EXPIRATION', 43200),

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),
];
