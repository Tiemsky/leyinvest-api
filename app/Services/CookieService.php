<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Cookie;

class CookieService
{
    public function createRefreshTokenCookie(string $refreshToken): Cookie
    {
        // Backend en staging/production = toujours HTTPS
        $secure = true;
        $sameSite = 'None'; // Obligatoire pour cross-origin (local ↔ staging)
        $domain = null; // Ou '.leyinvest.com' si tu veux partager entre sous-domaines

        return new Cookie(
            'refresh_token',
            $refreshToken,
            now()->addDays(30),
            '/',
            $domain,
            $secure,   // ✅ true
            true,      // HttpOnly
            false,
            $sameSite  // ✅ 'None'
        );
    }

    public function forgetRefreshTokenCookie(): Cookie
    {
        return Cookie::create(
            'refresh_token',
            '',
            now()->subDays(1),
            '/',
            null,
            true,      // Secure
            true,      // HttpOnly
            false,
            'None'     // SameSite
        );
    }
}
