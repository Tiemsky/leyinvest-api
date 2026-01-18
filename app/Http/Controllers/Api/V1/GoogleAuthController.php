<?php

namespace App\Http\Controllers\Api\V1;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Services\CookieService;
use Illuminate\Http\JsonResponse;
use App\Services\GoogleAuthService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\RefreshTokenService;
use Illuminate\Http\RedirectResponse;

/**
 * @tags Google Authentication
*/
class GoogleAuthController extends Controller
{
    protected string $frontendUrl;

    public function __construct(
        private GoogleAuthService $googleAuthService,
        private RefreshTokenService $refreshTokenService,
        private CookieService $cookieService
    ) {
        $this->frontendUrl = config('app.frontend_url', 'http://localhost:5173');
    }


    /**
    * Login via Google OAuth - Génère l'URL d'authentification
    */
    public function login(): JsonResponse
    {
    /**
     * Génère l'URL pour envoyer l'utilisateur vers Google
     */
        try {
            // Utilise Socialite en interne
            $authUrl = $this->googleAuthService->getAuthUrl();
            return response()->json([
                'success' => true,
                'message'   => 'Url genere avec success',
                'url' => $authUrl
            ]);
        } catch (\Exception $e) {
            Log::error('Google Auth URL error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur de connexion Google'], 500);
        }
    }

    /**
     * Callback après authentification Google - Gère la redirection et les tokens
     */

public function callback(Request $request)
{
    try {
        $googleUser = $this->googleAuthService->getGoogleUser();
        $user = $this->googleAuthService->getOrCreateUser($googleUser);

        // On utilise ton service de RefreshToken (SHA-256)
        $tokens = $this->refreshTokenService->createTokens($user, 'google_auth');

        // Redirection vers React avec SEULEMENT l'access_token
        $redirectUrl = config('app.frontend_url') . '/auth/callback?token=' . $tokens['access_token'];

        if (!$user->registration_completed) {
            $redirectUrl .= '&new_user=true';
        }

        // Envoi du refresh_token via Cookie HttpOnly
        return redirect()->away($redirectUrl)->withCookie(
            $this->cookieService->createRefreshTokenCookie($tokens['refresh_token'])
        );
    } catch (\Exception $e) {
        return redirect(config('app.frontend_url') . '/login?error=auth_failed');
    }
}

    /**
     * Authentification via token Google (ID Token) - Mobile/Web
     */
    public function tokenLogin(Request $request): JsonResponse
    {
        try {
            $request->validate(['token' => 'required|string']);
            $token = $request->input('token');

            $client = new \Google_Client(['client_id' => config('services.google.client_id')]);
            $payload = $client->verifyIdToken($token);

            if (!$payload) {
                return response()->json([
                    'success'   => false,
                    'error' => 'invalid_token',
                    'message' => 'Token Google invalide'
                ], 401);
            }

            $googleUserData = (object) [
                'email' => $payload['email'],
                'id' => $payload['sub'],
                'avatar' => $payload['picture'] ?? null,
                'user' => [
                    'given_name' => $payload['given_name'] ?? '',
                    'family_name' => $payload['family_name'] ?? '',
                ]
            ];

            $user = $this->googleAuthService->getOrCreateUser($googleUserData);
            $authToken = $user->createToken('google_token_auth')->plainTextToken;

            return response()->json([
                'success'   => true,
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
                'success'   => false,
                'error' => 'validation_error',
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Google Token Login Error: ' . $e->getMessage());
            return response()->json([
                'success'   => false,
                'error' => 'google_auth_failed',
                'message' => 'Erreur lors de l\'authentification Google'
            ], 500);
        }
    }
}
