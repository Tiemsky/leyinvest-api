<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyScraperWebhookToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-Webhook-Token');

        // DEBUG TEMPORAIRE
        \Log::info('Token reçu: ' . $token);
        \Log::info('Token attendu: ' . env('FASTAPI_WEBHOOK_TOKEN'));

        if (!$token || $token !== env('FASTAPI_WEBHOOK_TOKEN')) {
            return response()->json(['message' => 'Unauthorized. Invalid Token.'], 401);
        }

        return $next($request);
    }
}
