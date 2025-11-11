<?php
namespace App\Services;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GoogleAuthService
{
    /**
     * Générer l'URL d'authentification Google
     *
     * @return string URL de redirection Google
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
     *
     * @throws \Laravel\Socialite\Two\InvalidStateException
     * @throws \GuzzleHttp\Exception\ClientException
     */
    public function getGoogleUser()
    {
        return Socialite::driver('google')
            ->stateless()
            ->user();
    }

    /**
     * Créer ou récupérer un utilisateur depuis les données Google
     * Utilise une transaction pour garantir l'intégrité des données
     *
     * @param mixed $googleUser Objet utilisateur retourné par Google
     * @return User
     * @throws \Exception
     */
    public function getOrCreateUser($googleUser): User
    {
        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();

        // Validation des données essentielles
        if (empty($email) || empty($googleId)) {
            throw new \Exception('Email ou Google ID manquant');
        }

        return DB::transaction(function () use ($email, $googleId, $googleUser) {
            // Chercher par email OU google_id avec verrouillage
            $user = User::where('email', $email)
                ->orWhere('google_id', $googleId)
                ->lockForUpdate()
                ->first();

            if ($user) {
                // Utilisateur existant - mise à jour
                $this->updateExistingUser($user, $googleUser, $googleId);
                return $user->fresh();
            }

            // Créer un nouvel utilisateur
            return $this->createNewUser($googleUser, $googleId);
        });
    }

    /**
     * Mettre à jour un utilisateur existant
     *
     * @param User $user
     * @param mixed $googleUser
     * @param string $googleId
     * @return void
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

        // Mettre à jour le provider si nécessaire
        if (empty($user->auth_provider)) {
            $updates['auth_provider'] = 'google';
        }

        if (!empty($updates)) {
            $user->update($updates);
        }
    }

    /**
     * Créer un nouvel utilisateur
     *
     * @param mixed $googleUser
     * @param string $googleId
     * @return User
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

        return $user;
    }

    /**
     * Vérifier si le profil est complet
     *
     * @param mixed $googleUser
     * @return bool
     */
    private function checkProfileComplete($googleUser): bool
    {
        return !empty($googleUser->user['family_name'])
            && !empty($googleUser->user['given_name']);
    }



    /**
     * Générer un token d'authentification pour l'utilisateur
     * Compatible avec React/SPA
     *
     * @param User $user
     * @param string $tokenName
     * @return string
     */
    public function generateAuthToken(User $user, string $tokenName = 'auth_token'): string
    {
        // Révoquer les anciens tokens si nécessaire
        // $user->tokens()->delete();

        return $user->createToken($tokenName)->plainTextToken;
    }

    /**
     * Préparer la réponse pour le frontend React
     *
     * @param User $user
     * @param string $token
     * @return array
     */
    public function prepareAuthResponse(User $user, string $token): array
    {
        return [
            'success' => true,
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'avatar' => $user->avatar,
                'role' => $user->role,
                'registration_completed' => $user->registration_completed,
                'email_verified' => $user->email_verified,
            ],
        ];
    }
}
