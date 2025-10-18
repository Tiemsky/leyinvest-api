<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="SaleResource",
 *     type="object",
 *     title="Ressource Vente",
 *     description="Représentation d'une vente d'actions",
 *     @OA\Property(property="id", type="integer", example=1, description="Identifiant unique de la vente"),
 *     @OA\Property(property="key", type="string", example="SLE-98237AHS", description="Clé unique de la vente"),
 *
 *     @OA\Property(
 *         property="wallet",
 *         type="object",
 *         description="Informations du portefeuille associé",
 *         @OA\Property(property="id", type="integer", example=5, description="Identifiant du portefeuille"),
 *         @OA\Property(property="key", type="string", example="WALLET-5489XYZ", description="Clé unique du portefeuille")
 *     ),
 *
 *     @OA\Property(
 *         property="user",
 *         type="object",
 *         description="Informations de l'utilisateur ayant effectué la vente",
 *         @OA\Property(property="id", type="integer", example=10, description="Identifiant de l'utilisateur"),
 *         @OA\Property(property="nom", type="string", example="Doe", description="Nom de l'utilisateur"),
 *         @OA\Property(property="prenoms", type="string", example="John", description="Prénoms de l'utilisateur"),
 *         @OA\Property(property="email", type="string", example="john.doe@example.com", description="Adresse e-mail de l'utilisateur")
 *     ),
 *
 *     @OA\Property(property="quantite", type="integer", example=50, description="Quantité d'actions vendues"),
 *     @OA\Property(property="prix_par_action", type="number", format="float", example=1500.25, description="Prix unitaire de chaque action"),
 *     @OA\Property(property="montant_vente", type="number", format="float", example=75012.5, description="Montant total de la vente"),
 *     @OA\Property(property="montant_vente_formatted", type="string", example="75 012,50 F CFA", description="Montant formaté pour affichage"),
 *     @OA\Property(property="comment", type="string", example="Vente partielle du portefeuille", nullable=true, description="Commentaire optionnel sur la vente"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-18T09:45:00Z", description="Date de création de la vente"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-18T09:46:00Z", description="Date de dernière mise à jour")
 * )
 */
class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'wallet' => [
                'id' => $this->wallet_id,
                'key' => $this->wallet_key,
            ],
            'user' => [
                'id' => $this->user_id,
                'nom' => $this->user->nom ?? null,
                'prenoms' => $this->user->prenoms ?? null,
                'email' => $this->user->email ?? null,
            ],
            'quantite' => $this->quantite,
            'prix_par_action' => (float) $this->prix_par_action,
            'montant_vente' => (float) $this->montant_vente,
            'montant_vente_formatted' => $this->montant_total,
            'comment' => $this->comment,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
