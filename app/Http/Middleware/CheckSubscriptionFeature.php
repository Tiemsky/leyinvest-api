<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSubscriptionFeature
{
    public function handle(Request $request, Closure $next, string $feature)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success'   => false,
                'message'   => 'Non authentifié.'
            ], 401);
        }

        if (!$user->hasFeature($feature)) {
            return response()->json([
                'success'       => false,
                'message'       => 'Cette fonctionnalité nécessite un abonnement supérieur.',
                'feature'       => $feature,
                'current_plan'  => $user->activeSubscription?->plan->name ?? 'Aucun',
                // 'upgrade_url'   => route('api.plans.index')
            ], 403);
        }

        return $next($request);
    }
}
