<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Cookie;

class CookieService
{
    public function createRefreshTokenCookie(string $refreshToken): Cookie
    {
        $origin = request()->headers->get('origin');
        $isLocal = str_contains($origin, 'localhost') || str_contains($origin, '127.0.0.1');
        $isStagingOrProd = app()->environment(['production', 'staging']);

        return new Cookie(
            'refresh_token',
            $refreshToken,
            now()->addDays(30),
            '/',
            // En local on ne met pas de domaine pour localhost, en staging on utilise .leyinvest.com
            ($isLocal) ? null : config('session.domain'),

            // Obligatoire true si SameSite=None (Staging/Prod)
            $isStagingOrProd ? true : config('session.secure'),

            true, // httpOnly
            false,

            // 'None' est impératif pour que localhost puisse lire le cookie du staging
            $isStagingOrProd ? 'None' : 'Lax'
        );
    }

    public function forgetRefreshTokenCookie(): Cookie
    {
        return cookie()->forget('refresh_token', '/', config('session.domain'));
    }
}
