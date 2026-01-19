<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => 'false',
                'message' => 'Utilisateur non authentifié.',
                'code' => 401,
            ], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => 'false',
                'message' => 'Accès refusé. Rôle insuffisant.',
                'code' => 403,
            ], 403);
        }

        return $next($request);
    }
}
