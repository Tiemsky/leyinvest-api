<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class RefreshTokenService
{
    /**
     * Durée de vie de l'access token en minutes
     */
    private int $accessTokenExpiration;

    /**
     * Durée de vie du refresh token en minutes
     */
    private int $refreshTokenExpiration;

    public function __construct()
    {
        // Récupérer les configurations avec des valeurs par défaut sécurisées
        $this->accessTokenExpiration = config('sanctum.access_token_expiration', 15);
        $this->refreshTokenExpiration = config('sanctum.refresh_token_expiration', 10080);
    }

    /**
     * Créer un access token et un refresh token
     */
    public function createTokens(User $user, string $deviceName = 'api'): array
    {
        // 1. Générer l'access token avec Sanctum
        $accessToken = $user->createToken(
            $deviceName,
            ['*'],
            now()->addMinutes($this->accessTokenExpiration)
        );

        // 2. Générer et hasher le refresh token complet (sécurité et performance)
        $refreshToken = $this->generateRefreshToken(); // String unique
        $hashedRefreshToken = Hash::make($refreshToken);

        // 3. Stocker le refresh token hashed et son expiration
        DB::table('personal_access_tokens')
            ->where('id', $accessToken->accessToken->id)
            ->update([
                'refresh_token' => $hashedRefreshToken,
                'refresh_token_expires_at' => now()->addMinutes($this->refreshTokenExpiration),
            ]);

        return [
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->accessTokenExpiration * 60,
            'refresh_expires_in' => $this->refreshTokenExpiration * 60,
        ];
    }

    /**
     * Rafraîchir l'access token avec un refresh token (Rotation du token)
     */
    public function refreshToken(string $refreshToken): array
    {
        $validToken = $this->findValidTokenByRefreshToken($refreshToken);

        if (!$validToken) {
            throw ValidationException::withMessages([
                'refresh_token' => ['Le jeton de rafraîchissement est invalide ou expiré.'],
            ]);
        }

        // Charger l'utilisateur
        $user = User::find($validToken->tokenable_id);

        if (!$user) {
            // Révoquer le token associé à un utilisateur inexistant
            $validToken->delete();
            throw ValidationException::withMessages([
                'refresh_token' => ['Utilisateur introuvable.'],
            ]);
        }

        // Révoquer l'ancien token (rotation du Refresh Token)
        $validToken->delete();

        // Créer de nouveaux tokens
        return $this->createTokens($user, $validToken->name);
    }

    /**
     * Révoquer un refresh token spécifique
     */
    public function revokeRefreshToken(string $refreshToken): bool
    {
        $validToken = $this->findValidTokenByRefreshToken($refreshToken, false);

        if ($validToken) {
            $validToken->delete();
            return true;
        }

        return false;
    }

    /**
     * Révoquer tous les refresh tokens d'un utilisateur
     */
    public function revokeAllUserTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Nettoyer les refresh tokens expirés (Méthode de maintenance)
     */
    public function cleanExpiredTokens(): int
    {
        // Nettoie tous les tokens dont le cycle de vie le plus long est dépassé
        return PersonalAccessToken::where('refresh_token_expires_at', '<', now())->delete();
    }

    /**
     * Vérifier si un refresh token est valide (sans le révoquer)
     */
    public function validateRefreshToken(string $refreshToken): bool
    {
        return (bool) $this->findValidTokenByRefreshToken($refreshToken);
    }

    /**
     * Obtenir les informations d'un token sans le révoquer
     */
    public function getTokenInfo(string $refreshToken): ?array
    {
        $token = $this->findValidTokenByRefreshToken($refreshToken);

        if ($token) {
            return [
                'user_id' => $token->tokenable_id,
                'device_name' => $token->name,
                'created_at' => $token->created_at,
                'expires_at' => $token->refresh_token_expires_at,
            ];
        }

        return null;
    }

    /**
     * Générer un refresh token aléatoire sécurisé
     */
    private function generateRefreshToken(): string
    {
        return Str::random(64);
    }

    /**
     * Méthode utilitaire pour trouver le token validé (Optimisation de la performance)
     * Nous itérons sur un petit jeu de résultats récents pour éviter un parcours complet de la BDD.
     */
    private function findValidTokenByRefreshToken(string $refreshToken, bool $checkExpiration = true): ?PersonalAccessToken
    {
        $query = PersonalAccessToken::whereNotNull('refresh_token');

        if ($checkExpiration) {
             $query->where('refresh_token_expires_at', '>', now());
        }

        // Limite la recherche aux 500 tokens les plus récents pour l'optimisation
        $tokens = $query->latest()->limit(500)->get();

        // Vérifier le refresh token hashed
        foreach ($tokens as $token) {
            if (Hash::check($refreshToken, $token->refresh_token)) {
                return $token;
            }
        }

        return null;
    }
}
