<?php

namespace App\Services;

use App\Models\Action;
use App\Models\SectorFinancialMetric;
use App\Models\StockFinancialMetric;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SectorMetricAggregator
{
    /**
     * Calculer les métriques pour tous les secteurs BRVM pour une année donnée
     */
    public function calculateBrvmSectors(int $year): void
    {
        $brvmSectors = DB::table('brvm_sectors')->get();

        foreach ($brvmSectors as $sector) {
            $this->calculateSectorMetrics('brvm', $sector->id, $year, $sector->slug === 'services-financiers');
        }
    }

    /**
     * Calculer les métriques pour tous les secteurs classifiés pour une année donnée
     */
    public function calculateClassifiedSectors(int $year): void
    {
        $classifiedSectors = DB::table('classified_sectors')->get();

        foreach ($classifiedSectors as $sector) {
            // Déterminer si c'est un secteur financier (ajuster selon votre logique)
            $isFinancial = in_array($sector->slug, ['banques', 'assurances', 'services-financiers']);
            $this->calculateSectorMetrics('classified', $sector->id, $year, $isFinancial);
        }
    }

    /**
     * Calculer les métriques pour un secteur spécifique
     */
    public function calculateSectorMetrics(string $sectorType, int $sectorId, int $year, bool $isFinancialSector): void
    {
        // Récupérer toutes les actions du secteur
        $actions = $this->getActionsForSector($sectorType, $sectorId);

        if ($actions->isEmpty()) {
            return;
        }

        // Récupérer les métriques des actions pour l'année
        $metrics = StockFinancialMetric::whereIn('action_id', $actions->pluck('id'))
            ->where('year', $year)
            ->where('is_financial_sector', $isFinancialSector)
            ->get();

        if ($metrics->isEmpty()) {
            return;
        }

        $aggregatedData = [
            'sector_type' => $sectorType,
            'sector_id' => $sectorId,
            'year' => $year,
            'is_financial_sector' => $isFinancialSector,
            'companies_count' => $metrics->count(),
            'calculated_at' => now(),
        ];

        // Calculer les moyennes et écart-types pour chaque indicateur
        $indicators = $this->getIndicatorsList($isFinancialSector);

        foreach ($indicators as $indicator) {
            $values = $metrics->pluck($indicator)->filter(fn ($v) => $v !== null);

            if ($values->isNotEmpty()) {
                $aggregatedData[$indicator.'_moy'] = round($values->avg(), 2);
                $aggregatedData[$indicator.'_ecart_type'] = round($this->calculateStdDev($values), 2);
            }
        }

        // Sauvegarder les métriques sectorielles
        SectorFinancialMetric::updateOrCreate(
            [
                'sector_type' => $sectorType,
                'sector_id' => $sectorId,
                'year' => $year,
            ],
            $aggregatedData
        );
    }

    /**
     * Récupérer les actions d'un secteur
     */
    private function getActionsForSector(string $sectorType, int $sectorId): Collection
    {
        if ($sectorType === 'brvm') {
            return Action::where('brvm_sector_id', $sectorId)->get();
        } else {
            return Action::where('classified_sector_id', $sectorId)->get();
        }
    }

    /**
     * Liste des indicateurs à agréger selon le type de secteur
     */
    private function getIndicatorsList(bool $isFinancialSector): array
    {
        $common = [
            // Rentabilité
            'marge_nette',
            'marge_ebitda',
            'marge_operationnelle',
            'roe',
            'roa',
            'moy_rentabilite',

            // Rémunération
            'dnpa_calculated',
            'rendement_dividendes',
            'taux_distribution',
            'moy_remuneration',

            // Valorisation
            'per',
            'pbr',
            'ratio_ps',
            'ev_ebitda',
            'moy_valorisation',
        ];

        if ($isFinancialSector) {
            return array_merge($common, [
                // Croissance SF
                'croissance_pnb',
                'croissance_ebit_sf',
                'croissance_ebitda_sf',
                'croissance_rn_sf',
                'croissance_capex_sf',
                'moy_croissance_sf',

                // Solidité SF
                'autonomie_financiere',
                'ratio_prets_depots',
                'loan_to_deposit',
                'endettement_general_sf',
                'cout_du_risque_value',
                'moy_solidite_sf',
            ]);
        } else {
            return array_merge($common, [
                // Croissance AS
                'croissance_ca',
                'croissance_ebit_as',
                'croissance_ebitda_as',
                'croissance_rn_as',
                'croissance_capex_as',
                'moy_croissance_as',

                // Solidité AS
                'dette_capitalisation',
                'endettement_actif',
                'endettement_general_as',
                'moy_solidite_as',
            ]);
        }
    }

    /**
     * Calculer l'écart-type
     */
    private function calculateStdDev(Collection $values): float
    {
        if ($values->count() < 2) {
            return 0;
        }

        $mean = $values->avg();
        $variance = $values->map(fn ($v) => pow($v - $mean, 2))->avg();

        return sqrt($variance);
    }

    /**
     * Calculer les métriques pour une action et mettre à jour les secteurs
     */
    public function recalculateForAction(Action $action, int $year): void
    {
        // Recalculer les métriques sectorielles BRVM
        if ($action->brvm_sector_id) {
            $this->calculateSectorMetrics(
                'brvm',
                $action->brvm_sector_id,
                $year,
                $action->isFinancialService()
            );
        }

        // Recalculer les métriques sectorielles classifiées
        if ($action->classified_sector_id) {
            $this->calculateSectorMetrics(
                'classified',
                $action->classified_sector_id,
                $year,
                $action->isFinancialService()
            );
        }
    }
}
