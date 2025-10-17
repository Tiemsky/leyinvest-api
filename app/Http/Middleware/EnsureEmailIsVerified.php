<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié.',
            ], 401);
        }

        if (!$request->user()->email_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Votre email doit être vérifié pour accéder à cette ressource.',
            ], 403);
        }

        if (!$request->user()->hasCompletedRegistration()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez compléter votre inscription pour accéder à cette ressource.',
            ], 403);
        }

        return $next($request);
    }
}
