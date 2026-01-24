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
            Log::warning("Tentative d'accès Webhook échouée du scrapping", [
                'ip' => $request->ip(),
                'token_reçu' => $token ? 'Présent' : 'Manquant',
                'token_attendu' => $expectedToken ? 'Présent' : 'Manquant',
                'token' => $token,
            ]);

            return response()->json(['message' => 'Unauthorized. Invalid Token.'], 401);
        }

        return $next($request);
    }
}
