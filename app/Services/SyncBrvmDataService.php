<?php

namespace App\Services;

use App\Models\Action;
use App\Models\BrvmSector;
use Illuminate\Support\Str;
use App\Models\BocIndicator;
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
        // On utilise config() avec des valeurs par défaut pour éviter les RuntimeException au boot
        $this->baseUrl = rtrim(config('services.scraper.url', 'http://brvm_api_local:8000'), '/');
        $this->token = config('services.scraper.webhook_token');
    }

    public function syncAllData(): bool
    {
        if (empty($this->token)) {
            Log::error("❌ Config manquante : FASTAPI_WEBHOOK_TOKEN");
            return false;
        }

        Log::info("🔄 Démarrage de la synchronisation BRVM depuis {$this->baseUrl}");

        try {
            $response = Http::withHeaders([
                'X-Webhook-Token' => $this->token, // Nom cohérent avec votre middleware
                'Accept' => 'application/json',
            ])
            ->timeout(30) // 30s est souvent suffisant, 60s peut bloquer un worker trop longtemps
            ->retry(3, 100) // 🔄 AJOUT : Réessaie 3 fois en cas d'erreur réseau (micro-coupure Docker)
            ->get("{$this->baseUrl}/api/sync/all-data");

            if ($response->failed()) {
                $this->logErrorResponse($response);
                return false;
            }

            $payload = $response->json('payload');

            if (!is_array($payload)) {
                throw new \Exception("Le format du payload reçu est invalide.");
            }

            // Utilisation d'une transaction pour garantir l'intégrité des données
            DB::transaction(function () use ($payload) {
                $this->syncActions($payload['actions'] ?? []);
                $this->syncIndices($payload['indices'] ?? []);
                $this->syncMarketIndicator($payload['indicateur_marche'] ?? null);
            });

            Log::info("✅ Synchronisation BRVM terminée avec succès.");
            return true;

        } catch (\Exception $e) {
            Log::error("❌ Erreur critique lors de la synchro BRVM : " . $e->getMessage());
            return false;
        }
    }

    protected function syncActions(array $actions): void
    {
        if (empty($actions)) return;

        $now = now();
        $records = [];

        foreach ($actions as $item) {
            $nom = $item['nom'] ?? 'Inconnu';
            $records[] = [
                'symbole'         => $item['symbole'],
                'nom'             => $nom,
                'cours_ouverture' => $item['cours_ouverture'] ?? 0,
                'cours_cloture'   => $item['cours_cloture'] ?? 0,
                'cours_veille'    => $item['cours_veille'] ?? 0,
                'variation'       => $item['variation'] ?? 0,
                'volume'          => $item['volume'] ?? 0,
                'updated_at'      => $now, // Pour Laravel
            ];
        }

        // Upsert par paquets de 100 pour la stabilité
        foreach (array_chunk($records, 100) as $chunk) {
            Action::upsert($chunk, ['symbole'], [
                'nom', 'cours_ouverture', 'cours_cloture',
                'cours_veille', 'variation', 'volume', 'updated_at'
            ]);
        }
    }

    protected function syncIndices(array $indices): void
    {
        if (empty($indices)) return;

        $now = now();
        $records = array_map(fn($item) => [
            'slug'         => $item['slug'],
            'nom'          => $item['nom'],
            'variation'    => $item['variation'] ?? 0,
            'updated_at'   => $now,
        ], $indices);

        BrvmSector::upsert($records, ['slug'], ['nom', 'variation', 'updated_at']);
    }

    protected function syncMarketIndicator(?array $indicator): void
    {
        if (!$indicator || empty($indicator['date_rapport'])) return;

        BocIndicator::updateOrCreate(
            ['date_rapport' => $indicator['date_rapport']],
            [
                'per_moyen'              => $indicator['per_moyen'] ?? null,
                'taux_rendement_moyen'   => $indicator['taux_rendement_moyen'] ?? null,
                'taux_rentabilite_moyen' => $indicator['taux_rentabilite_moyen'] ?? null,
                'prime_risque_marche'    => $indicator['prime_risque_marche'] ?? null,
                'source_pdf'             => $indicator['source_pdf'] ?? null,
                'updated_at'             => now(),
            ]
        );
    }

    private function logErrorResponse(Response $response): void
    {
        Log::error("API Scraper Error", [
            'status' => $response->status(),
            'body'   => $response->json() ?? $response->body(),
            'url'    => $this->baseUrl
        ]);
    }
}
