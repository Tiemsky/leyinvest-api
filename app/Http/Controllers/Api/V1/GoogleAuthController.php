<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CookieService;
use App\Services\GoogleAuthService;
use App\Services\RefreshTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly GoogleAuthService $googleAuthService,
        private readonly RefreshTokenService $refreshTokenService,
        private readonly CookieService $cookieService
    ) {}

    /**
     * Étape 1: Redirection vers Google
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Validation de l'URL front pour éviter les redirections malveillantes
            $frontendUrl = $request->query('frontend_url');
            if ($frontendUrl && ! $this->isValidRedirectUrl($frontendUrl)) {
                return response()->json(['message' => 'URL de redirection non autorisée'], 400);
            }

            $targetUrl = $frontendUrl ?? config('app.frontend_url');
            $authUrl = $this->googleAuthService->getAuthUrl("frontend_url={$targetUrl}");

            return response()->json([
                'success' => true,
                'url' => $authUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('Google Auth Login Error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Configuration Google invalide'], 500);
        }
    }

    /**
     * Étape 2: Callback Web
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            // 1. Extraction et validation du state
            parse_str($request->input('state', ''), $params);
            $frontendUrl = $params['frontend_url'] ?? config('app.frontend_url');

            if (! $this->isValidRedirectUrl($frontendUrl)) {
                $frontendUrl = config('app.frontend_url');
            }

            // 2. Récupération utilisateur via Socialite
            $googleUser = $this->googleAuthService->handleCallback(); // Utilise la méthode handleCallback optimisée plus tôt

            // 3. Génération des tokens et redirection
            return $this->processUserAndRedirect($googleUser, $frontendUrl);

        } catch (\Exception $e) {
            Log::error('Google Auth Callback Error: '.$e->getMessage());
            $errorUrl = rtrim(config('app.frontend_url'), '/').'/login?error=auth_failed';

            return redirect()->away($errorUrl);
        }
    }

    /**
     * Étape Alternative: Mobile/Token Login
     */
    public function tokenLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $client = new \Google_Client(['client_id' => config('services.google.client_id')]);
            $payload = $client->verifyIdToken($request->token);

            if (! $payload) {
                return response()->json(['success' => false, 'message' => 'Token Google invalide'], 401);
            }

            // Normalisation des données pour le service
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
                'requires_completion' => ! $user->registration_completed,
            ])->withCookie($this->cookieService->createRefreshTokenCookie($tokens['refresh_token']));

        } catch (\Exception $e) {
            Log::error('Mobile Login Error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Erreur lors de l’authentification mobile'], 500);
        }
    }

    /**
     * Logique commune de génération de réponse de redirection (DRY)
     */
    private function processUserAndRedirect($user, string $frontendUrl): RedirectResponse
    {
        $tokens = $this->refreshTokenService->createTokens($user, 'google_auth');

        // On utilise l'URL fragment (#) ou query (?) selon tes besoins React
        // Le fragment (#) est souvent plus sécurisé car non envoyé au serveur par le navigateur
        $separator = str_contains($frontendUrl, '?') ? '&' : '?';
        $redirectUrl = rtrim($frontendUrl, '/').'/auth/callback'.$separator.'token='.$tokens['access_token'];

        if (! $user->registration_completed) {
            $redirectUrl .= '&new_user=true';
        }

        return redirect()->away($redirectUrl)->withCookie(
            $this->cookieService->createRefreshTokenCookie($tokens['refresh_token'])
        );
    }

    /**
     * Vérifie que l'URL de redirection appartient à tes domaines autorisés
     */
    private function isValidRedirectUrl(string $url): bool
    {
        $allowedDomains = [
            parse_url(config('app.url'), PHP_URL_HOST),
            parse_url(config('app.frontend_url'), PHP_URL_HOST),
            'localhost', // Pour le dev
        ];

        $host = parse_url($url, PHP_URL_HOST);

        return in_array($host, $allowedDomains);
    }
}
