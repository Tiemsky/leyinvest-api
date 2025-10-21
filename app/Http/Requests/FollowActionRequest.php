<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="FollowActionRequest",
 *     type="object",
 *     required={"action_id"},
 *     @OA\Property(property="action_id", type="integer", example=1, description="ID de l'action à suivre"),
 *     @OA\Property(property="stop_loss", type="number", format="float", nullable=true, example=950.5, description="Valeur optionnelle de stop loss"),
 *     @OA\Property(property="take_profit", type="number", format="float", nullable=true, example=1200.0, description="Valeur optionnelle de take profit")
 * )
 */

class FollowActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action_id' => ['required', 'integer', 'exists:users,id'],
            'stop_loss' => ['nullable', 'numeric', 'min:0'],
            'take_profit' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'action_id.required' => 'L\'ID de l\'action est requis',
            'action_id.exists' => 'Cette action n\'existe pas',
            'stop_loss.numeric' => 'Le stop loss doit être un nombre',
            'stop_loss.min' => 'Le stop loss doit être positif',
            'take_profit.numeric' => 'Le take profit doit être un nombre',
            'take_profit.min' => 'Le take profit doit être positif',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => auth()->id(),
        ]);
    }
}
