<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

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
     * Récupérer l'utilisateur Google depuis le callback Socialite
     */
    public function getGoogleUser()
    {
        // On utilise stateless() car on est en mode API (sans session Laravel classique)
        return Socialite::driver('google')->stateless()->user();
    }

    /**
     * Créer ou mettre à jour un utilisateur via Google
     */
    public function getOrCreateUser($googleUser): User
    {
        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();

        if (empty($email) || empty($googleId)) {
            throw new \Exception('Données Google incomplètes (Email ou ID manquant).');
        }

        return DB::transaction(function () use ($email, $googleId, $googleUser) {
            // Recherche par email ou google_id avec verrouillage pour éviter les doublons
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

    /**
     * Met à jour les informations si l'utilisateur existe déjà
     */
    private function updateExistingUser(User $user, $googleUser, string $googleId): void
    {
        $updates = [];

        // Si l'utilisateur s'était inscrit par email, on lie son compte Google
        if (empty($user->google_id)) {
            $updates['google_id'] = $googleId;
        }

        // Google certifie l'email, donc on valide automatiquement
        if (! $user->email_verified) {
            $updates['email_verified'] = true;
        }

        // On ne met à jour l'avatar que s'il n'en a pas déjà un personnalisé
        if (empty($user->avatar) && $googleUser->getAvatar()) {
            $updates['avatar'] = $googleUser->getAvatar();
        }

        // On remplit nom/prénom s'ils sont manquants
        if (empty($user->nom)) {
            $updates['nom'] = $googleUser->user['family_name'] ?? ($user->nom ?? '');
        }
        if (empty($user->prenom)) {
            $updates['prenom'] = $googleUser->user['given_name'] ?? ($user->prenom ?? '');
        }

        if (! empty($updates)) {
            $user->update($updates);
        }
    }

    /**
     * Création d'un nouvel utilisateur (Premier Login Google)
     */
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
            'registration_completed' => false, // Oblige à passer par une étape de finition si besoin
            'role' => 'user',
        ]);
    }
}
