<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SendOtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
            'prenoms' => $data['prenoms'],
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
                'otp' => ['Cet email est déjà vérifié.'],
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

        $user->completeRegistration([
            'password' => $data['password'],
            'country' => $data['country'],
            'phone' => $data['phone'] ?? null,
        ]);

        return $user->fresh();
    }

    /**
     * Connexion utilisateur
     */
    public function login(array $credentials, string $deviceName = 'api'): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
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

    /**
     * Réinitialiser le mot de passe
     */
    public function resetPassword(string $email, string $otp, string $password): User
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
            'password' => Hash::make($password),
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        // Révoquer tous les tokens existants
        $user->tokens()->delete();

        return $user;
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
