<?php

namespace App\Services;

use App\Models\Action;
use App\Models\BrvmSector;
use App\Models\ClassifiedSector;
use App\Models\SectorBenchmark;
use App\Models\StockFinancial;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Service de calcul des benchmarks sectoriels
 *
 * Calcule 2 types de benchmarks:
 * 1. Secteur BRVM (brvm_sector_id)
 * 2. Secteur Reclassé (classified_sector_id)
 */
class BenchmarkService
{
    public function __construct(
        private CalculatorFactory $calculatorFactory,
        private FinancialCacheService $cacheService
    ) {}

    /**
     * Calcule et sauvegarde les benchmarks pour tous les secteurs
     */
    public function calculateAllBenchmarks(int $year): int
    {
        $count = 0;
        $horizons = config('financial_indicators.horizons', ['court_terme', 'moyen_terme', 'long_terme']);

        // 1. Benchmarks Secteur BRVM
        $brvmSectors = BrvmSector::all();
        foreach ($brvmSectors as $sector) {
            foreach ($horizons as $horizon) {
                $this->calculateAndStoreSectorBenchmark($sector, $year, $horizon, 'brvm');
                $count++;
            }
        }

        // 2. Benchmarks Secteur Reclassé
        $classifiedSectors = ClassifiedSector::all();
        foreach ($classifiedSectors as $sector) {
            foreach ($horizons as $horizon) {
                $this->calculateAndStoreSectorBenchmark($sector, $year, $horizon, 'classified');
                $count++;
            }
        }

        // Invalider cache
        $this->cacheService->forgetBenchmarks($year);

        return $count;
    }

    /**
     * Calcule et sauvegarde le benchmark d'un secteur
     */
    public function calculateAndStoreSectorBenchmark(
        BrvmSector|ClassifiedSector $sector,
        int $year,
        string $horizon,
        string $sectorType = 'brvm'
    ): void {
        // Récupérer les actions du secteur avec données
        $actions = $this->getActionsWithFinancials($sector, $year, $sectorType);

        if ($actions->isEmpty()) {
            Log::info("Aucune action avec données pour {$sector->nom} en {$year}");
            return;
        }

        // Minimum 3 actions pour un benchmark valide
        $minActions = config('financial_indicators.benchmarks.min_actions', 3);
        if ($actions->count() < $minActions) {
            Log::warning("Pas assez d'actions ({$actions->count()}) pour {$sector->nom} en {$year}");
            return;
        }

        // Calculer les indicateurs pour toutes les actions
        $allIndicators = $this->collectIndicators($actions, $year);

        if ($allIndicators->isEmpty()) {
            Log::warning("Aucun indicateur calculé pour {$sector->nom} en {$year}");
            return;
        }

        // Calculer moyennes et écarts-types
        $benchmarks = $this->calculateBenchmarkStatistics($allIndicators);

        // Sauvegarder
        $this->storeBenchmark($sector, $year, $horizon, $sectorType, $benchmarks);

        Log::info("✅ Benchmark calculé: {$sector->nom} - {$horizon} ({$actions->count()} actions)");
    }

    /**
     * Récupère les actions d'un secteur avec données financières
     */
    private function getActionsWithFinancials(
        BrvmSector|ClassifiedSector $sector,
        int $year,
        string $sectorType
    ): Collection {
        $query = Action::query();

        if ($sectorType === 'brvm') {
            $query->where('brvm_sector_id', $sector->id);
        } else {
            $query->where('classified_sector_id', $sector->id);
        }

        return $query->whereHas('stockFinancials', function ($q) use ($year) {
            $q->where('year', $year);
        })->with(['stockFinancials' => function ($q) use ($year) {
            $q->where('year', $year)
              ->orWhere('year', $year - 1);
        }, 'brvmSector'])->get();
    }

    /**
     * Collecte les indicateurs de toutes les actions
     */
    private function collectIndicators(Collection $actions, int $year): Collection
    {
        return $actions->map(function ($action) use ($year) {
            try {
                // Récupérer données financières année N
                $financial = $action->stockFinancials->firstWhere('year', $year);

                if (!$financial) {
                    return null;
                }

                // Récupérer année N-1 pour croissance
                $previousFinancial = $action->stockFinancials->firstWhere('year', $year - 1);

                // Créer calculateur approprié
                $calculator = CalculatorFactory::make($action);

                // Calculer indicateurs
                return $calculator->calculate($financial, $previousFinancial);

            } catch (\Exception $e) {
                Log::warning("Erreur calcul indicateurs pour {$action->symbole}: {$e->getMessage()}");
                return null;
            }
        })->filter();
    }

    /**
     * Calcule les statistiques (moyennes et écarts-types)
     */
    private function calculateBenchmarkStatistics(Collection $allIndicators): array
    {
        $categories = ['croissance', 'rentabilite', 'remuneration', 'valorisation', 'solidite_financiere'];
        $statistics = [];

        foreach ($categories as $category) {
            $categoryData = $this->extractCategoryValues($allIndicators, $category);

            $statistics[$category . '_avg'] = $this->calculateAverages($categoryData);
            $statistics[$category . '_std'] = $this->calculateStandardDeviations($categoryData);
        }

        return $statistics;
    }

