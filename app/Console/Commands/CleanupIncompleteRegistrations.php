<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupIncompleteRegistrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registrations:cleanup
                            {--dry-run : Afficher le nombre d\'inscriptions incomplètes sans les supprimer}
                            {--hours=24 : Nombre d\'heures avant suppression}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Supprime définitivement (hard delete) les inscriptions incomplètes vieilles de plus de X heures.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');

        // 🚨 Important : inclure les soft-deleted si jamais un admin les a soft-supprimés pendant le process
        // Mais dans ton cas, ce n'est probablement pas nécessaire.
        // On se concentre sur les users actifs mais incomplets.
        $query = User::withTrashed() // ⚠️ Optionnel : si tu veux aussi nettoyer les soft-deleted incomplets
            ->where('registration_completed', false)
            ->where('created_at', '<', now()->subHours($hours));

        // Si tu es **sûr** qu'aucun soft-delete n'est appliqué aux inscriptions incomplètes,
        // tu peux garder juste `User::where(...)` (sans `withTrashed()`).

        $count = $query->count();

        if ($count === 0) {
            $this->info("✅ Aucune inscription incomplète à nettoyer (seuil: {$hours}h).");
            return Command::SUCCESS;
        }

        if ($dryRun) {
            $sample = $query->limit(10)->get(['id','key', 'nom', 'prenom', 'email', 'created_at']);
            $this->info("🔍 Mode dry-run : {$count} inscription(s) incomplète(s) trouvée(s).");
            $this->table(['ID','key', 'Nom', 'Prénom', 'Email', 'Créé il y a'], $sample->map(function ($user) {
                return [
                    $user->id,
                    $user->key,
                    $user->nom,
                    $user->prenom,
                    $user->email,
                    now()->diffForHumans($user->created_at, true)
                ];
            })->toArray());
            return Command::SUCCESS;
        }

        // Récupérer les utilisateurs à supprimer (avec avatar)
        $users = $query->get(['id', 'nom', 'prenom', 'email', 'avatar']);

        $deleted = 0;
        foreach ($users as $user) {
            // Supprimer l'avatar physique si présent
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            // 🔥 Hard delete : supprime définitivement de la base
            if ($user->forceDelete()) {
                $deleted++;
                Log::info("🧹 Hard delete inscription incomplète : ID={$user->id}, email={$user->email}");
            }
        }

        $this->info("✅ {$deleted} inscription(s) incomplète(s) supprimée(s) définitivement (seuil: {$hours}h).");
        Log::info("Nettoyage hard des inscriptions incomplètes : {$deleted} comptes supprimés.");

        return Command::SUCCESS;
    }
}
