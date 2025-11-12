<?php

namespace App\Http\Controllers\Api\V1;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\GoogleAuthService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class GoogleAuthController extends Controller
{
    public function __construct(
        private GoogleAuthService $googleAuthService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/auth/google/login",
     *     tags={"Google Authentication"},
     *     summary="Initier la connexion Google",
     *     description="Retourne l'URL d'authentification Google à laquelle le frontend doit rediriger l'utilisateur.",
     *     @OA\Response(
     *         response=200,
     *         description="URL générée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="url", type="string", example="https://accounts.google.com/o/oauth2/auth?..."),
     *             @OA\Property(property="message", type="string", example="Redirect to this URL for Google authentication")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur lors de la génération de l'URL Google",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="google_login_failed"),
     *             @OA\Property(property="message", type="string", example="Impossible de générer l'URL d'authentification Google")
     *         )
     *     )
     * )
     */
    public function login(): JsonResponse
    {
        try {
            $authUrl = $this->googleAuthService->getAuthUrl();

            return response()->json([
                'success' => true,
                'url' => $authUrl,
                'message' => 'Redirect to this URL for Google authentication'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Google Login Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'google_login_failed',
                'message' => 'Impossible de générer l\'URL d\'authentification Google'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/google/callback",
     *     tags={"Google Authentication"},
     *     summary="Callback Google OAuth",
     *     description="Endpoint appelé par Google après autorisation OAuth. Gère la création/connexion de l'utilisateur et retourne un token Sanctum.",
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         required=true,
     *         description="Code d'autorisation OAuth renvoyé par Google",
     *         @OA\Schema(type="string", example="4/0AbCdeF...")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion Google réussie",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="access_token", type="string", example="1|YvX9aT7X..."),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="redirect_url", type="string", example="https://frontend.com/dashboard?token=1|YvX9aT7X..."),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=12),
     *                 @OA\Property(property="email", type="string", example="user@gmail.com"),
     *                 @OA\Property(property="nom", type="string", example="Kouadio"),
     *                 @OA\Property(property="prenom", type="string", example="Yao"),
     *                 @OA\Property(property="email_verified", type="boolean", example=true),
     *                 @OA\Property(property="avatar", type="string", example="https://lh3.googleusercontent.com/photo.jpg"),
     *                 @OA\Property(property="role", type="string", example="user")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Code d'autorisation manquant ou session expirée",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="missing_code"),
     *             @OA\Property(property="message", type="string", example="Code d'autorisation manquant")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne Google OAuth",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="google_auth_failed"),
     *             @OA\Property(property="message", type="string", example="Erreur lors du traitement du callback Google")
     *         )
     *     )
     * )
     */
    public function callback(Request $request): JsonResponse|RedirectResponse
    {
        try {
            if (!$request->has('code')) {
                return response()->json(['error' => 'missing_code', 'message' => 'Code manquant'], 400);
            }

            $googleUser = $this->googleAuthService->getGoogleUser();
            $user = $this->googleAuthService->getOrCreateUser($googleUser);

            $token = $user->createToken('google_auth_token')->plainTextToken;

            // 🔥 Détecter dynamiquement l'URL frontend
            $origin = $request->headers->get('Origin') ?: rtrim(config('app.frontend_url'), '/');
            $allowed = config('app.allowed_frontend_urls', []);

            if (!empty($allowed) && !in_array($origin, $allowed)) {
                Log::warning("Unauthorized origin: {$origin}");
                return response()->json(['error' => 'unauthorized_origin'], 403);
            }

            // Construire l'URL de redirection
            $redirectPath = $user->registration_completed ? '/user/dashboard' : '/auth/complete-profile';
            $queryParams = $user->registration_completed
                ? "token={$token}"
                : "token={$token}&email=" . urlencode($user->email);

            $redirectUrl = "{$origin}{$redirectPath}?{$queryParams}";


            return redirect()->away($redirectUrl);

            // return response()->json([
            //     'success'   => true,
            //     'access_token' => $token,
            //     'token_type' => 'Bearer',
            //     'redirect_url' => $redirectUrl,
            //     'user' => [
            //         'id' => $user->id,
            //         'email' => $user->email,
            //         'nom' => $user->nom,
            //         'prenom' => $user->prenom,
            //         'email_verified' => $user->email_verified,
            //         'avatar' => $user->avatar,
            //         'role' => $user->role,
            //         'registration_completed' => $user->registration_completed,
            //     ],
            // ]);
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            Log::error('Invalid state', ['exception' => $e]);
            return response()->json([
                'success' => false,
                'error' => 'invalid_state',
                'message' => 'Session expirée'],
                400);
        } catch (\Exception $e) {
            Log::error('Google callback error', ['exception' => $e]);
            return response()->json([
                'success' => false,
                'error' => 'google_auth_failed',
                'message' => 'Erreur interne'
            ], 500);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/v1/auth/google/token",
     *     tags={"Google Authentication"},
     *     summary="Connexion via Google ID Token (pour mobile / SPA)",
     *     description="Permet la connexion directe à l'aide d'un token ID Google. Utilisée pour les apps mobiles et SPA.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Token ID fourni par Google après authentification sur le client",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"token"},
     *             @OA\Property(property="token", type="string", example="eyJhbGciOiJSUzI1NiIsImtpZCI6IjUxYz...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Authentification réussie avec Google Token",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="access_token", type="string", example="1|Xf9aKj7t..."),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=8),
     *                 @OA\Property(property="email", type="string", example="user@gmail.com"),
     *                 @OA\Property(property="nom", type="string", example="Konan"),
     *                 @OA\Property(property="prenom", type="string", example="Eric"),
     *                 @OA\Property(property="email_verified", type="boolean", example=false),
     *                 @OA\Property(property="avatar", type="string", example="https://lh3.googleusercontent.com/..."),
     *             ),
     *             @OA\Property(property="requires_profile_completion", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token Google invalide ou expiré",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="invalid_token"),
     *             @OA\Property(property="message", type="string", example="Token Google invalide")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="validation_error"),
     *             @OA\Property(property="message", type="string", example="Le champ token est requis.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="google_auth_failed"),
     *             @OA\Property(property="message", type="string", example="Erreur lors de l'authentification Google")
     *         )
     *     )
     * )
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
