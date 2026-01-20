<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CookieService;
use App\Services\GoogleAuthService;
use App\Services\RefreshTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    public function __construct(
        private GoogleAuthService $googleAuthService,
        private RefreshTokenService $refreshTokenService,
        private CookieService $cookieService
    ) {}

    /**
     * Étape 1: Redirection vers Google (Appelé par le Front)
     */
    public function login(Request $request)
    {
        try {
            // On récupère l'origine (ex: http://localhost:5173 ou https://staging.app...)
            $frontendUrl = $request->query('frontend_url', config('app.frontend_url'));

            // On délègue au service avec l'URL front stockée dans le 'state'
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
     * Étape 2: Retour de Google (Callback sur le Backend)
     */
    public function callback(Request $request)
    {
        try {
            // 1. Extraction de l'URL front depuis le state Google
            $state = $request->input('state');
            parse_str($state, $params);
            $finalFrontendUrl = $params['frontend_url'] ?? config('app.frontend_url');

            // 2. Traitement utilisateur
            $googleUser = $this->googleAuthService->getGoogleUser();
            $user = $this->googleAuthService->getOrCreateUser($googleUser);

            // 3. Génération des tokens LeyInvest
            $tokens = $this->refreshTokenService->createTokens($user, 'google_auth');

            // 4. Construction de l'URL de redirection VERS LE FRONT
            // On s'assure que le chemin correspond à ta route React
            $redirectUrl = rtrim($finalFrontendUrl, '/').'/auth/callback?token='.$tokens['access_token'];

            if (! $user->registration_completed) {
                $redirectUrl .= '&new_user=true';
            }

            // 5. Redirection externe (away) avec le cookie de session
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
     * Étape Alternative: Login via ID Token (Mobile)
     */
    public function tokenLogin(Request $request): JsonResponse
    {
        try {
            $request->validate(['token' => 'required|string']);

            $client = new \Google_Client(['client_id' => config('services.google.client_id')]);
            $payload = $client->verifyIdToken($request->token);

            if (! $payload) {
                return response()->json(['success' => false, 'message' => 'Token invalide'], 401);
            }

            // Adaptation pour réutiliser GoogleAuthService
            $googleUserData = (object) [
                'getEmail' => fn () => $payload['email'],
                'getId' => fn () => $payload['sub'],
                'getAvatar' => fn () => $payload['picture'] ?? null,
                'user' => [
                    'family_name' => $payload['family_name'] ?? '',
                    'given_name' => $payload['given_name'] ?? '',
                ],
            ];

            $user = $this->googleAuthService->getOrCreateUser($googleUserData);
            $tokens = $this->refreshTokenService->createTokens($user, 'google_mobile_auth');

            return response()->json([
                'success' => true,
                'access_token' => $tokens['access_token'],
                'user' => $user,
            ])->withCookie($this->cookieService->createRefreshTokenCookie($tokens['refresh_token']));

        } catch (\Exception $e) {
            Log::error('Mobile Login Error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Erreur auth mobile'], 500);
        }
    }
}
