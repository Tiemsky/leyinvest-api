<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Cookie;

class CookieService
{
    public function createRefreshTokenCookie(string $refreshToken): Cookie
    {
        return new Cookie(
            'refresh_token',
            $refreshToken,
            now()->addDays(30), // Durée du refresh token
            '/',
            config('session.domain'),
            config('session.secure'), // false en local via .env
            true, // httpOnly : Invisible pour le JS (Sécurité)
            false,
            config('session.same_site', 'lax')
        );
    }

    public function forgetRefreshTokenCookie(): Cookie
    {
        return cookie()->forget('refresh_token');
    }
}
