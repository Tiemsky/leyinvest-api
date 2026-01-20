<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterStepOneRequest;
use App\Http\Requests\Auth\RegisterStepTwoRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\AuthUserResource;
use App\Services\AuthService;
use App\Services\CookieService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Authentification
 */
class AuthController extends Controller
{
    public function __construct(private AuthService $authService, private CookieService $cookieService) {}

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
     * Étape 2 : Vérifier le code OTP pour l'inscription
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
     * Étape 3 : Compléter l'inscription - Mot de passe, Pays, etc.
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
     * Étape 4 : Connexion utilisateur
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // 1. Appel au service pour la logique métier
        $result = $this->authService->login(
            $request->only('email', 'password'),
            $request->input('device_name', 'api')
        );
        // 1. On prépare la réponse JSON
        $data = $result;

        // 2. Si c'est du Web (React), on prépare le cookie ET on nettoie le JSON
        if ($request->hasHeader('Origin')) {
            $response = response()->json([
                'success' => true,
                'data' => collect($data)->except(['refresh_token', 'refresh_expires_in'])->toArray(),
            ], 200);

            // On ajoute le cookie (en utilisant la clé qui existe maintenant !)
            return $response->withCookie(
                $this->cookieService->createRefreshTokenCookie($data['refresh_token'])
            );
        }

        // 3. Si c'est Mobile, on laisse tout dans le JSON
        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * Rafraîchir l'access token
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $token = $request->cookie('refresh_token') ?? $request->input('refresh_token');

        Log::info('Refresh token attempt', [
            'has_cookie' => $request->cookie('refresh_token') ? 'yes' : 'no',
            'ip' => $request->ip(),
        ]);

        if (! $token) {
            Log::warning('No refresh token provided');

            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        try {
            $result = $this->authService->refreshToken($token);

            if ($request->hasHeader('Origin')) {
                $response = response()->json([
                    'success' => true,
                    'message' => 'Token rafraîchi avec succès',
                    'data' => collect($result)->except(['refresh_token', 'refresh_expires_in'])->toArray(),
                ]);

                return $response->withCookie(
                    $this->cookieService->createRefreshTokenCookie($result['refresh_token'])
                );
            }

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            Log::error('Refresh token failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            throw $e;
        }
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
        ], 200);
    }

    /**
     * Demander une réinitialisation de mot de passe
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->forgotPassword($request->input('email'));

        return response()->json([
            'success' => true,
            'message' => 'Un code de réinitialisation a été envoyé à votre email.',
        ]);
    }

    /**
     * Vérifier le code OTP pour la réinitialisation du mot de passe
     */
    public function verifyResetOtp(VerifyOtpRequest $request): JsonResponse
    {
        $request->validated();

        $user = $this->authService->verifyResetOtp(
            $request->input('email'),
            $request->input('otp')
        );

        return response()->json([
            'success' => true,
            'message' => 'OTP de réinitialisation vérifié avec succès. Vous pouvez maintenant réinitialiser votre mot de passe.',
            'data' => [
                'user' => new AuthUserResource($user),
                'next_step' => 'reset_password',
            ],
        ], 200);
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $user = $this->authService->resetPassword(
            $request->input('email'),
            $request->input('password'),
            $request->input('password_confirmation')
        );

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès.',
            'data' => ['user' => new AuthUserResource($user)],
        ], 200);
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request): JsonResponse
    {
        // On récupère le token pour le révoquer aussi en BDD
        $token = $request->cookie('refresh_token') ?? $request->input('refresh_token');
        $this->authService->logout($request->user(), $token);

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie.',
        ])->withoutCookie('refresh_token');
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
        ])->withoutCookie('refresh_token');
    }

    /**
     * Obtenir l'utilisateur authentifié
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ['user' => new AuthUserResource($request->user())],
        ]);
    }

    /**
     * Changer le mot de passe utilisateur
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->authService->changePassword(
            $request->user(),
            $request->input('current_password'),
            $request->input('new_password')
        );

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe changé avec succès.',
        ], 200);
    }

    /**
     * Mettre à jour le profil utilisateur
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'nom' => ['sometimes', 'string', 'max:255'],
            'prenom' => ['sometimes', 'string', 'max:255'],
            'country_id' => ['sometimes', 'integer', 'max:100'],
            'numero' => ['sometimes', 'string', 'max:20'],
            'whatsaap' => ['sometimes', 'string', 'max:20'],
            'age' => ['sometimes', 'integer', 'max:100'],
            'situation_professionnelle' => ['sometimes', 'string', 'max:100'],
            'avatar' => ['sometimes', 'image', 'max:2048'], // Max 2MB
        ]);

        $user = $request->user();
        // Update Profile
        $this->authService->updateProfile($user, $request->only(['nom', 'prenomss', 'phone', 'country']));

        // // Update Avatar
        // if ($request->hasFile('avatar')) {
        //     $this->authService->updateAvatar($user, $request->file('avatar'));
        // }

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès.',
            'data' => ['user' => new AuthUserResource($user->fresh())],
        ]);
    }

    /**  * Mettre à jour ou supprimer l'avatar de l'utilisateur
     */
    public function manageAvatar(Request $request): void
    {
        $user = $request->user();
        // Update Avatar
        if ($request->hasFile('avatar')) {
            $this->authService->updateAvatar($user, $request->file('avatar'));

            return;
        }
    }

    /**
     * Supprimer le compte utilisateur
     */
    public function deleteUser(Request $request): JsonResponse
    {
        $this->authService->deleteUser($request->user());
        // On supprime aussi le cookie lors de la suppression du compte
        $cookie = $this->cookieService->forgetRefreshTokenCookie();

        return response()->json([
            'success' => true,
            'message' => 'Compte utilisateur supprimé avec succès.',
        ], 200)->withCookie($cookie);
    }
}
