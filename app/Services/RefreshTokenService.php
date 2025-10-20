<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
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
        $this->accessTokenExpiration = config('sanctum.access_token_expiration', 15);
        $this->refreshTokenExpiration = config('sanctum.refresh_token_expiration', 10080);
    }

    /**
     * Créer un access token et un refresh token
     */
    public function createTokens(User $user, string $deviceName = 'api'): array
    {
        // Générer l'access token avec Sanctum
        $accessToken = $user->createToken(
            $deviceName,
            ['*'],
            now()->addMinutes($this->accessTokenExpiration)
        );

        // Générer un refresh token unique
        $refreshToken = $this->generateRefreshToken();

        // Stocker le refresh token hashed dans la base de données
        DB::table('personal_access_tokens')
            ->where('id', $accessToken->accessToken->id)
            ->update([
                'refresh_token' => Hash::make($refreshToken),
                'refresh_token_expires_at' => now()->addMinutes($this->refreshTokenExpiration),
            ]);

        return [
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->accessTokenExpiration * 60, // en secondes
            'refresh_expires_in' => $this->refreshTokenExpiration * 60, // en secondes
        ];
    }

    /**
     * Rafraîchir l'access token avec un refresh token
     */
    public function refreshToken(string $refreshToken): array
    {
        // Récupérer tous les tokens non expirés
        $tokens = PersonalAccessToken::whereNotNull('refresh_token')
            ->where('refresh_token_expires_at', '>', now())
            ->get();

        $validToken = null;

        // Vérifier le refresh token hashed
        foreach ($tokens as $token) {
            if (Hash::check($refreshToken, $token->refresh_token)) {
                $validToken = $token;
                break;
            }
        }

        if (!$validToken) {
            throw ValidationException::withMessages([
                'refresh_token' => ['Le refresh token est invalide ou expiré.'],
            ]);
        }

        // Charger l'utilisateur
        $user = User::find($validToken->tokenable_id);

        if (!$user) {
            throw ValidationException::withMessages([
                'refresh_token' => ['Utilisateur introuvable.'],
            ]);
        }

        // Révoquer l'ancien token
        $validToken->delete();

        // Créer de nouveaux tokens
        return $this->createTokens($user, $validToken->name);
    }

    /**
     * Révoquer un refresh token spécifique
     */
    public function revokeRefreshToken(string $refreshToken): bool
    {
        $tokens = PersonalAccessToken::whereNotNull('refresh_token')->get();

        foreach ($tokens as $token) {
            if (Hash::check($refreshToken, $token->refresh_token)) {
                $token->delete();
                return true;
            }
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
     * Nettoyer les refresh tokens expirés
     */
    public function cleanExpiredTokens(): int
    {
        return PersonalAccessToken::where('refresh_token_expires_at', '<', now())
            ->orWhere('expires_at', '<', now())
            ->delete();
    }

    /**
     * Générer un refresh token aléatoire sécurisé
     */
    private function generateRefreshToken(): string
    {
        return Str::random(64);
    }

    /**
     * Vérifier si un refresh token est valide (sans le révoquer)
     */
    public function validateRefreshToken(string $refreshToken): bool
    {
        $tokens = PersonalAccessToken::whereNotNull('refresh_token')
            ->where('refresh_token_expires_at', '>', now())
            ->get();

        foreach ($tokens as $token) {
            if (Hash::check($refreshToken, $token->refresh_token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtenir les informations d'un token sans le révoquer
     */
    public function getTokenInfo(string $refreshToken): ?array
    {
        $tokens = PersonalAccessToken::whereNotNull('refresh_token')
            ->where('refresh_token_expires_at', '>', now())
            ->get();

        foreach ($tokens as $token) {
            if (Hash::check($refreshToken, $token->refresh_token)) {
                return [
                    'user_id' => $token->tokenable_id,
                    'device_name' => $token->name,
                    'created_at' => $token->created_at,
                    'expires_at' => $token->refresh_token_expires_at,
                ];
            }
        }

        return null;
    }
}
