<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Notifications\SendOtpNotification;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;

class AuthService
{
    public function __construct(protected RefreshTokenService $refreshTokenService) {}

    /**
     * Étape 1 : Enregistrer ou reprendre une inscription incomplète et envoyer l'OTP
     */
    public function registerStepOne(array $data): array
    {
        $email = $data['email'];

        // Vérifie si une inscription incomplète existe
        $user = User::where('email', $email)
            ->where('registration_completed', false)
            ->first();

        if ($user) {
            // Réutiliser l'utilisateur existant → régénérer OTP
            $otp = $user->generateOtpCode();
            $user->notify(new SendOtpNotification($otp, 'verification'));
            Log::info("OTP resent for incomplete registration to {$user->email}: {$otp}");

            return [
                'user' => $user,
                'message' => 'Vous avez déjà commencé une inscription. Un nouveau code OTP a été envoyé.',
            ];
        }

        // Sinon, créer un nouvel utilisateur
        $user = User::create([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $email,
            'email_verified' => false,
            'registration_completed' => false,
        ]);

        $otp = $user->generateOtpCode();
        $user->notify(new SendOtpNotification($otp, 'verification'));
        Log::info("New OTP for registration sent to {$user->email}: {$otp}");

        return [
            'user' => $user,
            'message' => 'Code OTP envoyé. Veuillez vérifier votre email.',
        ];
    }

