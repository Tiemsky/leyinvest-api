<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CookieService;
use App\Services\GoogleAuthService;
use App\Services\RefreshTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function __construct(
        private GoogleAuthService $googleAuthService,
        private RefreshTokenService $refreshTokenService,
        private CookieService $cookieService
    ) {}

    /**
     * Étape 1: Redirection vers Google
     */
    public function login(Request $request)
    {
        try {
            // On capture l'URL du front qui appelle (ex: http://localhost:5173)
            $frontendUrl = $request->query('frontend_url', config('app.frontend_url'));

            // On génère l'URL avec le state
            $authUrl = $this->googleAuthService->getAuthUrl("frontend_url={$frontendUrl}");

            return response()->json([
                'success' => true,
                'url' => $authUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('Google Auth Login Error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Erreur configuration Google'], 500);
        }
    }

    /**
     * Étape 2: Retour de Google (Callback)
     */
    public function callback(Request $request)
    {
        try {
            // 1. On récupère l'URL du front d'origine depuis le 'state'
            $state = $request->input('state');
            parse_str($state, $params);
            $finalFrontendUrl = $params['frontend_url'] ?? config('app.frontend_url');

            // 2. Récupération et synchronisation de l'utilisateur
            $googleUser = $this->googleAuthService->getGoogleUser();
            $user = $this->googleAuthService->getOrCreateUser($googleUser);

            // 3. Génération des tokens sécurisés (SHA-256)
            $tokens = $this->refreshTokenService->createTokens($user, 'google_auth');

            // 4. Préparation de la redirection avec le token en paramètre URL
            $redirectUrl = rtrim($finalFrontendUrl, '/').'/auth/callback?token='.$tokens['access_token'];

            if (! $user->registration_completed) {
                $redirectUrl .= '&new_user=true';
            }

            // 5. Redirection finale avec le Refresh Token en Cookie HttpOnly
            return redirect()->away($redirectUrl)->withCookie(
                $this->cookieService->createRefreshTokenCookie($tokens['refresh_token'])
            );
        } catch (\Exception $e) {
            Log::error('Google Auth Callback Error: '.$e->getMessage());
            $errorUrl = rtrim(config('app.frontend_url'), '/').'/login?error=auth_failed';

            return redirect()->away($errorUrl);
        }
    }

    /**
     * Authentification via ID Token (Utilisé par le Mobile)
     */
    public function tokenLogin(Request $request): JsonResponse
    {
        try {
            $request->validate(['token' => 'required|string']);
            $idToken = $request->input('token');

            // 1. Vérification du token via la bibliothèque Google
            $client = new \Google_Client(['client_id' => config('services.google.client_id')]);
            $payload = $client->verifyIdToken($idToken);

            if (! $payload) {
                return response()->json(['success' => false, 'message' => 'Token Google invalide'], 401);
            }

            // 2. Transformer le payload en objet similaire à ce que Socialite renvoie
            // pour réutiliser ton service GoogleAuthService
            $googleUserData = (object) [
                'email' => $payload['email'],
                'id' => $payload['sub'],
                'user' => [
                    'family_name' => $payload['family_name'] ?? '',
                    'given_name' => $payload['given_name'] ?? '',
                ],
                'getAvatar' => fn () => $payload['picture'] ?? null,
                'getEmail' => fn () => $payload['email'],
                'getId' => fn () => $payload['sub'],
            ];

            // 3. Utiliser ton service existant (Transaction, Lock, etc.)
            $user = $this->googleAuthService->getOrCreateUser($googleUserData);

            // 4. Créer les tokens de ton système
            $tokens = $this->refreshTokenService->createTokens($user, 'google_mobile_auth');

            return response()->json([
                'success' => true,
                'access_token' => $tokens['access_token'],
                'user' => $user,
                'requires_profile_completion' => ! $user->registration_completed,
            ], 200)->withCookie(
                $this->cookieService->createRefreshTokenCookie($tokens['refresh_token'])
            );

        } catch (\Exception $e) {
            Log::error('Mobile Google Login Error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Échec de l\'authentification'], 500);
        }
    }
}
