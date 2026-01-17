<?php

namespace App\Services;

use App\Models\Action;
use App\Models\BrvmSector;
use Illuminate\Support\Str;
use App\Models\BocIndicator;
use App\Support\BrvmMapping;
use App\Models\ClassifiedSector;
use App\Jobs\ProcessForecastsJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SyncBrvmDataService
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        // Nettoyage de l'URL pour éviter les doubles slashes
        $this->baseUrl = rtrim(config('services.scraper.url'), '/');
        $this->token = config('services.scraper.webhook_token');
    }

    /**
     * Point d'entrée principal pour la synchronisation
     */
    public function syncAllData(): bool
    {
        if (empty($this->token)) {
            Log::error("Configuration manquante : FASTAPI_WEBHOOK_TOKEN");
            return false;
        }

        Log::info(" Démarrage de la synchronisation BRVM depuis {$this->baseUrl}");

        try {
            $response = Http::withHeaders([
                'X-Webhook-Token' => $this->token,
                'Accept' => 'application/json',
            ])
                ->timeout(60)
                ->retry(3, 100)
                ->get("{$this->baseUrl}/api/sync/all-data");

            if ($response->failed()) {
                $this->logErrorResponse($response);
                return false;
            }

            $payload = $response->json('payload');

            if (!is_array($payload)) {
                throw new \Exception("Le format du payload reçu est invalide.");
            }

            // Utilisation d'une transaction pour garantir la cohérence des données
            DB::transaction(function () use ($payload) {
                // 1. On synchronise les secteurs d'abord (car les actions en dépendent)
                $this->syncIndices($payload['indices'] ?? []);
                // 2. On synchronise les actions
                $this->syncActions($payload['actions'] ?? []);
                // 3. On synchronise les indicateurs généraux
                $this->syncMarketIndicator($payload['indicateur_marche'] ?? null);
            });
            ProcessForecastsJob::dispatch();
            Log::info(" Synchronisation BRVM terminée avec succès.");
            return true;

        } catch (\Exception $e) {
            Log::error("Erreur critique lors de la synchro BRVM : " . $e->getMessage());
            return false;
        }
    }
    protected function syncActions(array $actions): void
    {
        if (empty($actions)) return;

        $brvmSectors = BrvmSector::pluck('id', 'nom')->toArray();
        $classifiedSectors = ClassifiedSector::pluck('id', 'nom')->toArray();
        $mapping = BrvmMapping::actionSectorMap();
        $now = now();

        $records = [];
        foreach ($actions as $item) {
            $symbole = $item['symbole'];
            $sectors = $mapping[$symbole] ?? null;
            if (!$sectors) continue;

            $records[] = [
                'key' => 'act_' . strtolower($symbole),                'symbole' => $symbole,
                'nom' => $item['nom'] ?? 'Inconnu',
                'brvm_sector_id' => $brvmSectors[$sectors[0]] ?? 1,
                'classified_sector_id' => $classifiedSectors[$sectors[1]] ?? 1,
                'volume' => (string)($item['volume'] ?? '0'),
                'cours_veille' => $item['cours_veille'] ?? 0,
                'cours_ouverture' => $item['cours_ouverture'] ?? 0,
                'cours_cloture' => $item['cours_cloture'] ?? 0,
                'variation' => $item['variation'] ?? 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Action::upsert($records, ['symbole'], ['volume', 'cours_veille', 'cours_ouverture', 'cours_cloture', 'variation', 'updated_at']);
    }

    protected function syncIndices(array $indices): void
    {
        if (empty($indices))
            return;

        $records = array_map(fn($item) => [
            'key' => 'brv_' . Str::random(config('key.length', 10)),
            'slug' => $item['slug'],
            'nom' => $item['nom'],
            'variation' => $item['variation'] ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $indices);

        BrvmSector::upsert($records, ['slug'], ['nom', 'variation', 'updated_at']);
    }

    protected function syncMarketIndicator(?array $indicator): void
    {
        if (!$indicator)
            return;
        BocIndicator::updateOrCreate(
            ['date_rapport' => $indicator['date_rapport']],
            array_merge($indicator, ['updated_at' => now()])
        );
    }

    private function logErrorResponse(Response $response): void
    {
        Log::error("API Scraper Error", [
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
            'url' => $this->baseUrl
        ]);
    }
}
