<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenExpiration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $token = $user->currentAccessToken();

            if ($token && $token->expires_at) {
                // Vérifier si le token est expiré
                if ($token->expires_at->isPast()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Token expiré. Veuillez utiliser votre refresh token pour obtenir un nouveau token.',
                        'error_code' => 'TOKEN_EXPIRED',
                        'expired_at' => $token->expires_at->toIso8601String(),
                    ], 401);
                }

                // Optionnel : Avertir si le token expire bientôt (dans les 2 prochaines minutes)
                $minutesUntilExpiration = Carbon::now()->diffInMinutes($token->expires_at, false);

                if ($minutesUntilExpiration > 0 && $minutesUntilExpiration <= 2) {
                    // Ajouter un header pour informer le frontend
                    $response = $next($request);
                    $response->headers->set('X-Token-Expires-Soon', 'true');
                    $response->headers->set('X-Token-Expires-In', $minutesUntilExpiration * 60); // en secondes

                    return $response;
                }
            }
        }

        return $next($request);
    }
}
