<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => [
                'required',
                'integer',
                Rule::exists('wallets', 'id')->where(function ($query) {
                    return $query->where('user_id', $this->user()->id);
                }),
            ],
            'quantite' => ['required', 'integer', 'min:1'],
            'prix_par_action' => ['required', 'numeric', 'min:0.01'],
            'montant_achat' => ['nullable', 'numeric', 'min:0.01'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'wallet_id.required' => 'Le portefeuille est requis.',
            'wallet_id.exists' => 'Le portefeuille sélectionné n\'existe pas ou ne vous appartient pas.',
            'quantite.required' => 'La quantité est requise.',
            'quantite.min' => 'La quantité doit être au minimum 1.',
            'prix_par_action.required' => 'Le prix par action est requis.',
            'prix_par_action.min' => 'Le prix par action doit être positif.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Calculer automatiquement le montant_achat si non fourni
        if (!$this->has('montant_achat') && $this->has('quantite') && $this->has('prix_par_action')) {
            $this->merge([
                'montant_achat' => $this->quantite * $this->prix_par_action,
            ]);
        }
    }
}
