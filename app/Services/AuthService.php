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
    public function __construct(
        protected RefreshTokenService $refreshTokenService,
        protected CookieService $cookieService
    ) {}

    /**
     * Étape 1 : Enregistrer ou reprendre une inscription incomplète et envoyer l'OTP.
     *
     * @throws ValidationException Si l'email est déjà utilisé par un compte complet
     * @return User L'utilisateur (nouveau ou existant)
     */
    public function registerStepOne(array $data): User
    {
        $email = $data['email'];

        // Vérifie si un compte COMPLET existe déjà
        $existingCompleteUser = User::where('email', $email)
            ->where('registration_completed', true)
            ->first();

        if ($existingCompleteUser) {
            throw ValidationException::withMessages([
                'email' => ['Cet email est déjà utilisé. Veuillez vous connecter.'],
            ]);
        }

        // Vérifie si une inscription INCOMPLÈTE existe
        $incompleteUser = User::where('email', $email)
            ->where('registration_completed', false)
            ->first();

        if ($incompleteUser) {
            // Mettre à jour les informations si elles ont changé
            $incompleteUser->update([
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
            ]);

            // Réinitialiser la vérification email si elle était déjà faite
            if ($incompleteUser->email_verified) {
                $incompleteUser->update(['email_verified' => false]);
            }

            // Régénérer et envoyer un nouvel OTP
            $otp = $incompleteUser->generateOtpCode();
            $incompleteUser->notify(new SendOtpNotification($otp, 'verification'));
            Log::info("OTP resent for incomplete registration to {$incompleteUser->email}: {$otp}");

            return $incompleteUser->fresh();
        }

        // Créer un nouvel utilisateur
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

        return $user;
    }

    /**
     * Vérifier le code OTP pour l'inscription.
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

        // Marquer l'email comme vérifié et nettoyer l'OTP
        $user->update([
            'email_verified' => true,
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        Log::info("Email verified successfully for {$user->email}");

        return $user->fresh();
    }

    /**
     * Étape 2 : Compléter l'inscription avec mot de passe et informations supplémentaires.
     */
    public function registerStepTwo(array $data): User
    {
        $user = User::where('email', $data['email'])
            ->where('email_verified', true)
            ->where('registration_completed', false)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Aucune inscription en attente de complétion pour cet email. Veuillez d\'abord vérifier votre email.'],
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

            Log::info("Registration completed successfully for {$user->email}");
            DB::commit();

            return $user->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to complete registration for {$data['email']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Connexion utilisateur.
     */
    public function login(array $credentials, string $deviceName = 'api'): array
    {
        $genericError = ['Ces informations d\'identification ne correspondent pas à nos enregistrements.'];

        $user = User::where('email', $credentials['email'])->first();

        // 1. Vérification de l'existence de l'utilisateur ou du mot de passe (Sécurité: message générique)
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => $genericError]);
        }

        // 2. Vérifications spécifiques (ces erreurs ne sont affichées QUE si les credentials sont corrects)
        if (!$user->registration_completed) {
            throw ValidationException::withMessages([
                'email' => ['Votre inscription n\'est pas terminée. Veuillez compléter votre inscription.'],
            ]);
        }

        if (!$user->email_verified) {
            throw ValidationException::withMessages([
                'email' => ['Votre email n\'est pas vérifié. Veuillez vérifier votre boîte de réception.'],
            ]);
        }

        // 3. Création des tokens
        $tokens = $this->refreshTokenService->createTokens($user, $deviceName);
        Log::info("User {$user->email} logged in successfully.");

        return [
            'user' => $user,
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => $tokens['token_type'],
            'expires_in' => $tokens['expires_in'],
            'refresh_expires_in' => $tokens['refresh_expires_in'],
        ];
    }

/**
 * Rafraîchir le token d'accès.
 */
