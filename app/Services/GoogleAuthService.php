<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthService
{
    /**
     * Générer l'URL d'authentification Google
     * On accepte un paramètre $state pour la redirection dynamique (ex: frontend_url=...)
     */
    public function getAuthUrl(?string $state = null): string
    {
        $driver = Socialite::driver('google')->stateless();

        if ($state) {
            $driver->with(['state' => $state]);
        }

        return $driver->redirect()->getTargetUrl();
    }

    /**
     * Récupérer l'utilisateur Google depuis le callback Socialite
     */
    public function getGoogleUser()
    {
        return Socialite::driver('google')->stateless()->user();
    }

    /**
     * Créer ou mettre à jour un utilisateur via Google (Fintech Ready)
     */
    public function getOrCreateUser($googleUser): User
    {
        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();

        if (empty($email) || empty($googleId)) {
            throw new \Exception('Données Google incomplètes.');
        }

        return DB::transaction(function () use ($email, $googleId, $googleUser) {
            // lockForUpdate empêche la création de doublons lors de clics simultanés
            $user = User::where('email', $email)
                ->orWhere('google_id', $googleId)
                ->lockForUpdate()
                ->first();

            if ($user) {
                $this->updateExistingUser($user, $googleUser, $googleId);

                return $user->fresh();
            }

            return $this->createNewUser($googleUser, $googleId);
        });
    }

    private function updateExistingUser(User $user, $googleUser, string $googleId): void
    {
        $updates = [];
        if (empty($user->google_id)) {
            $updates['google_id'] = $googleId;
        }
        if (! $user->email_verified) {
            $updates['email_verified'] = true;
        }

        // Mise à jour des infos de base si vides
        if (empty($user->nom)) {
            $updates['nom'] = $googleUser->user['family_name'] ?? '';
        }
        if (empty($user->prenom)) {
            $updates['prenom'] = $googleUser->user['given_name'] ?? '';
        }
        if (empty($user->avatar)) {
            $updates['avatar'] = $googleUser->getAvatar();
        }

        if (! empty($updates)) {
            $user->update($updates);
        }
    }

    private function createNewUser($googleUser, string $googleId): User
    {
        return User::create([
            'email' => $googleUser->getEmail(),
            'google_id' => $googleId,
            'nom' => $googleUser->user['family_name'] ?? '',
            'prenom' => $googleUser->user['given_name'] ?? '',
            'avatar' => $googleUser->getAvatar(),
            'auth_provider' => 'google',
            'email_verified' => true,
            'registration_completed' => false, // Important pour le workflow Fintech
            'role' => 'user',
        ]);
    }
}
