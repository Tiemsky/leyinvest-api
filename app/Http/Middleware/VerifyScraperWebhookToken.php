<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyScraperWebhookToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-Webhook-Token');
        $expectedToken = config('services.scraper.webhook_token');

        if (! $token || $token !== $expectedToken) {
            Log::warning("Tentative d'accès Webhook échouée", [
                'ip' => $request->ip(),
                'token_recu' => $token ? 'Présent' : 'Manquant',
            ]);

            return response()->json(['message' => 'Unauthorized. Invalid Token.'], 401);
        }

        return $next($request);
    }
}