public function refreshToken(string $refreshToken): array
{
    // FIX: Appeler directement le service. Si le token est invalide/expiré,
    // le service lèvera déjà la ValidationException (voir le RefreshTokenService corrigé).
    $tokens = $this->refreshTokenService->refreshToken($refreshToken);

    // Après un succès du rafraîchissement, on récupère les infos de l'utilisateur
    // via le nouveau token (méthode de secours si le service ne retourne pas l'user).
    // Une approche plus propre consiste à récupérer l'info via un appel distinct si nécessaire.
    // Dans ce scénario, nous allons chercher l'utilisateur via un appel au service.

    $tokenInfo = $this->refreshTokenService->getTokenInfo($tokens['refresh_token']);

    if (empty($tokenInfo['user_id'])) {
         // Si le service ne donne aucune info sur l'utilisateur, lever une erreur générique
         throw ValidationException::withMessages(['token' => ['Informations utilisateur introuvables.']]);
    }

    $user = User::find($tokenInfo['user_id']);

    if (!$user) {
        // Le service aurait déjà dû gérer ce cas en révoquant le token, mais on le sécurise ici.
        throw ValidationException::withMessages(['token' => ['Utilisateur introuvable.']]);
    }

    Log::info("Token refreshed for user {$user->email}");

    return [
        'user' => $user,
        'access_token' => $tokens['access_token'],
        'refresh_token' => $tokens['refresh_token'],
        'token_type' => $tokens['token_type'],
        'expires_in' => $tokens['expires_in'],
        'refresh_expires_in' => $tokens['refresh_expires_in'],
    ];
}
    /**
     * Renvoyer un OTP pour une inscription incomplète.
     */
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

        // Générer un nouvel OTP même si l'email est déjà vérifié
        // (utile si l'utilisateur a perdu l'accès avant de compléter l'inscription)
        $otp = $user->generateOtpCode();
        $user->notify(new SendOtpNotification($otp, 'verification'));
        Log::info("OTP resent to {$user->email}: {$otp}");
    }

    /**
     * Mot de passe oublié : envoyer un OTP de réinitialisation.
     */
    public function forgotPassword(string $email): void
    {
        $user = User::where('email', $email)
            ->where('registration_completed', true)
            ->where('email_verified', true)
            ->first();

        if (!$user) {
            // Silencieux pour sécurité (ne pas confirmer l'existence de l'email)
            Log::info("Password reset requested for non-existent or incomplete account: {$email}");
            return;
        }

        $otp = $user->generateOtpCode();
        $user->notify(new SendOtpNotification($otp, 'reset'));
        Log::info("Password reset OTP sent to {$user->email}: {$otp}");
    }

    /**
     * Vérifier l'OTP de réinitialisation de mot de passe.
     */
    public function verifyResetOtp(string $email, string $otp): User
    {
        $user = User::where('email', $email)
            ->where('registration_completed', true)
            ->where('email_verified', true)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Aucun compte actif trouvé pour cet email.'],
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

        // Nettoyer l'OTP après vérification réussie
        $user->update(['otp_code' => null, 'otp_expires_at' => null]);
        Log::info("Password reset OTP verified for {$user->email}");

        return $user->fresh();
    }

    /**
     * Réinitialiser le mot de passe après vérification OTP.
     */
    public function resetPassword(string $email, string $password, string $passwordConfirmation): User
    {
        if ($password !== $passwordConfirmation) {
            throw ValidationException::withMessages([
                'password_confirmation' => ['Les mots de passe ne correspondent pas.'],
            ]);
        }

        $user = User::where('email', $email)
            ->where('registration_completed', true)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Utilisateur non trouvé.'],
            ]);
        }

        // Vérifier que le nouveau mot de passe est différent de l'ancien
        if (Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Le nouveau mot de passe doit être différent de l\'ancien.'],
            ]);
        }

        DB::beginTransaction();
        try {
            $user->update(['password' => Hash::make($password)]);
            $user->tokens()->delete(); // Révoquer tous les tokens
            $this->refreshTokenService->revokeAllUserTokens($user);

            Log::info("Password reset successfully for {$user->email}");
            DB::commit();

            return $user->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to reset password for {$email}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Changer le mot de passe (utilisateur connecté).
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

        DB::beginTransaction();
        try {
            $user->update(['password' => Hash::make($newPassword)]);

            // Révoquer tous les tokens sauf le token actuel
            $currentTokenId = $user->currentAccessToken()?->id;
            $user->tokens()->where('id', '!=', $currentTokenId)->delete();

            Log::info("Password changed for user {$user->email}");
            DB::commit();

            return $user->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to change password for {$user->email}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mettre à jour le profil utilisateur.
     */
    public function updateProfile(User $user, array $data): User
    {
        $allowed = ['nom', 'prenom', 'numero', 'country_id', 'whatsapp', 'age', 'genre', 'situation_professionnelle'];
        $update = array_intersect_key($data, array_flip($allowed));

        if (empty($update)) {
            throw ValidationException::withMessages([
                'data' => ['Aucune donnée valide à mettre à jour.'],
            ]);
        }

        DB::beginTransaction();
        try {
            $user->update($update);
            Log::info("Profile updated for user {$user->email}", $update);
            DB::commit();

            return $user->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update profile for {$user->email}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mettre à jour l'avatar utilisateur.
     */
    public function updateAvatar(User $user, UploadedFile $avatar): User
    {
        DB::beginTransaction();
        try {
            // Supprimer l'ancien avatar s'il existe
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Stocker le nouvel avatar
            $path = $avatar->store('avatars', 'public');
            $user->update(['avatar' => $path]);

            Log::info("Avatar updated for {$user->email}");
            DB::commit();

            return $user->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update avatar for {$user->email}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Supprimer l'avatar utilisateur.
     */
    public function deleteAvatar(User $user): User
    {
        if (!$user->avatar) {
            throw ValidationException::withMessages([
                'avatar' => ['Aucun avatar à supprimer.'],
            ]);
        }

        DB::beginTransaction();
        try {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);

            Log::info("Avatar deleted for {$user->email}");
            DB::commit();

            return $user->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete avatar for {$user->email}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Déconnexion de la session actuelle.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
        Log::info("User {$user->email} logged out from current session.");
    }

    /**
     * Déconnexion de toutes les sessions.
     */
    public function logoutAll(User $user): void
    {
        $this->refreshTokenService->revokeAllUserTokens($user);
        $user->tokens()->delete();
        Log::info("User {$user->email} logged out from all sessions.");
    }

    /**
     * Supprimer le compte utilisateur.
     */
    public function deleteUser(User $user): void
    {
        DB::beginTransaction();
        try {
            // Révoquer tous les tokens
            $this->refreshTokenService->revokeAllUserTokens($user);
            $user->tokens()->delete();

            // Supprimer l'avatar
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Supprimer l'utilisateur
            $email = $user->email;
            $user->delete();

            Log::info("User {$email} deleted successfully.");
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete user {$user->email}: " . $e->getMessage());
            throw $e;
        }
    }
}
