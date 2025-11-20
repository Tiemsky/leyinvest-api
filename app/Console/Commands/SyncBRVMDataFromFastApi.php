<?php

namespace App\Console\Commands;

use App\Services\BRVMDataSyncService;
use Illuminate\Console\Command;

class SyncBRVMDataFromFastApi extends Command
{
    protected $signature = 'brvm:sync
                            {--clean : Nettoyer les snapshots orphelins}';

    protected $description = 'Synchronise les données BRVM depuis FastAPI (historique glissant 10 jours)';

    public function handle(BRVMDataSyncService $syncService)
    {
        $this->info('🔄 Synchronisation des données BRVM...');

        try {
            // Synchronisation principale
            $stats = $syncService->syncFromFastAPI();

            // Affichage des résultats
            $this->newLine();
            $this->info('📊 Résultats:');
            $this->line("  ✓ Actions mises à jour: {$stats['actions_updated']}");
            $this->line("  ✓ Snapshots créés: {$stats['snapshots_created']}");
            $this->line("  🗑️ Snapshots supprimés (rotation): {$stats['snapshots_deleted']}");

            if ($stats['errors'] > 0) {
                $this->warn("  ⚠️ Erreurs: {$stats['errors']}");
            }

            // Nettoyage optionnel
            if ($this->option('clean')) {
                $this->info('🧹 Nettoyage des snapshots orphelins...');
                $cleaned = $syncService->cleanOrphanedSnapshots();
                $this->line("  ✓ {$cleaned} snapshots orphelins supprimés");
            }

            $this->newLine();
            $this->info('✅ Synchronisation terminée avec succès!');
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erreur: {$e->getMessage()}");
            return 1;
        }
    }
}
