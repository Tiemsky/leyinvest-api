<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de sécurité pour extraire le refresh token depuis les cookies HTTP-only
 *
 * Ce middleware protège contre les attaques XSS en s'assurant que le refresh token
 * est stocké dans un cookie HTTP-only plutôt que dans le localStorage JavaScript.
 */
class EnsureRefreshTokenFromCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si un refresh_token est présent dans le cookie, l'injecter dans la requête
        if ($request->hasCookie('refresh_token')) {
            $refreshToken = $request->cookie('refresh_token');

            // Injecter dans le request pour que le controller puisse le récupérer
            $request->merge(['refresh_token' => $refreshToken]);
        }

        return $next($request);
    }
}
