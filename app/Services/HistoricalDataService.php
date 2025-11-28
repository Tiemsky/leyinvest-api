<?php

namespace App\Services;

use App\Models\Action;
use App\Models\StockFinancial;
use Illuminate\Support\Collection;

/**
 * Service pour les données historiques sur 4-5 ans
 * Correspond aux screenshots avec colonnes multi-années
 */
class HistoricalDataService
{
    public function __construct(
        private BenchmarkService $benchmarkService,
        private FinancialCacheService $cacheService
    ) {}

    /**
     * Récupère les données historiques complètes
     */
    public function getHistoricalData(
        Action $action,
        int $startYear,
        int $endYear,
        string $horizon = 'moyen_terme'
    ): array {
        return $this->cacheService->rememberHistorical(
            $action->key,
            $startYear,
            $endYear,
            $horizon,
            fn() => $this->buildHistoricalData($action, $startYear, $endYear, $horizon)
        );
    }

    /**
     * Construit les données historiques
     */
    private function buildHistoricalData(
        Action $action,
        int $startYear,
        int $endYear,
        string $horizon
    ): array {
        // Récupérer toutes les données financières
        $financials = StockFinancial::where('action_id', $action->id)
            ->whereBetween('year', [$startYear, $endYear])
            ->orderBy('year', 'desc')
            ->get()
            ->keyBy('year');

        if ($financials->isEmpty()) {
            return [
                'action' => $this->getActionInfo($action),
                'periode' => ['start_year' => $startYear, 'end_year' => $endYear],
                'error' => 'Aucune donnée disponible pour cette période',
            ];
        }

        // Calculer indicateurs pour chaque année
        $years = range($endYear, $startYear);
        $calculator = CalculatorFactory::make($action);
        $dataByYear = [];

        foreach ($years as $year) {
            $financial = $financials->get($year);
            $previousFinancial = $financials->get($year - 1);

            if ($financial) {
                $dataByYear[$year] = $calculator->calculate($financial, $previousFinancial);
            }
        }

        // Récupérer benchmarks pour chaque année
        $benchmarksByYear = $this->getBenchmarksByYear($action, $years, $horizon);

        // Structurer par catégorie
        $categories = $this->formatByCategory($dataByYear, $years, $benchmarksByYear);

        return [
            'action' => $this->getActionInfo($action),
            'periode' => [
                'start_year' => $startYear,
                'end_year' => $endYear,
                'years' => $years,
            ],
            'categories' => $categories,
        ];
    }

    /**
     * Récupère les benchmarks pour plusieurs années
     */
    private function getBenchmarksByYear(Action $action, array $years, string $horizon): array
    {
        $benchmarks = [];

        foreach ($years as $year) {
            $benchmarks[$year] = $this->benchmarkService->getBenchmarksForAction($action, $year, $horizon);
        }

        return $benchmarks;
    }

    /**
     * Formate les données par catégorie
     */
    private function formatByCategory(array $dataByYear, array $years, array $benchmarksByYear): array
    {
        $categories = ['croissance', 'rentabilite', 'remuneration', 'valorisation', 'solidite_financiere'];
        $result = [];

        foreach ($categories as $category) {
            $result[$category] = [
                'indicators' => $this->extractCategoryIndicators($category, $dataByYear, $years, $benchmarksByYear)
            ];
        }

        return $result;
    }

