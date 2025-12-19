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
    private int $accessTokenExpiration;
    private int $refreshTokenExpiration;

    public function __construct()
    {
        $this->accessTokenExpiration = config('sanctum.access_token_expiration', 60); // 1h
        $this->refreshTokenExpiration = config('sanctum.refresh_token_expiration', 43200); // 30 jours
    }

    public function createTokens(User $user, string $deviceName = 'api'): array
    {
        // 1. Créer l'Access Token
        $accessToken = $user->createToken(
            $deviceName,
            ['*'],
            now()->addMinutes($this->accessTokenExpiration)
        );

        // 2. Générer le Refresh Token
        $refreshToken = $this->generateRefreshToken();

        // OPTIMISATION : On stocke un ID simple (Plain ID) ou un Hash rapide (SHA256)
        // pour permettre une recherche directe en BDD sans boucle foreach.
        $tokenIdentifier = hash('sha256', $refreshToken);

        DB::table('personal_access_tokens')
            ->where('id', $accessToken->accessToken->id)
            ->update([
                'refresh_token' => $tokenIdentifier, // Recherche rapide
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

    public function refreshToken(string $refreshToken): array
    {
        $token = $this->findTokenRecord($refreshToken);

        if (!$token || now()->parse($token->refresh_token_expires_at)->isPast()) {
            throw ValidationException::withMessages([
                'refresh_token' => ['Session expirée ou invalide.'],
            ]);
        }

        $user = User::find($token->tokenable_id);

        // Rotation : Supprimer l'ancien token Sanctum (Access + Refresh)
        PersonalAccessToken::find($token->id)->delete();

        return $this->createTokens($user, $token->name);
    }

    public function getTokenInfo(string $refreshToken): ?array
    {
        $token = $this->findTokenRecord($refreshToken);

        if (!$token) return null;

        return [
            'user_id' => $token->tokenable_id,
            'device_name' => $token->name,
            'expires_at' => $token->refresh_token_expires_at,
        ];
    }

    /**
     * RECHERCHE ULTRA-RAPIDE (Plus de boucle foreach)
     */
    private function findTokenRecord(string $refreshToken)
    {
        $tokenIdentifier = hash('sha256', $refreshToken);

        return DB::table('personal_access_tokens')
            ->where('refresh_token', $tokenIdentifier)
            ->first();
    }

    private function generateRefreshToken(): string
    {
        return Str::random(64);
    }

    public function revokeRefreshToken(string $refreshToken): void
    {
        $tokenIdentifier = hash('sha256', $refreshToken);
        DB::table('personal_access_tokens')->where('refresh_token', $tokenIdentifier)->delete();
    }
}
