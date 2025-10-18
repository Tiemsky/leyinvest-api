<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="VerifyOtpRequest",
 *     type="object",
 *     required={"email","otp"},
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         example="user@example.com",
 *         description="Adresse e-mail associée au compte à réinitialiser"
 *     ),
 *     @OA\Property(
 *         property="otp",
 *         type="string",
 *         example="458120",
 *         description="Code OTP à 6 chiffres envoyé par e-mail"
 *     )
 * )
 * */
class VerifyOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'otp' => ['required', 'string', 'size:6'],
        ];
    }
}
