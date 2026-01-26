<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserEmailExistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Nettoyage des données avant validation
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim($this->email)),
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                // Pour PostgreSQL (case-sensitive), on garde une approche robuste :
                'exists:users,email',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.exists' => 'Aucun compte n’est associé à cette adresse e-mail.',
        ];
    }
}
