<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

            $user = User::where('email', $email)->first();

            if ($user) {
                // Cas 1 : Inscription complète → Bloquer
                if ($user->registration_completed) {
                    $validator->errors()->add(
                        'email',
                        'Cet email est déjà utilisé. Veuillez vous connecter.'
                    );
                }
                // Cas 2 : Inscription incomplète → Informer l'utilisateur
                else {
                    $validator->errors()->add(
                        'email',
                        'Une inscription avec cet email est en cours. Veuillez terminer votre inscription en vérifiant votre email.'
                    );
                }
            }
            // Cas 3 : Email libre → Aucune erreur, validation OK
        });
    }
}
