<?php

namespace App\Services;

use App\Models\Action;
use App\Models\StockFinancial;

/**
 * Service de préparation des données pour le dashboard
 *
 * Enrichit les indicateurs calculés avec:
 * - Benchmarks secteur BRVM
 * - Benchmarks secteur reclassé (SR)
 * - Données de présentation
 */
class DashboardDataService
{
    public function __construct(
        private BenchmarkService $benchmarkService,
        private FinancialCacheService $cacheService
    ) {}

    /**
     * Récupère toutes les données du dashboard pour une action
     */
    public function getDashboardData(Action $action, int $year, string $horizon): array
    {
        return $this->cacheService->rememberDashboard(
            $action->key,
            $year,
            $horizon,
            fn() => $this->buildDashboardData($action, $year, $horizon)
        );
    }

    /**
     * Construit les données du dashboard
     */
    private function buildDashboardData(Action $action, int $year, string $horizon): array
    {
        // 1. Récupérer données financières
        $financial = StockFinancial::where('action_id', $action->id)
            ->where('year', $year)
            ->first();

        if (!$financial) {
            return [
                'action' => $this->getActionInfo($action),
                'year' => $year,
                'horizon' => $horizon,
                'error' => "Aucune donnée financière pour l'année {$year}",
            ];
        }

        $previousFinancial = StockFinancial::where('action_id', $action->id)
            ->where('year', $year - 1)
            ->first();

        // 2. Calculer indicateurs
        $calculator = CalculatorFactory::make($action);
        $indicators = $calculator->calculate($financial, $previousFinancial);

        // 3. Récupérer benchmarks
        $benchmarks = $this->benchmarkService->getBenchmarksForAction($action, $year, $horizon);

        // 4. Enrichir avec benchmarks
        $enrichedIndicators = $this->enrichIndicatorsWithBenchmarks($indicators, $benchmarks);

        // 5. Assembler réponse complète
        return [
            'action' => $this->getActionInfo($action),
            'year' => $year,
            'horizon' => $horizon,
            'presentation' => $this->getPresentationData($financial),
            'bilan' => $this->getBilanData($financial),
            'compte_resultat' => $this->getCompteResultatData($financial),
            'indicateurs' => $enrichedIndicators,
            'sectorType' => CalculatorFactory::getSectorType($action),
        ];
    }

    /**
     * Enrichit les indicateurs avec les benchmarks
     */
    private function enrichIndicatorsWithBenchmarks(array $indicators, array $benchmarks): array
    {
        $enriched = [];

        foreach ($indicators as $category => $categoryIndicators) {
            $enriched[$category] = [];

            foreach ($categoryIndicators as $indicatorName => $indicatorData) {
                $enriched[$category][$indicatorName] = [
                    'valeur' => $indicatorData['valeur'] ?? null,
                    'formatted' => $indicatorData['formatted'] ?? null,
                    'moy_secteur_brvm' => $benchmarks['brvm'][$category][$indicatorName] ?? null,
                    'moy_sr' => $benchmarks['sr'][$category][$indicatorName] ?? null,
                ];
            }
        }

        return $enriched;
    }

    /**
     * Informations de l'action
     */
    private function getActionInfo(Action $action): array
    {
        return [
            'id' => $action->id,
            'key' => $action->key,
            'symbole' => $action->symbole,
            'nom' => $action->nom,
            'description' => $action->description,
            'brvm_sector' => [
                'id' => $action->brvmSector->id,
                'nom' => $action->brvmSector->nom,
                'slug' => $action->brvmSector->slug,
            ],
            'classified_sector' => [
                'id' => $action->classifiedSector->id,
                'nom' => $action->classifiedSector->nom,
                'slug' => $action->classifiedSector->slug,
            ],
            'cours_actuel' => [
                'cours_cloture' => $action->cours_cloture,
                'variation' => $action->variation,
            ],
        ];
    }

    /**
     * Données de présentation
     */
    private function getPresentationData(StockFinancial $financial): array
    {
        return [
            'nombre_titre' => $financial->nombre_titre,
            'cours_31_12' => $financial->cours_31_12,
            'capitalisation' => $financial->capitalisation,
            'per' => $financial->per,
            'dnpa' => $financial->dnpa,
        ];
    }

    /**
     * Données du bilan
     */
    private function getBilanData(StockFinancial $financial): array
    {
        $data = [
            'actif' => [
                'total_immobilisation' => $financial->total_immobilisation,
                'actif_circulant' => $financial->actif_circulant,
                'total_actif' => $financial->total_actif,
            ],
            'passif' => [
                'capitaux_propres' => $financial->capitaux_propres,
                'passif_circulant' => $financial->passif_circulant,
                'dette_totale' => $financial->dette_totale,
            ],
        ];

        // Ajouter données spécifiques services financiers
        if ($financial->isFinancialService()) {
            $data['actif']['credits_clientele'] = $financial->credits_clientele;
            $data['passif']['depots_clientele'] = $financial->depots_clientele;
        }

        return $data;
    }

    /**
     * Données du compte de résultat
     */
    private function getCompteResultatData(StockFinancial $financial): array
    {
        $data = [
            'ebitda' => $financial->ebitda,
            'ebit' => $financial->ebit,
            'resultat_avant_impot' => $financial->resultat_avant_impot,
            'resultat_net' => $financial->resultat_net,
            'capex' => $financial->capex,
            'dividendes_bruts' => $financial->dividendes_bruts,
        ];

        if ($financial->isFinancialService()) {
            $data['produit_net_bancaire'] = $financial->produit_net_bancaire;
            $data['cout_du_risque'] = $financial->cout_du_risque;
        } else {
            $data['chiffre_affaires'] = $financial->chiffre_affaires;
            $data['valeur_ajoutee'] = $financial->valeur_ajoutee;
        }

        return $data;
    }
}
