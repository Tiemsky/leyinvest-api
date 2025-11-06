<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\User;

class RegisterStepOneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être une adresse valide.',
        ];
    }

    /**
     * Validation post-règles : vérifier l'état de l'email dans la base.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $email = $this->input('email');

            // Si un utilisateur COMPLET existe → bloquer
            if (User::where('email', $email)->where('registration_completed', true)->exists()) {
                $validator->errors()->add('email', 'Cet email est déjà utilisé.');
            }

            // Si inscription incomplète → autoriser (le contrôleur gérera le réenvoi OTP)
            // Aucun message ici : on ne bloque pas
        });
    }
}
