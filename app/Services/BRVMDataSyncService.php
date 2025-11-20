<?php

namespace App\Services;

use App\Models\Action;
use App\Models\ActionDailySnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class BRVMDataSyncService
{
    protected string $fastApiUrl;
    protected int $maxHistoryDays = 10;

    public function __construct()
    {
        $this->fastApiUrl = config('services.brvm.fastapi_url');
    }

    /**
     * Méthode principale: synchronise depuis l'API FastAPI
     *
     * @return array Statistiques de synchronisation
     */
    public function syncFromFastAPI(): array
    {
        Log::info('🚀 Démarrage de la synchronisation BRVM depuis FastAPI');

        $stats = [
            'actions_updated' => 0,
            'snapshots_created' => 0,
            'snapshots_deleted' => 0,
            'errors' => 0,
        ];

        DB::beginTransaction();

        try {
            // 1. Récupération des données depuis FastAPI
            $actionsData = $this->fetchFromFastAPI();

            if (empty($actionsData)) {
                throw new \Exception('Aucune donnée reçue de FastAPI');
            }

            Log::info("📥 {count($actionsData)} actions reçues de FastAPI");

            $today = Carbon::today();

            // 2. Traitement de chaque action
            foreach ($actionsData as $actionData) {
                try {
                    // Mise à jour de la table `actions` (état actuel)
                    $action = $this->updateOrCreateAction($actionData);
                    $stats['actions_updated']++;

                    // Création du snapshot quotidien
                    $this->createDailySnapshot($action, $actionData, $today);
                    $stats['snapshots_created']++;

                } catch (\Exception $e) {
                    Log::error("❌ Erreur pour {$actionData['symbole']}: {$e->getMessage()}");
                    $stats['errors']++;
                    continue;
                }
            }

            // 3. Rotation: suppression des snapshots > 10 jours
            $deletedCount = $this->rotateOldSnapshots();
            $stats['snapshots_deleted'] = $deletedCount;

            DB::commit();

            Log::info('✅ Synchronisation terminée', $stats);
            return $stats;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("💥 Erreur globale de synchronisation: {$e->getMessage()}");
            $stats['errors']++;
            return $stats;
        }
    }

    /**
     * Récupère les données depuis l'API FastAPI
     */
    protected function fetchFromFastAPI(): array
    {
        try {
            $response = Http::timeout(30)
                ->retry(3, 1000) // 3 tentatives, 1s entre chaque
                ->get($this->fastApiUrl . '/actions/scrape'); // Endpoint de scraping

            if (!$response->successful()) {
                throw new \Exception("API FastAPI erreur: {$response->status()}");
            }

            $data = $response->json();

            // Valide la structure de la réponse
            if (!isset($data['actions']) || !is_array($data['actions'])) {
                throw new \Exception('Format de réponse invalide');
            }

            return $data['actions'];

        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération depuis FastAPI: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Met à jour ou crée une action dans la table `actions`
     */
    protected function updateOrCreateAction(array $data): Action
    {
        // Validation des données essentielles
        if (empty($data['symbole'])) {
            throw new \Exception('Symbole manquant');
        }

        return Action::updateOrCreate(
            ['symbole' => $data['symbole']], // Critère de recherche
            [
                'nom' => $data['nom'] ?? '',
                'volume' => $data['volume'] ?? 0,
                'cours_veille' => $data['cours_veille'] ?? null,
                'cours_ouverture' => $data['cours_ouverture'] ?? null,
                'cours_cloture' => $data['cours_cloture'] ?? 0,
                'variation' => $data['variation'] ?? null,
                // key, sectors, description ne sont pas mis à jour ici (gérés ailleurs)
            ]
        );
    }

    /**
     * Crée un snapshot quotidien
     */
    protected function createDailySnapshot(Action $action, array $data, Carbon $date): ActionDailySnapshot
    {
        return ActionDailySnapshot::updateOrCreate(
            [
                'action_id' => $action->id,
                'snapshot_date' => $date,
            ],
            [
                'symbole' => $action->symbole,
                'nom' => $data['nom'] ?? $action->nom,
                'volume' => $data['volume'] ?? 0,
                'cours_veille' => $data['cours_veille'] ?? null,
                'cours_ouverture' => $data['cours_ouverture'] ?? null,
                'cours_cloture' => $data['cours_cloture'] ?? 0,
                'variation' => $data['variation'] ?? null,
            ]
        );
    }

    /**
     * Rotation: supprime les snapshots de plus de 10 jours
     *
     * @return int Nombre de snapshots supprimés
     */
    protected function rotateOldSnapshots(): int
    {
        $cutoffDate = Carbon::today()->subDays($this->maxHistoryDays);

        $deletedCount = ActionDailySnapshot::where('snapshot_date', '<', $cutoffDate)
            ->delete();

        if ($deletedCount > 0) {
            Log::info("🗑️ {$deletedCount} snapshots supprimés (> {$this->maxHistoryDays} jours)");
        }

        return $deletedCount;
    }

    /**
     * Nettoie les snapshots orphelins (actions supprimées)
     */
    public function cleanOrphanedSnapshots(): int
    {
        $deletedCount = DB::table('action_daily_snapshots')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('actions')
                    ->whereColumn('actions.id', 'action_daily_snapshots.action_id');
            })
            ->delete();

        if ($deletedCount > 0) {
            Log::info("🧹 {$deletedCount} snapshots orphelins nettoyés");
        }

        return $deletedCount;
    }

    /**
     * Récupère l'évolution d'une action sur N jours
     */
    public function getActionEvolution(string $symbole, int $days = 10): array
    {
        $snapshots = ActionDailySnapshot::forSymbol($symbole)
            ->lastDays($days)
            ->orderBy('snapshot_date', 'asc')
            ->get();

        return $snapshots->map(function ($snapshot) {
            return [
                'date' => $snapshot->snapshot_date->format('Y-m-d'),
                'cours' => $snapshot->cours_cloture,
                'volume' => $snapshot->volume,
                'variation' => $snapshot->variation,
            ];
        })->toArray();
    }

    /**
     * Calcule les statistiques sur 10 jours pour une action
     */
    public function getActionStats(string $symbole): array
    {
        $snapshots = ActionDailySnapshot::forSymbol($symbole)
            ->lastDays(10)
            ->orderBy('snapshot_date', 'asc')
            ->get();

        if ($snapshots->isEmpty()) {
            return [];
        }

        $cours = $snapshots->pluck('cours_cloture')->filter();
        $volumes = $snapshots->pluck('volume')->filter();

        return [
            'symbole' => $symbole,
            'periode' => [
                'debut' => $snapshots->first()->snapshot_date->format('Y-m-d'),
                'fin' => $snapshots->last()->snapshot_date->format('Y-m-d'),
            ],
            'cours' => [
                'min' => $cours->min(),
                'max' => $cours->max(),
                'moyen' => round($cours->avg(), 2),
                'debut' => $snapshots->first()->cours_cloture,
                'fin' => $snapshots->last()->cours_cloture,
                'variation_totale' => $this->calculateTotalVariation(
                    $snapshots->first()->cours_cloture,
                    $snapshots->last()->cours_cloture
                ),
            ],
            'volume' => [
                'total' => $volumes->sum(),
                'moyen' => round($volumes->avg(), 0),
                'max' => $volumes->max(),
            ],
            'jours_disponibles' => $snapshots->count(),
        ];
    }

    /**
     * Calcule la variation totale en %
     */
    protected function calculateTotalVariation(?float $debut, ?float $fin): ?float
    {
        if (!$debut || !$fin || $debut == 0) {
            return null;
        }

        return round((($fin - $debut) / $debut) * 100, 2);
    }
  }
