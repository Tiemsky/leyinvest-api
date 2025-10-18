<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="StoreSaleRequest",
 *     title="Requête de création de vente",
 *     description="Données nécessaires pour créer une vente",
 *     required={"action_id", "quantite", "prix_par_action"},
 *     @OA\Property(
 *         property="action_id",
 *         type="integer",
 *         example=1,
 *         description="Identifiant de l'action que l'utilisateur souhaite vendre"
 *     ),
 *     @OA\Property(
 *         property="quantite",
 *         type="integer",
 *         example=10,
 *         description="Quantité d'actions à vendre"
 *     ),
 *     @OA\Property(
 *         property="prix_par_action",
 *         type="number",
 *         format="float",
 *         example=2500.75,
 *         description="Prix unitaire de l'action"
 *     ),
 *     @OA\Property(
 *         property="montant_vente",
 *         type="number",
 *         format="float",
 *         example=25007.5,
 *         description="Montant total de la vente"
 *     ),
 *     @OA\Property(
 *         property="comment",
 *         type="string",
 *         maxLength=1000,
 *         example="Vente partielle pour ajustement de portefeuille"
 *     )
 * )
 */
class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action_id' => [
                'required',
                'integer',
                Rule::exists('actions', 'id')
            ],
            'quantite' => ['required', 'integer', 'min:1'],
            'prix_par_action' => ['required', 'numeric', 'min:0.01'],
            'montant_vente' => ['required', 'numeric', 'min:0.01'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('montant_vente') && $this->has('quantite') && $this->has('prix_par_action')) {
            $this->merge([
                'montant_vente' => $this->quantite * $this->prix_par_action,
            ]);
        }
    }
}
