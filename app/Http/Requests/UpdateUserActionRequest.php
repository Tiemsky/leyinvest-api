<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="UpdateUserActionRequest",
 *     type="object",
 *
 *     @OA\Property(property="stop_loss", type="number", format="float", nullable=true, example=980.0, description="Nouvelle valeur de stop loss"),
 *     @OA\Property(property="take_profit", type="number", format="float", nullable=true, example=1250.0, description="Nouvelle valeur de take profit")
 * )
 */
class UpdateUserActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stop_loss' => ['nullable', 'numeric', 'min:0'],
            'take_profit' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'stop_loss.numeric' => 'Le stop loss doit être un nombre',
            'stop_loss.min' => 'Le stop loss doit être positif',
            'take_profit.numeric' => 'Le take profit doit être un nombre',
            'take_profit.min' => 'Le take profit doit être positif',
        ];
    }
}
