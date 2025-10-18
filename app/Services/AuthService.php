<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Notifications\SendOtpNotification;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Étape 1 : Enregistrer les informations de base et envoyer l'OTP
     */
    public function registerStepOne(array $data): User
    {
        $user = User::create([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'email_verified' => false,
            'registration_completed' => false,
        ]);

        $otp = $user->generateOtpCode();
        $user->notify(new SendOtpNotification($otp, 'verification'));
        Log::info("OTP for registration sent to {$user->email}: {$otp}");
        return $user;
    }

    /**
     * Vérifier le code OTP pour l'inscription
     */
    public function verifyRegistrationOtp(string $email, string $otp): User
    {
        $user = User::where('email', $email)->firstOrFail();

        if ($user->email_verified) {
            throw ValidationException::withMessages([
                'otp' => ['Cet email est déjà vérifié. Veuillez veuillez complétez votre inscription.'],
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

        return $user;
    }

    /**
     * Étape 2 : Compléter l'inscription avec mot de passe et informations complémentaires
     */
    public function registerStepTwo(array $data): User
    {
        $user = User::where('email', $data['email'])->firstOrFail();

        if (!$user->email_verified) {
            throw ValidationException::withMessages([
                'email' => ['Veuillez d\'abord vérifier votre email avec le code OTP.'],
            ]);
        }

        if ($user->registration_completed) {
            throw ValidationException::withMessages([
                'email' => ['L\'inscription pour ce compte est déjà complétée.'],
            ]);
        }

        DB::beginTransaction();

        try {
            // Compléter l'inscription
            $user->completeRegistration([
                'password' => $data['password'],
                'country_id' => $data['country_id'],
                'numero' => $data['numero'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'age' => $data['age'] ?? null,
                'genre' => $data['genre'] ?? null,
                'situation_professionnelle' => $data['situation_professionnelle'] ?? null,
            ]);

            // Créer automatiquement le wallet
            Wallet::create([ 'user_id' => $user->id,]);

            DB::commit();

            return $user->fresh(['wallet']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Connexion utilisateur
     */
    public function login(array $credentials, string $deviceName = 'api'): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Ces informations d\'identification ne correspondent pas à nos enregistrements.'],
            ]);
        }

        if (!$user->email_verified) {
            throw ValidationException::withMessages([
                'email' => ['Veuillez vérifier votre email avant de vous connecter.'],
            ]);
        }

        if (!$user->hasCompletedRegistration()) {
            throw ValidationException::withMessages([
                'email' => ['Veuillez compléter votre inscription.'],
            ]);
        }

        // Révoquer les anciens tokens (optionnel)
        // $user->tokens()->delete();

        $token = $user->createToken($deviceName)->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Renvoyer le code OTP
     */
    public function resendOtp(string $email): void
    {
        $user = User::where('email', $email)->firstOrFail();

        if ($user->email_verified && $user->registration_completed) {
            throw ValidationException::withMessages([
                'email' => ['Ce compte est déjà vérifié et complété.'],
            ]);
        }

        $otp = $user->generateOtpCode();
        $user->notify(new SendOtpNotification($otp, 'verification'));
    }

    /**
     * Demander la réinitialisation du mot de passe
     */
    public function forgotPassword(string $email): void
    {
        $user = User::where('email', $email)->firstOrFail();

        if (!$user->hasCompletedRegistration()) {
            throw ValidationException::withMessages([
                'email' => ['Ce compte n\'a pas encore été complété. Veuillez terminer votre inscription.'],
            ]);
        }

        $otp = $user->generateOtpCode();
        $user->notify(new SendOtpNotification($otp, 'reset'));
    }

    public function verifyResetOtp(string $email, string $otp): User
    {
        $user = User::where('email', $email)->firstOrFail();

        if ($user->isOtpExpired()) {
            throw ValidationException::withMessages([
                'otp' => ['Le code OTP a expiré. Veuillez demander un nouveau code.'],
            ]);
        }

        if ($user->otp_code !== $otp) {
            throw ValidationException::withMessages([
                'otp' => ['Le code OTP est invalide.'],
            ]);
        }

        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);
        return $user;
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function resetPassword(string $email, string $password, string $passwordConfirmation): User
    {
        $user = User::where('email', $email)->firstOrFail();
        if ($password !== $passwordConfirmation) {
            throw ValidationException::withMessages([
                'password_confirmation' => ['Les mot de passe ne sont pas identiques.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($password),
        ]);

        // Révoquer tous les tokens existants
        $user->tokens()->delete();

        return $user;
    }

    /**
     * Changer le mot de passe (utilisateur connecté)
     */
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

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        Log::info("Password changed for user {$user->email}");

        return $user->fresh();
    }

    /**
     * Mettre à jour les informations du profil
     */
    public function updateProfile(User $user, array $data): User
    {
        $allowedFields = ['nom', 'prenom', 'numero', 'country_id', 'whatsapp', 'age', 'situation_professionnelle'];
        $updateData = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            throw ValidationException::withMessages([
                'data' => ['Aucune donnée valide à mettre à jour.'],
            ]);
        }

        $user->update($updateData);
        Log::info("Profile updated for user {$user->email}", $updateData);
        return $user->fresh();
    }

    /**
     * Mettre à jour l'avatar de l'utilisateur
     */
    public function updateAvatar(User $user, UploadedFile $avatar): User
    {
        // Supprimer l'ancien avatar s'il existe
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Stocker le nouvel avatar
        $path = $avatar->store('avatars', 'public');

        $user->update([
            'avatar' => $path,
        ]);

        Log::info("Avatar updated for user {$user->email}");

        return $user->fresh();
    }

    /**
     * Supprimer l'avatar de l'utilisateur
     */
    public function deleteAvatar(User $user): User
    {
        if (!$user->avatar) {
            throw ValidationException::withMessages([
                'avatar' => ['Aucun avatar à supprimer.'],
            ]);
        }

        Storage::disk('public')->delete($user->avatar);

        $user->update([
            'avatar' => null,
        ]);

        Log::info("Avatar deleted for user {$user->email}");

        return $user->fresh();
    }

    /**
     * Déconnexion
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Déconnexion de tous les appareils
     */
    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();
    }
}
