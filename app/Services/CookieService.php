<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Cookie;

class CookieService
{
    public function createRefreshTokenCookie(string $refreshToken): Cookie
    {
        $origin = request()->headers->get('origin');
        $isLocal = $origin && (str_contains($origin, 'localhost') || str_contains($origin, '127.0.0.1'));
        $isStagingOrProd = app()->environment(['production', 'staging']);

        // ⚠️ VALIDATION DE L'ORIGINE CRITIQUE
        $allowedOrigins = explode(',', env('CORS_ALLOWED_ORIGINS', ''));
        if (! $isLocal && ! in_array($origin, $allowedOrigins)) {
            throw new \Exception('Origin non autorisée');
        }

        return new Cookie(
            'refresh_token',
            $refreshToken,
            now()->addDays(30),
            '/',
            $isLocal ? null : config('session.domain'), // .domaine.com
            $isStagingOrProd, // Secure: true en prod (HTTPS obligatoire)
            true, // HTTPOnly: JAMAIS accessible en JS (protection XSS)
            false, // raw
            $isStagingOrProd ? 'None' : 'Lax' // SameSite: None pour cross-origin
        );
    }

    public function forgetRefreshTokenCookie(): Cookie
    {
        $isStagingOrProd = app()->environment(['production', 'staging']);

        return Cookie::create(
            'refresh_token',
            '',
            now()->subDays(1),
            '/',
            $isStagingOrProd ? config('session.domain') : null,
            $isStagingOrProd,
            true,
            false,
            $isStagingOrProd ? 'None' : 'Lax'
        );
    }
}
