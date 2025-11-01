<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autorise uniquement les utilisateurs authentifiés
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'nom'           => ['sometimes', 'string', 'max:255'],
            'prenom'        => ['sometimes', 'string', 'max:255'],
            'country_id'    => ['sometimes', 'integer', 'max:100'],
            'numero'        => ['sometimes', 'string', 'max:20'],
            'whatsaap'      => ['sometimes', 'string', 'max:20'],
            'age'           => ['sometimes', 'integer', 'max:100'],
            'situation_professionnelle' => ['sometimes', 'string', 'max:100'],
            'avatar'        => ['sometimes', 'image', 'max:2048'],
        ];
    }
}