    /**
     * Extrait les indicateurs d'une catégorie
     */
    private function extractCategoryIndicators(
        string $category,
        array $dataByYear,
        array $years,
        array $benchmarksByYear
    ): array {
        // Collecter tous les codes d'indicateurs
        $indicatorCodes = $this->getIndicatorCodes($category, $dataByYear, $years);

        $indicators = [];

        foreach ($indicatorCodes as $code => $label) {
            $indicatorData = [
                'indicateur' => $label,
                'code' => $code,
            ];

            // Valeurs par année
            $values = [];
            foreach ($years as $year) {
                $yearData = $dataByYear[$year][$category][$code] ?? null;
                $indicatorData[(string)$year] = $yearData;

                if ($yearData && isset($yearData['valeur']) && !is_null($yearData['valeur'])) {
                    $values[] = $yearData['valeur'];
                }
            }

            // Calculer moyenne sur la période
            if (!empty($values)) {
                $moyenne = array_sum($values) / count($values);
                $ecartType = $this->calculateStandardDeviation($values);

                $indicatorData['moyenne'] = [
                    'valeur' => round($moyenne, 2),
                    'formatted' => $this->formatValue($moyenne, $code),
                ];

                $indicatorData['ecart_type'] = [
                    'valeur' => round($ecartType, 2),
                    'formatted' => $this->formatValue($ecartType, $code),
                ];
            } else {
                $indicatorData['moyenne'] = null;
                $indicatorData['ecart_type'] = null;
            }

            // Moyennes sectorielles (moyennées sur toutes les années)
            $brvmValues = [];
            $srValues = [];

            foreach ($years as $year) {
                if (isset($benchmarksByYear[$year]['brvm'][$category][$code])) {
                    $brvmValues[] = $benchmarksByYear[$year]['brvm'][$category][$code];
                }
                if (isset($benchmarksByYear[$year]['sr'][$category][$code])) {
                    $srValues[] = $benchmarksByYear[$year]['sr'][$category][$code];
                }
            }

            $indicatorData['moy_secteur_brvm'] = !empty($brvmValues)
                ? ['valeur' => round(array_sum($brvmValues) / count($brvmValues), 2), 'formatted' => $this->formatValue(array_sum($brvmValues) / count($brvmValues), $code)]
                : null;

            $indicatorData['moy_sr'] = !empty($srValues)
                ? ['valeur' => round(array_sum($srValues) / count($srValues), 2), 'formatted' => $this->formatValue(array_sum($srValues) / count($srValues), $code)]
                : null;

            $indicators[] = $indicatorData;
        }

        return $indicators;
    }

    /**
     * Récupère les codes d'indicateurs
     */
    private function getIndicatorCodes(string $category, array $dataByYear, array $years): array
    {
        foreach ($years as $year) {
            if (isset($dataByYear[$year][$category])) {
                $indicators = [];
                foreach ($dataByYear[$year][$category] as $code => $data) {
                    $indicators[$code] = $this->getIndicatorLabel($code);
                }
                return $indicators;
            }
        }
        return [];
    }

    /**
     * Labels des indicateurs
     */
    private function getIndicatorLabel(string $code): string
    {
        $labels = [
            'croissance_pnb' => 'PNB',
            'croissance_ca' => "Chiffre d'affaire",
            'croissance_rn' => 'RN (Résultat Net)',
            'croissance_ebit' => 'EBIT',
            'croissance_ebitda' => 'EBITDA',
            'croissance_capex' => 'CAPEX',
            'marge_nette' => 'Marge Nette',
            'marge_ebitda' => 'Marge EBITDA',
            'marge_operationnelle' => 'Marge Opérationnelle',
            'roe' => 'ROE',
            'roa' => 'ROA',
            'dnpa' => 'DNPA',
            'rendement_dividende' => 'Rendement Dvds',
            'taux_distribution' => 'Taux de distribution',
            'per' => 'PER',
            'pbr' => 'PBR',
            'ratio_ps' => 'P/S',
            'ev_ebitda' => 'EV/EBITDA',
            'cours_cible' => 'Cours Cible',
        ];

        return $labels[$code] ?? ucfirst(str_replace('_', ' ', $code));
    }

    /**
     * Formate une valeur
     */
    private function formatValue(?float $value, string $code): ?string
    {
        if (is_null($value)) return null;

        if (in_array($code, ['per', 'pbr', 'ratio_ps', 'ev_ebitda'])) {
            return number_format($value, 2) . 'x';
        }
        if (in_array($code, ['dnpa', 'cours_cible'])) {
            return number_format($value, 2) . ' FCFA';
        }
        return number_format($value, 2) . '%';
    }

    /**
     * Calcule l'écart-type
     */
    private function calculateStandardDeviation(array $values): float
    {
        if (count($values) < 2) return 0;

        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn($val) => pow($val - $mean, 2), $values)) / count($values);

        return sqrt($variance);
    }

    /**
     * Informations de l'action
     */
    private function getActionInfo(Action $action): array
    {
        return [
            'key' => $action->key,
            'symbole' => $action->symbole,
            'nom' => $action->nom,
            'brvm_sector' => $action->brvmSector->nom,
            'classified_sector' => $action->classifiedSector->nom,
        ];
    }
}
