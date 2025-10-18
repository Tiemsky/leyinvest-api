<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="ChangePasswordRequest",
 *     type="object",
 *     required={"current_password","new_password","new_password_confirmation"},
 *     @OA\Property(property="current_password", type="string", format="password", example="oldPassword123", description="Mot de passe actuel"),
 *     @OA\Property(property="new_password", type="string", format="password", example="newPassword123", description="Nouveau mot de passe"),
 *     @OA\Property(property="new_password_confirmation", type="string", format="password", example="newPassword123", description="Confirmation du nouveau mot de passe")
 * )
 */
class ChangePasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'current_password' => ['required','string'],
            'new_password' => ['required','string','min:6','confirmed'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
