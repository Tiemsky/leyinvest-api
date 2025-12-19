<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next, string $provider = 'default') {
        $header = match($provider) {
            'stripe' => 'Stripe-Signature',
            'fedapay' => 'X-FedaPay-Signature',
            default => 'X-Webhook-Signature',
        };

        $signature = $request->header($header);
        $secret = config("services.$provider.webhook_secret");

        if (!$signature || !$this->validate($request->getContent(), $signature, $secret)) {
            throw new AccessDeniedHttpException('Signature Webhook Invalide.');
        }

        return $next($request);
    }

    private function validate($payload, $signature, $secret): bool {
        return hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
    }
}
