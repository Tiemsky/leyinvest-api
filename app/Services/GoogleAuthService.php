<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;

class GoogleAuthService
{
    /**
     * Génère l'URL de redirection vers Google.
     * Le paramètre 'state' peut servir à passer des données au frontend après le callback.
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
     * Traite l'utilisateur récupéré via Socialite.
     */
    public function handleCallback(): User
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            return $this->getOrCreateUser($googleUser);
        } catch (Exception $e) {
            Log::error('Erreur Google Auth Callback: '.$e->getMessage());
            throw new Exception("Impossible de s'authentifier avec Google.");
        }
    }

    /**
     * Logique centrale : Créer ou Lier un compte Google.
     */
    public function getOrCreateUser(GoogleUser $googleUser): User
    {
        $email = strtolower(trim($googleUser->getEmail()));
        $googleId = $googleUser->getId();

        return DB::transaction(function () use ($email, $googleId, $googleUser) {
            // lockForUpdate empêche qu'un autre processus (ex: RegisterStepOne)
            // n'interfère pendant cette transaction.
            $user = User::where('email', $email)
                ->orWhere('google_id', $googleId)
                ->lockForUpdate()
                ->first();

            if ($user) {
                return $this->linkExistingAccount($user, $googleUser);
            }

            return $this->createNewGoogleAccount($googleUser);
        });
    }

    /**
     * SCÉNARIO : L'utilisateur existe déjà (via Email Manuel ou autre).
     * On unifie les informations.
     */
    private function linkExistingAccount(User $user, GoogleUser $googleUser): User
    {
        $updates = [
            'google_id' => $googleUser->getId(),
            'email_verified' => true, // La confiance est déléguée à Google
        ];

        // On ne remplit les colonnes vides que pour ne pas écraser
        // les données manuelles potentiellement plus précises.
        if (empty($user->nom)) {
            $updates['nom'] = $googleUser->offsetGet('family_name') ?? $user->nom;
        }
        if (empty($user->prenom)) {
            $updates['prenom'] = $googleUser->offsetGet('given_name') ?? $user->prenom;
        }
        if (empty($user->avatar)) {
            $updates['avatar'] = $googleUser->getAvatar();
        }

        // Si l'utilisateur avait commencé une inscription manuelle (Step 1),
        // mais passe maintenant par Google, il garde registration_completed = false
        // pour être forcé de faire le Step 2 (Pays, Numéro, etc.)
        $user->update($updates);

        Log::info("Compte lié à Google pour l'utilisateur ID: {$user->id}");

        return $user;
    }

    /**
     * SCÉNARIO : Nouvel utilisateur total.
     */
    private function createNewGoogleAccount(GoogleUser $googleUser): User
    {
        $user = User::create([
            'email' => strtolower($googleUser->getEmail()),
            'google_id' => $googleUser->getId(),
            'nom' => $googleUser->offsetGet('family_name') ?? '',
            'prenom' => $googleUser->offsetGet('given_name') ?? '',
            'avatar' => $googleUser->getAvatar(),
            'auth_provider' => 'google',
            'email_verified' => true,
            'registration_completed' => false, // Obligatoire pour passer au Step 2
            'password' => Str::random(32), // Sécurité pour NOT NULL
            'key' => Str::uuid()->toString(), // Si vous utilisez une colonne 'key' unique
        ]);

        Log::info("Nouveau compte créé via Google: {$user->email}");

        return $user;
    }
}
