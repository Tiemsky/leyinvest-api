<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="ResendOtpRequest",
 *     type="object",
 *     required={"email"},
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         example="jean@example.com",
 *         description="Email de l'utilisateur pour renvoyer le code OTP"
 *     )
 * )
 */
class ResendOtpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
