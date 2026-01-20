<?php

namespace App\Services;

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class RefreshTokenService
{
    private int $accessTokenExpiration;

    private int $refreshTokenExpiration;

    public function __construct()
    {
        $this->accessTokenExpiration = config('sanctum.access_token_expiration', 60);
        $this->refreshTokenExpiration = config('sanctum.refresh_token_expiration', 43200);
    }

    public function createTokens(User $user, string $deviceName = 'api'): array
    {
        // 1. Créer l'Access Token avec expiration courte
        $accessToken = $user->createToken(
            $deviceName,
            ['*'],
            now()->addMinutes($this->accessTokenExpiration)
        );

        // 2. Générer le Refresh Token sécurisé
        $refreshToken = $this->generateRefreshToken();
        $tokenIdentifier = hash('sha256', $refreshToken);

        // 3. Stocker le hash en BDD avec metadata de sécurité
        DB::table('personal_access_tokens')
            ->where('id', $accessToken->accessToken->id)
            ->update([
                'refresh_token' => $tokenIdentifier,
                'refresh_token_expires_at' => now()->addMinutes($this->refreshTokenExpiration),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
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
        // Rate limiting: Max 10 refresh par minute par IP
        $rateLimitKey = 'refresh_token:'.request()->ip();
        if (Cache::get($rateLimitKey, 0) > 10) {
            throw ValidationException::withMessages([
                'refresh_token' => ['Trop de tentatives. Réessayez dans 1 minute.'],
            ]);
        }
        Cache::increment($rateLimitKey);
        Cache::put($rateLimitKey, Cache::get($rateLimitKey), now()->addMinute());

        $token = $this->findTokenRecord($refreshToken);

        if (! $token || now()->parse($token->refresh_token_expires_at)->isPast()) {
            throw ValidationException::withMessages([
                'refresh_token' => ['Session expirée ou invalide.'],
            ]);
        }

        // VALIDATION DE SÉCURITÉ: Vérifier IP et User-Agent
        if ($token->ip_address !== request()->ip()) {
            \Log::warning('IP mismatch during token refresh', [
                'user_id' => $token->tokenable_id,
                'original_ip' => $token->ip_address,
                'current_ip' => request()->ip(),
            ]);
            // Optionnel: bloquer ou demander re-authentification
        }

        $user = User::find($token->tokenable_id);

        // ROTATION OBLIGATOIRE: Invalider l'ancien token
        PersonalAccessToken::find($token->id)->delete();

        return $this->createTokens($user, $token->name);
    }

    public function revokeRefreshToken(string $refreshToken): void
    {
        $tokenIdentifier = hash('sha256', $refreshToken);
        DB::table('personal_access_tokens')
            ->where('refresh_token', $tokenIdentifier)
            ->delete();
    }

    public function revokeAllUserTokens(User $user): void
    {
        DB::table('personal_access_tokens')
            ->where('tokenable_id', $user->id)
            ->where('tokenable_type', get_class($user))
            ->delete();
    }

    private function findTokenRecord(string $refreshToken)
    {
        $tokenIdentifier = hash('sha256', $refreshToken);

        return DB::table('personal_access_tokens')
            ->where('refresh_token', $tokenIdentifier)
            ->first();
    }

    private function generateRefreshToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
