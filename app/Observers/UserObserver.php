<?php

namespace App\Observers;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // --- SÉCURITÉ : Restriction par rôle ---
        // Seuls les utilisateurs "standards" reçoivent un abonnement automatique.
        // Les admins, modérateurs, etc. sont ignorés.
        if ($user->role !== RoleEnum::USER->value) { return; }

        try {
            // 1. Récupérer le plan gratuit (via Scope ou Slug)
            $freePlan = Plan::where('slug', 'gratuit')->where('is_active', true)->first();

            if (!$freePlan) {
                Log::warning("Observer: Plan gratuit introuvable pour l'utilisateur {$user->id}.");
                return;
            }

            // 2. Création via le Service
            $this->subscriptionService->subscribe($user, $freePlan, [
                'payment_status' => 'paid',
                'payment_method' => 'system_auto_assignment',
                'starts_at' => now(),
            ]);

            Log::info("Observer: Abonnement gratuit assigné au nouveau client {$user->id}.");

        } catch (\Exception $e) {
            Log::error("Observer: Échec de l'assignation du plan gratuit pour {$user->id}", [
                'error' => $e->getMessage()
            ]);
        }
    }
}
