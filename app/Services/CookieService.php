<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Cookie;

/**
 * Service centralisé pour la gestion sécurisée des cookies d'authentification
 *
 * Cette classe garantit une configuration uniforme et sécurisée des cookies
 * utilisés pour le stockage des refresh tokens.
 */
class CookieService
{
    /**
     * Nom du cookie contenant le refresh token
     */
    private const REFRESH_TOKEN_COOKIE_NAME = 'refresh_token';

    /**
     * Durée de vie du refresh token en minutes (configurée via sanctum.php)
     */
    private int $refreshTokenExpiration;

    public function __construct()
    {
        $this->refreshTokenExpiration = config('sanctum.refresh_token_expiration', 10080);
    }

    /**
     * Créer un cookie HTTP-only sécurisé pour le refresh token
     *
     * @param string $refreshToken Le token à stocker
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    public function createRefreshTokenCookie(string $refreshToken): Cookie
    {
        return cookie(
            self::REFRESH_TOKEN_COOKIE_NAME,
            $refreshToken,
            $this->refreshTokenExpiration,     // Durée de vie (minutes)
            '/',                                // Path
            null,                               // Domain (null = domaine actuel)
            config('app.env') === 'production', // Secure (HTTPS uniquement en production)
            true,                               // HttpOnly (inaccessible au JavaScript)
            false,                              // Raw (pas d'encodage)
            'strict'                            // SameSite (protection CSRF)
        );
    }

    /**
     * Créer un cookie pour invalider le refresh token (logout)
     *
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    public function createExpiredRefreshTokenCookie(): Cookie
    {
        return cookie(
            self::REFRESH_TOKEN_COOKIE_NAME,
            '',                                 // Valeur vide
            -1,                                 // Expiration immédiate
            '/',                                // Path
            null,                               // Domain
            config('app.env') === 'production', // Secure
            true,                               // HttpOnly
            false,                              // Raw
            'strict'                            // SameSite
        );
    }

    /**
     * Récupérer le nom du cookie pour le refresh token
     *
     * @return string
     */
    public function getRefreshTokenCookieName(): string
    {
        return self::REFRESH_TOKEN_COOKIE_NAME;
    }
}