    /**
     * Extrait les valeurs d'une catégorie
     */
    private function extractCategoryValues(Collection $allIndicators, string $category): array
    {
        $values = [];

        foreach ($allIndicators as $indicators) {
            if (!isset($indicators[$category])) {
                continue;
            }

            foreach ($indicators[$category] as $indicatorName => $data) {
                if (!isset($values[$indicatorName])) {
                    $values[$indicatorName] = [];
                }

                $value = is_array($data) ? ($data['valeur'] ?? null) : $data;

                if (!is_null($value)) {
                    $values[$indicatorName][] = $value;
                }
            }
        }

        return $values;
    }

    /**
     * Calcule les moyennes
     */
    private function calculateAverages(array $categoryData): array
    {
        $averages = [];

        foreach ($categoryData as $indicator => $values) {
            if (!empty($values)) {
                $averages[$indicator] = array_sum($values) / count($values);
            }
        }

        return $averages;
    }

    /**
     * Calcule les écarts-types
     */
    private function calculateStandardDeviations(array $categoryData): array
    {
        $stds = [];

        foreach ($categoryData as $indicator => $values) {
            if (count($values) < 2) {
                continue;
            }

            $mean = array_sum($values) / count($values);
            $variance = array_sum(array_map(function($val) use ($mean) {
                return pow($val - $mean, 2);
            }, $values)) / count($values);

            $stds[$indicator] = sqrt($variance);
        }

        return $stds;
    }

    /**
     * Sauvegarde le benchmark en base
     */
    private function storeBenchmark(
        BrvmSector|ClassifiedSector $sector,
        int $year,
        string $horizon,
        string $sectorType,
        array $benchmarks
    ): void {
        $type = $sectorType === 'brvm' ? 'secteur_brvm' : 'secteur_reclasse';

        $data = [
            'year' => $year,
            'horizon' => $horizon,
            'type' => $type,
            'croissance_avg' => $benchmarks['croissance_avg'] ?? null,
            'croissance_std' => $benchmarks['croissance_std'] ?? null,
            'rentabilite_avg' => $benchmarks['rentabilite_avg'] ?? null,
            'rentabilite_std' => $benchmarks['rentabilite_std'] ?? null,
            'remuneration_avg' => $benchmarks['remuneration_avg'] ?? null,
            'remuneration_std' => $benchmarks['remuneration_std'] ?? null,
            'valorisation_avg' => $benchmarks['valorisation_avg'] ?? null,
            'valorisation_std' => $benchmarks['valorisation_std'] ?? null,
            'solidite_avg' => $benchmarks['solidite_financiere_avg'] ?? null,
            'solidite_std' => $benchmarks['solidite_financiere_std'] ?? null,
            'calculated_at' => now(),
        ];

        if ($sectorType === 'brvm') {
            $data['brvm_sector_id'] = $sector->id;
            $data['classified_sector_id'] = null;
        } else {
            $data['brvm_sector_id'] = null;
            $data['classified_sector_id'] = $sector->id;
        }

        SectorBenchmark::updateOrCreate(
            [
                'brvm_sector_id' => $data['brvm_sector_id'],
                'classified_sector_id' => $data['classified_sector_id'],
                'year' => $year,
                'horizon' => $horizon,
                'type' => $type,
            ],
            $data
        );
    }

    /**
     * Récupère les benchmarks pour une action
     */
    public function getBenchmarksForAction(Action $action, int $year, string $horizon): array
    {
        return [
            'brvm' => $this->getBenchmarkBrvm($action->brvm_sector_id, $year, $horizon),
            'sr' => $this->getBenchmarkSR($action->classified_sector_id, $year, $horizon),
        ];
    }

    /**
     * Récupère le benchmark secteur BRVM
     */
    private function getBenchmarkBrvm(int $sectorId, int $year, string $horizon): ?array
    {
        return $this->cacheService->rememberBenchmarksBrvm($sectorId, $year, $horizon, function () use ($sectorId, $year, $horizon) {
            $benchmark = SectorBenchmark::where('type', 'secteur_brvm')
                ->where('brvm_sector_id', $sectorId)
                ->where('year', $year)
                ->where('horizon', $horizon)
                ->first();

            return $benchmark ? $this->formatBenchmark($benchmark) : null;
        });
    }

    /**
     * Récupère le benchmark secteur reclassé
     */
    private function getBenchmarkSR(int $sectorId, int $year, string $horizon): ?array
    {
        return $this->cacheService->rememberBenchmarksSR($sectorId, $year, $horizon, function () use ($sectorId, $year, $horizon) {
            $benchmark = SectorBenchmark::where('type', 'secteur_reclasse')
                ->where('classified_sector_id', $sectorId)
                ->where('year', $year)
                ->where('horizon', $horizon)
                ->first();

            return $benchmark ? $this->formatBenchmark($benchmark) : null;
        });
    }

    /**
     * Formate un benchmark
     */
    private function formatBenchmark(SectorBenchmark $benchmark): array
    {
        return [
            'croissance' => $benchmark->croissance_avg ?? [],
            'rentabilite' => $benchmark->rentabilite_avg ?? [],
            'remuneration' => $benchmark->remuneration_avg ?? [],
            'valorisation' => $benchmark->valorisation_avg ?? [],
            'solidite_financiere' => $benchmark->solidite_avg ?? [],
        ];
    }
}
