<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionFeature
{
    /**
     * Vérifie si l'utilisateur a accès à une feature spécifique.
     *
     * @param  string  $featureKey  - La clé de la feature à vérifier (ex: 'articles_premium')
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $user = Auth::user();

        // 1. Vérification de l'authentification
        if (! $user) {
            return $this->unauthorizedResponse($request);
        }

        // 2. Vérification de la Feature
        // La logique est déléguée au modèle User/Plan pour vérifier la relation Many-to-Many
        if (! $user->hasFeature($featureKey)) {

            // Accès refusé : L'utilisateur n'a pas la feature dans son plan
            return $this->forbiddenResponse($request, $user, $featureKey);
        }

        // Accès autorisé
        return $next($request);
    }

    // --- Méthodes de Réponse Déléguées pour la robustesse (Web vs. API) ---

    /**
     * Réponse pour l'utilisateur non authentifié (401).
     */
    protected function unauthorizedResponse(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié. Veuillez vous connecter.',
            ], 401);
        }

        // Pour les applications Web
        return redirect()->guest(route('login'));
    }

    /**
     * Réponse pour l'utilisateur n'ayant pas la feature (403 - Upgrade requis).
     */
    protected function forbiddenResponse(Request $request, $user, string $featureKey): Response
    {
        // On suppose que la relation 'activeSubscription' et 'plan' sont bien définies
        $subscription = $user->activeSubscription;
        $currentPlan = $user->plan;

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette fonctionnalité n\'est pas incluse dans votre plan actuel.',
                'feature_required' => $featureKey,
                'current_plan' => [
                    'name' => $currentPlan?->nom ?? 'Aucun',
                    'slug' => $currentPlan?->slug ?? null,
                ],
                'upgrade_required' => true,
            ], 403);
        }

        // Pour les applications Web, rediriger vers la page de tarification
        // Remplacez 'pricing.upgrade' par le nom de votre route de page de tarification
        return redirect()->route('pricing.upgrade')
            ->with('error', "La fonctionnalité '{$featureKey}' n'est pas disponible dans votre plan actuel. Veuillez mettre à niveau.");
    }
}
