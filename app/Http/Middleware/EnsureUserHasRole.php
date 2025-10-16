<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authentification requise.',
                'code' => 401
            ], 401);
        }

        if ($user->role !== $role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Accès refusé. Rôle insuffisant.',
                'code' => 403
            ], 403);
        }
        return $next($request);
    }
}
