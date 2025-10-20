<?php

namespace App\Services;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class GoogleAuthService
{
    /**
     * Générer l'URL d'authentification Google
     */
    public function getAuthUrl(): string
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();
    }

    /**
     * Récupérer l'utilisateur Google depuis le callback
     */
    public function getGoogleUser()
    {
        return Socialite::driver('google')
            ->stateless()
            ->user();
    }

    /**
     * Créer ou récupérer un utilisateur depuis les données Google
     */
    public function getOrCreateUser($googleUser): User
    {
        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();

        // Chercher par email OU google_id
        $user = User::where('email', $email)
            ->orWhere('google_id', $googleId)
            ->first();

        if ($user) {
            // Utilisateur existant - mise à jour
            $this->updateExistingUser($user, $googleUser, $googleId);
            return $user->fresh();
        }

        // Créer un nouvel utilisateur
        return $this->createNewUser($googleUser, $googleId);
    }

    /**
     * Mettre à jour un utilisateur existant
     */
    private function updateExistingUser(User $user, $googleUser, string $googleId): void
    {
        $updates = [];

        // Lier le compte Google si pas encore fait
        if (empty($user->google_id)) {
            $updates['google_id'] = $googleId;
        }

        // Vérifier l'email si pas encore fait
        if (!$user->email_verified) {
            $updates['email_verified'] = true;
        }

        // Mettre à jour nom/prénom si vides
        if (empty($user->nom) && !empty($googleUser->user['family_name'])) {
            $updates['nom'] = $googleUser->user['family_name'];
        }

        if (empty($user->prenom) && !empty($googleUser->user['given_name'])) {
            $updates['prenom'] = $googleUser->user['given_name'];
        }

        // Mettre à jour l'avatar
        if ($googleUser->getAvatar()) {
            $updates['avatar'] = $googleUser->getAvatar();
        }

        if (!empty($updates)) {
            $user->update($updates);
        }
    }

    /**
     * Créer un nouvel utilisateur
     */
    private function createNewUser($googleUser, string $googleId): User
    {
        $user = User::create([
            'email' => $googleUser->getEmail(),
            'nom' => $googleUser->user['family_name'] ?? '',
            'prenom' => $googleUser->user['given_name'] ?? '',
            'google_id' => $googleId,
            'avatar' => $googleUser->getAvatar(),
            'auth_provider' => 'google',
            'email_verified' => true,
            'registration_completed' => $this->checkProfileComplete($googleUser),
            'role' => 'user',
        ]);

        // Créer le portefeuille par défaut
        $this->createDefaultPortfolio($user);

        return $user;
    }

    /**
     * Vérifier si le profil est complet
     */
    private function checkProfileComplete($googleUser): bool
    {
        return !empty($googleUser->user['family_name'])
            && !empty($googleUser->user['given_name']);
    }

    /**
     * Créer un portefeuille par défaut pour l'utilisateur
     */
    private function createDefaultPortfolio(User $user): void
    {
        try {
            // Implémenter votre logique de création de portefeuille ici
            // Exemple: Portfolio::create(['user_id' => $user->id, ...]);
        } catch (\Exception $e) {
            \Log::error('Erreur création portefeuille: ' . $e->getMessage());
        }
    }
}
