<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\{
    RegisterStepOneRequest,
    RegisterStepTwoRequest,
    LoginRequest,
    VerifyOtpRequest,
    ForgotPasswordRequest,
    ResetPasswordRequest
};
use App\Http\Resources\AuthUserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Étape 1 : Inscription - Nom, Prénom, Email
     */
    public function registerStepOne(RegisterStepOneRequest $request): JsonResponse
    {
        $user = $this->authService->registerStepOne($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Inscription initiée. Un code de vérification a été envoyé à votre email.',
            'data' => [
                'user' => new AuthUserResource($user),
                'next_step' => 'verify_otp',
            ],
        ], 201);
    }

    /**
     * Vérifier le code OTP pour l'inscription
     */
    public function verifyRegistrationOtp(VerifyOtpRequest $request): JsonResponse
    {
        $request->validated();
        $user = $this->authService->verifyRegistrationOtp(
            $request->input('email'),
            $request->input('otp')
        );

        return response()->json([
            'success' => true,
            'message' => 'Email vérifié avec succès. Veuillez compléter votre inscription.',
            'data' => [
                'user' => new AuthUserResource($user),
                'next_step' => 'complete_registration',
            ],
        ]);
    }

    /**
     * Étape 2 : Compléter l'inscription - Mot de passe, Pays, etc.
     */
    public function registerStepTwo(RegisterStepTwoRequest $request): JsonResponse
    {
        $user = $this->authService->registerStepTwo($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Inscription complétée avec succès. Vous pouvez maintenant vous connecter.',
            'data' => [
                'user' => new AuthUserResource($user),
            ],
        ], 201);
    }

    /**
     * Connexion utilisateur
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated(),
            $request->input('device_name', 'api')
        );

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie.',
            'data' => [
                'user' => new AuthUserResource($result['user']),
                'token' => $result['token'],
            ],
        ]);
    }

    /**
     * Renvoyer le code OTP
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $this->authService->resendOtp($request->input('email'));

        return response()->json([
            'success' => true,
            'message' => 'Un nouveau code de vérification a été envoyé à votre email.',
        ]);
    }

    /**
     * Demander la réinitialisation du mot de passe
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->forgotPassword($request->input('email'));

        return response()->json([
            'success' => true,
            'message' => 'Un code de réinitialisation a été envoyé à votre email.',
        ]);
    }
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $user = $this->authService->resetPassword(
            $request->input('email'),
            $request->input('otp'),
            $request->input('password')
        );

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès.',
            'data' => [
                'user' => new AuthUserResource($user),
            ],
        ]);
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie.',
        ]);
    }

    /**
     * Déconnexion de tous les appareils
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAll($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion de tous les appareils réussie.',
        ]);
    }

    /**
     * Obtenir l'utilisateur authentifié
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => new AuthUserResource($request->user()),
            ],
        ]);
    }
}
