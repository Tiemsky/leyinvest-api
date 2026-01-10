<?php

namespace App\Services;

use App\Models\BocIndicator;
use App\Models\BrvmSector;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Action;;
use Illuminate\Support\Str;

class SyncBrvmDataService
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        // Assurez-vous que ces clés existent dans config/services.php
        $this->baseUrl = config('services.fastapi.url');
        $this->token = config('services.fastapi.token');
    }

    public function syncAllData()
    {
        try {
            Log::info("🔄 Démarrage de la synchronisation complète BRVM...");

            $response = Http::withHeaders([
                'X-Debug-Key' => $this->token // Utilisation de la clé définie dans ton .env FastAPI
            ])->timeout(60)->get("{$this->baseUrl}/api/sync/all-data");

            if ($response->failed()) {
                throw new \Exception("L'API FastAPI a répondu avec une erreur : " . $response->status());
            }

            $payload = $response->json()['payload'];

            // 1. Synchronisation des Actions
            $this->syncActions($payload['actions']);

            // 2. Synchronisation des Indices
            $this->syncIndices($payload['indices']);

            // 3. Synchronisation de l'Indicateur de Marché
            $this->syncMarketIndicator($payload['indicateur_marche']);

            Log::info("✅ Synchronisation BRVM terminée avec succès.");
            return true;

        } catch (\Exception $e) {
            Log::error("❌ Erreur critique lors de la synchronisation BRVM : " . $e->getMessage());
            return false;
        }
    }

    protected function syncActions(array $actions)
    {
        foreach ($actions as $item) {
            // On utilise le 'symbole' comme identifiant unique (ex: ABJC)
            Action::updateOrCreate(
                ['symbole' => $item['symbole']],
                [
                    'nom' => $item['nom'],
                    'slug' => Str::slug($item['nom']),
                    'cours_ouverture' => $item['cours_ouverture'],
                    'cours_cloture' => $item['cours_cloture'],
                    'cours_veille' => $item['cours_veille'],
                    'variation' => $item['variation'],
                    'volume' => $item['volume'],
                    'last_sync_at' => $item['updated_at'],
                ]
            );
        }
        Log::debug(count($actions) . " actions synchronisées.");
    }

    protected function syncIndices(array $indices)
    {
        foreach ($indices as $item) {
            BrvmSector::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'nom' => $item['nom'],
                    'variation' => $item['variation'],
                    'last_sync_at' => $item['updated_at'],
                ]
            );
        }
        Log::debug(count($indices) . " indices sectoriels synchronisés.");
    }

    protected function syncMarketIndicator($indicator)
    {
        if (!$indicator) return;

        BocIndicator::updateOrCreate(
            ['date_rapport' => $indicator['date_rapport']],
            [
                'per_moyen' => $indicator['per_moyen'],
                'taux_rendement_moyen' => $indicator['taux_rendement_moyen'],
                'taux_rentabilite_moyen' => $indicator['taux_rentabilite_moyen'],
                'prime_risque_marche' => $indicator['prime_risque_marche'],
                'source_pdf' => $indicator['source_pdf'],
            ]
        );
        Log::debug("Indicateurs de marché du {$indicator['date_rapport']} mis à jour.");
    }
}
