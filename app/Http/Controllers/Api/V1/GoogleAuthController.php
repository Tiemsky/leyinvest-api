<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\GoogleAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    public function __construct(
        private GoogleAuthService $googleAuthService
    ) {}

    /**
     * 🔵 Initier la connexion Google
     *
     * GET /api/auth/google/login
     * Redirige vers la page de connexion Google
     */
    public function login(): JsonResponse
    {
        try {
            $authUrl = $this->googleAuthService->getAuthUrl();

            return response()->json([
                'url' => $authUrl,
                'message' => 'Redirect to this URL for Google authentication'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Google Login Error: ' . $e->getMessage());

            return response()->json([
                'error' => 'google_login_failed',
                'message' => 'Impossible de générer l\'URL d\'authentification Google'
            ], 500);
        }
    }

    /**
     * 🔵 Callback Google OAuth
     *
     * GET /api/auth/google/callback?code=xxx
     * Endpoint de callback après autorisation Google
     */
    public function callback(Request $request): JsonResponse
    {
        try {
            // Vérifier la présence du code
            if (!$request->has('code')) {
                return response()->json([
                    'error' => 'missing_code',
                    'message' => 'Code d\'autorisation manquant'
                ], 400);
            }

            // Récupérer l'utilisateur Google via Socialite
            $googleUser = $this->googleAuthService->getGoogleUser();

            // Créer ou récupérer l'utilisateur
            $user = $this->googleAuthService->getOrCreateUser($googleUser);

            // Générer un token Sanctum
            $token = $user->createToken('google_auth_token')->plainTextToken;

            // Déterminer l'URL de redirection
            $frontendUrl = config('services.frontend.url');
            $redirectUrl = $user->email_verified
                ? "{$frontendUrl}/dashboard?token={$token}"
                : "{$frontendUrl}/complete-profile?token={$token}&email={$user->email}";

            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'redirect_url' => $redirectUrl,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'email_verified' => $user->email_verified,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                ]
            ], 200);

        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            Log::error('Google Auth - Invalid State: ' . $e->getMessage());

            return response()->json([
                'error' => 'invalid_state',
                'message' => 'Session expirée ou invalide. Veuillez réessayer.'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Google Callback Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'google_auth_failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔵 Connexion avec Google ID Token (pour Mobile/SPA)
     *
     * POST /api/auth/google/token
     * Authentification avec un token ID Google direct
     */
    public function tokenLogin(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'token' => 'required|string'
            ]);

            $token = $request->input('token');

            // Vérifier le token avec Google
            $client = new \Google_Client(['client_id' => config('services.google.client_id')]);
            $payload = $client->verifyIdToken($token);

            if (!$payload) {
                return response()->json([
                    'error' => 'invalid_token',
                    'message' => 'Token Google invalide'
                ], 401);
            }

            // Créer un objet similaire à Socialite User
            $googleUserData = (object) [
                'email' => $payload['email'],
                'id' => $payload['sub'],
                'avatar' => $payload['picture'] ?? null,
                'user' => [
                    'given_name' => $payload['given_name'] ?? '',
                    'family_name' => $payload['family_name'] ?? '',
                ]
            ];

            // Créer ou récupérer l'utilisateur
            $user = $this->googleAuthService->getOrCreateUser($googleUserData);

            // Générer un token Sanctum
            $authToken = $user->createToken('google_token_auth')->plainTextToken;

            return response()->json([
                'access_token' => $authToken,
                'token_type' => 'bearer',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'email_verified' => $user->email_verified,
                    'avatar' => $user->avatar,
                ],
                'requires_profile_completion' => !$user->email_verified
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'validation_error',
                'message' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Google Token Login Error: ' . $e->getMessage());

            return response()->json([
                'error' => 'google_auth_failed',
                'message' => 'Erreur lors de l\'authentification Google'
            ], 500);
        }
    }
}
