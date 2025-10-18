<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterStepTwoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'         => ['required', 'email', 'exists:users,email'],
            'password'      => ['required', Password::defaults()],
            'country_id'    => ['nullable', 'integer', 'max:255'],
            'numero'        => ['nullable', 'string', 'max:20'],
            'whatsapp'      => ['nullable', 'string', 'max:20'],
            'age'           => ['nullable', 'integer', 'min:16'],
            'situation_professionnelle' => ['nullable', 'string'],
            'genre' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'L\'email est obligatoire.',
            'email.exists' => 'Aucun compte trouvé avec cet email.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'country.required' => 'Le pays est obligatoire.',
        ];
    }
}
