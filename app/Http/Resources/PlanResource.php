<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;


 /**
 * * Schéma du PlanResource (Réponse API)
 * * Représente les données d'un plan d'abonnement.
 * * * * @OA\Schema(
 * * schema="PlanResource",
 * * type="object",
 * * title="Plan d'Abonnement",
 * * description="Structure des données d'un plan d'abonnement pour l'API.",
 * * required={"slug", "name", "price", "billing_cycle"},
 * * @OA\Property(property="id", type="integer", example=1, description="ID interne du plan."),
 * * @OA\Property(property="key", type="string", example="PLN_XZY123", description="Clé publique unique du plan."),
 * * @OA\Property(property="slug", type="string", example="premium-annuel", description="Identifiant lisible et unique pour les URLs."),
 * * @OA\Property(property="name", type="string", example="Premium Annuel"),
 * * @OA\Property(property="description", type="string", nullable=true, example="Accès illimité à toutes les fonctionnalités."),
 * * @OA\Property(property="price", type="number", format="float", example=99.99, description="Prix du plan."),
 * * @OA\Property(property="billing_cycle", type="string", enum={"monthly", "quarterly", "yearly"}, example="yearly", description="Fréquence de facturation."),
 * * @OA\Property(property="trial_days", type="integer", example=14, description="Nombre de jours d'essai."),
 * * @OA\Property(
 * * property="status",
 * * type="object",
 * * description="Statut du plan et du contexte utilisateur",
 * * @OA\Property(property="is_active", type="boolean", example=true),
 * * @OA\Property(property="is_visible", type="boolean", example=true),
 * * @OA\Property(property="is_free", type="boolean", example=false),
 * * @OA\Property(property="is_current", type="boolean", nullable=true, example=false, description="Vrai si le plan correspond à l'abonnement actif de l'utilisateur (si connecté)."),
 * * ),
 * * @OA\Property(
 * * property="features",
 * * type="array",
 * * nullable=true,
 * * description="Liste des fonctionnalités actives pour ce plan.",
 * * @OA\Items(
 * * type="object",
 * * @OA\Property(property="key", type="string", example="unlimited_storage"),
 * * @OA\Property(property="name", type="string", example="Stockage illimité"),
 * * @OA\Property(property="enabled", type="boolean", example=true),
 * * )
 * * ),
 * * @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-27T10:00:00.000000Z"),
 * * )
 * */
class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Utilisation de la méthode when() pour inclure la vérification 'is_current'
        // uniquement si l'utilisateur est connecté.
        $isCurrentLogic = $this->when(Auth::check(), function () use ($request) {
            // NOTE: C'est une vérification simplifiée. Dans un système complet,
            // vous devriez vérifier si le plan correspond à l'activeSubscription de l'utilisateur.
            $user = $request->user();
            return $user->activeSubscription?->plan_id === $this->id;
        });

        return [
            // 1. Identification
            'id' => $this->id, // ID interne (pour l'admin/débogage)
            'key' => $this->key, // Clé publique (si le trait HasKey est utilisé)
            'name' => $this->nom,
            'slug' => $this->slug,
            // 3. Tarification
            'price' => (float) $this->prix,
            'billing_cycle' => $this->billing_cycle,
            'trial_days' => (int) $this->trial_days,

            // 4. Statut et Contexte Utilisateur
            'status' => [
                'is_active' => (bool) $this->is_active,
                'is_visible' => (bool) $this->is_visible,
                'is_free' => $this->isFree(), // Utilise la méthode utilitaire du modèle
                'is_current' => $isCurrentLogic, // Booléen ou absent
            ],

            // 5. Fonctionnalités (Conditionnel pour éviter N+1 Query)
            'features' => $this->whenLoaded('activeFeatures', function () {
                // Utilisation de la relation 'activeFeatures' optimisée
                return $this->activeFeatures->map(function ($feature) {
                    return [
                        'name' => $feature->name,
                        'enabled' => (bool) $feature->pivot->is_enabled,
                    ];
                });
            }),

            // 6. Métadonnées (optionnel)
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