    /**
     * Vérifier le code OTP pour l'inscription
     */
    public function verifyRegistrationOtp(string $email, string $otp): User
    {
        $user = User::where('email', $email)
            ->where('registration_completed', false)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Aucune inscription en cours trouvée pour cet email.'],
            ]);
        }

        if ($user->email_verified) {
            throw ValidationException::withMessages([
                'otp' => ['Cet email est déjà vérifié. Veuillez compléter votre inscription.'],
            ]);
        }

        if ($user->isOtpExpired()) {
            throw ValidationException::withMessages([
                'otp' => ['Le code OTP a expiré. Veuillez demander un nouveau code.'],
            ]);
        }

        if (!$user->verifyOtpCode($otp)) {
            throw ValidationException::withMessages([
                'otp' => ['Le code OTP est invalide.'],
            ]);
        }

        // Marquer email comme vérifié
        $user->update(['email_verified' => true]);
        return $user->fresh();
    }

    /**
     * Étape 2 : Compléter l'inscription
     */
    public function registerStepTwo(array $data): User
    {
        $user = User::where('email', $data['email'])
            ->where('email_verified', true)
            ->where('registration_completed', false)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Aucune inscription en attente de complétion pour cet email.'],
            ]);
        }

        DB::beginTransaction();
        try {
            $user->completeRegistration([
                'password' => $data['password'],
                'country_id' => $data['country_id'],
                'numero' => $data['numero'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'age' => $data['age'] ?? null,
                'genre' => $data['genre'] ?? null,
                'situation_professionnelle' => $data['situation_professionnelle'] ?? null,
            ]);

            DB::commit();
            return $user->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Connexion
     */
    public function login(array $credentials, string $deviceName = 'api'): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (
            !$user ||
            !$user->registration_completed ||
            !$user->email_verified ||
            !Hash::check($credentials['password'], $user->password)
        ) {
            // Uniformiser le message pour éviter fuite d'info
            throw ValidationException::withMessages([
                'email' => ['Ces informations d\'identification ne correspondent pas à nos enregistrements.'],
            ]);
        }

        $tokens = $this->refreshTokenService->createTokens($user, $deviceName);

        return [
            'user' => $user,
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => $tokens['token_type'],
            'expires_in' => $tokens['expires_in'],
            'refresh_expires_in' => $tokens['refresh_expires_in'],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $tokens = $this->refreshTokenService->refreshToken($refreshToken);
        $tokenInfo = $this->refreshTokenService->getTokenInfo($refreshToken);
        $user = User::find($tokenInfo['user_id']);

        if (!$user) {
            throw ValidationException::withMessages(['token' => ['Utilisateur introuvable.']]);
        }

        return [
            'user' => $user,
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => $tokens['token_type'],
            'expires_in' => $tokens['expires_in'],
            'refresh_expires_in' => $tokens['refresh_expires_in'],
        ];
    }

    public function resendOtp(string $email): void
    {
        $user = User::where('email', $email)
            ->where('registration_completed', false)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Aucune inscription en cours trouvée pour cet email.'],
            ]);
        }

        if ($user->email_verified && $user->registration_completed) {
            throw ValidationException::withMessages([
                'email' => ['Ce compte est déjà actif.'],
            ]);
        }
        $otp = $user->generateOtpCode();
        $user->notify(new SendOtpNotification($otp, 'verification'));
        Log::info("OTP resent to {$user->email}");
    }

    public function forgotPassword(string $email): void
    {
        $user = User::where('email', $email)
            ->where('registration_completed', true)
            ->where('email_verified', true)
            ->first();

        if (!$user) {
            // Ne pas révéler si l'email existe ou non → sécurité
            return;
        }
        $otp = $user->generateOtpCode();
        $user->notify(new SendOtpNotification($otp, 'reset'));
    }

    public function verifyResetOtp(string $email, string $otp): User
    {
        $user = User::where('email', $email)
            ->where('registration_completed', true)
            ->where('email_verified', true)
            ->first();

        if (!$user || $user->isOtpExpired() || !$user->verifyOtpCode($otp)) {
            throw ValidationException::withMessages([
                'otp' => ['Le code OTP est invalide ou a expiré.'],
            ]);
        }
        // Nettoyer OTP après vérification réussie
        $user->update(['otp_code' => null, 'otp_expires_at' => null]);

        return $user;
    }

    public function resetPassword(string $email, string $password, string $passwordConfirmation): User
    {
        if ($password !== $passwordConfirmation) {
            throw ValidationException::withMessages([
                'password_confirmation' => ['Les mots de passe ne correspondent pas.'],
            ]);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Utilisateur non trouvé.'],
            ]);
        }
        $user->update(['password' => Hash::make($password)]);
        $user->tokens()->delete(); // Révoquer tous les tokens
        return $user;
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): User
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        if (Hash::check($newPassword, $user->password)) {
            throw ValidationException::withMessages([
                'new_password' => ['Le nouveau mot de passe doit être différent de l\'ancien.'],
            ]);
        }

        $user->update(['password' => Hash::make($newPassword)]);
        return $user->fresh();
    }

    public function updateProfile(User $user, array $data): User
    {
        $allowed = ['nom', 'prenom', 'numero', 'country_id', 'whatsapp', 'age', 'situation_professionnelle'];
        $update = array_intersect_key($data, array_flip($allowed));

        if (empty($update)) {
            throw ValidationException::withMessages([
                'data' => ['Aucune donnée valide à mettre à jour.'],
            ]);
        }

        $user->update($update);
        return $user->fresh();
    }

    public function updateAvatar(User $user, UploadedFile $avatar): User
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $avatar->store('avatars', 'public');
        $user->update(['avatar' => $path]);
        Log::info("Avatar updated for {$user->email}");
        return $user->fresh();
    }

    public function deleteAvatar(User $user): User
    {
        if (!$user->avatar) {
            throw ValidationException::withMessages([
                'avatar' => ['Aucun avatar à supprimer.'],
            ]);
        }

        Storage::disk('public')->delete($user->avatar);
        $user->update(['avatar' => null]);
        Log::info("Avatar deleted for {$user->email}");
        return $user->fresh();
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function logoutAll(User $user): void
    {
        $this->refreshTokenService->revokeAllUserTokens($user);
    }

    public function deleteUser(User $user): void
    {
        DB::beginTransaction();
        try {
            $this->refreshTokenService->revokeAllUserTokens($user);

            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->delete();
            DB::commit();
            Log::info("User {$user->email} deleted.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete user {$user->email}: " . $e->getMessage());
            throw $e;
        }
    }
}
