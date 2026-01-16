<?php

namespace App\Services;

use App\Models\Action;
use App\Models\BrvmSector;
use App\Models\BocIndicator;
use Illuminate\Support\Str;
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
            Log::error("❌ Configuration manquante : FASTAPI_WEBHOOK_TOKEN");
            return false;
        }

        Log::info("🔄 Démarrage de la synchronisation BRVM depuis {$this->baseUrl}");

        try {
            $response = Http::withHeaders([
                'X-Webhook-Token' => $this->token,
                'Accept'          => 'application/json',
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

            Log::info("✅ Synchronisation BRVM terminée avec succès.");
            return true;

        } catch (\Exception $e) {
            Log::error("❌ Erreur critique lors de la synchro BRVM : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Synchronisation des actions avec logique Upsert
     */
    protected function syncActions(array $actions): void
    {
        if (empty($actions)) return;

        $now = now();
        // Récupération des IDs de secteurs existants pour le mappage
        $sectors = BrvmSector::pluck('id', 'slug')->toArray();

        // On définit un ID par défaut si le secteur n'est pas trouvé (ex: secteur 1)
        $defaultSectorId = !empty($sectors) ? reset($sectors) : 1;

        $records = [];
        foreach ($actions as $item) {
            $symbole = $item['symbole'];

            $records[] = [
                // Colonnes obligatoires pour l'insertion (PostgreSQL NOT NULL)
                'key'                  => 'act_' . strtolower($symbole),
                'symbole'              => $symbole,
                'nom'                  => $item['nom'] ?? 'Inconnu',
                'brvm_sector_id'       => $sectors[$item['sector_slug'] ?? ''] ?? $defaultSectorId,
                'classified_sector_id' => 1, // Fixe selon votre besoin
                'created_at'           => $now,
                'updated_at'           => $now,

                // Données variables qui seront mises à jour
                'cours_ouverture'      => $item['cours_ouverture'] ?? 0,
                'cours_cloture'        => $item['cours_cloture'] ?? 0,
                'cours_veille'         => $item['cours_veille'] ?? 0,
                'variation'            => $item['variation'] ?? 0,
                'volume'               => (string) ($item['volume'] ?? '0'),
            ];
        }

        // Upsert :
        // Arg 1: Données complètes
        // Arg 2: Colonne unique pour détecter le conflit
        // Arg 3: Colonnes à mettre à jour UNIQUEMENT si le symbole existe déjà
        foreach (array_chunk($records, 100) as $chunk) {
            Action::upsert($chunk, ['symbole'], [
                'cours_ouverture',
                'cours_cloture',
                'cours_veille',
                'variation',
                'volume',
                'updated_at'
            ]);
        }

        Log::debug(count($actions) . " actions traitées.");
    }

    /**
     * Synchronisation des secteurs (indices)
     */
    protected function syncIndices(array $indices): void
    {
        if (empty($indices)) return;

        $now = now();
        $records = array_map(fn($item) => [
            'slug'       => $item['slug'],
            'nom'        => $item['nom'],
            'variation'  => $item['variation'] ?? 0,
            'created_at' => $now,
            'updated_at' => $now,
        ], $indices);

        BrvmSector::upsert($records, ['slug'], ['nom', 'variation', 'updated_at']);
    }

    /**
     * Mise à jour des indicateurs de marché
     */
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
