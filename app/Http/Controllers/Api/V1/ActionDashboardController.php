<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;
use App\Models\Action;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActionHistoryResource;
use App\Http\Resources\ActionDashboardResource;

class ActionDashboardController extends Controller
{
     /**
     * Date de bascule annuelle (1er mars)
     * Avant ou égal au 1er mars : on utilise les données de N-2
     * À partir du 2 mars : on utilise les données de N-1
     */
    private const FISCAL_CUTOFF_MONTH = 3; //Mois on doit basculer dans les données de l'année en cours
    private const FISCAL_CUTOFF_DAY = 1;   // Jour du mois de bascule (Transition le 1er mars)
    /**
     * GET /api/actions/{key}/dashboard
     *
     * Retourne le dashboard avec gestion intelligente de l'année fiscale :
     * - Du 01/01 au 01/03 inclus : données de l'année N-2 (ex: en 2026 → 2024)
     * - Du 02/03 au 31/12 : données de l'année N-1 (ex: en 2026 → 2025)
     * - Données brutes (bilan, compte de résultat, indicateurs boursiers)
     * - Indicateurs calculés avec comparaisons sectorielles
     * - Gouvernance (actionnaires + employés)
     */
       public function dashboard(Action $action): JsonResponse
    {
        $action = $action->load([
            'brvmSector',
            'classifiedSector',
            'shareholders',
            'employees.position',
        ]);

        // Calcul de l'année de référence selon la date de bascule
        $referenceYear = $this->getReferenceYear();

        // Chargement des données financières de l'année de référence
        $action->load([
            'financials' => fn($q) => $q->where('year', $referenceYear)
                ->orderBy('year', 'desc')
        ]);

        return response()->json([
            'success' => true,
            'data' => new ActionDashboardResource($action, $referenceYear),
            'metadata' => [
                'reference_year' => $referenceYear,
                'current_date' => now()->toDateString(),
                'fiscal_period' => $this->getFiscalPeriodLabel(),
                'next_update_date' => $this->getNextUpdateDate(),
                'calculated_at' => now()->toIso8601String(),
                'data_source' => 'stock_financials',
                'calculation_version' => '1.0'
            ]
        ]);
    }

    /**
     * GET /api/actions/{id}/history
     *
     * Retourne l'historique complet sur 5 ans
     */
    public function history(Action $action): JsonResponse
    {
        $action = $action->load([
            'brvmSector',
            'classifiedSector',
        ]);

        $referenceYear = $this->getReferenceYear();

        // Génération des 5 dernières années à partir de l'année de référence
        $years = range($referenceYear, $referenceYear - 4);

        // Chargement des données financières pour ces années
        $action->load([
            'financials' => fn($q) => $q->whereIn('year', $years)
                ->orderBy('year', 'desc')
        ]);

        return response()->json([
            'success' => true,
            'data' => new ActionHistoryResource($action, $years),
            'metadata' => [
                'reference_year' => $referenceYear,
                'years_range' => [
                    'from' => $referenceYear - 4,
                    'to' => $referenceYear
                ],
                'years_available' => count($years),
                'fiscal_period' => $this->getFiscalPeriodLabel(),
                'calculated_at' => now()->toIso8601String(),
            ]
        ]);
    }


     /**
     * Détermine l'année de référence selon la date de bascule fiscale
     *
     * Logique :
     * - Avant le 01/03 inclus : année N-2 (en 2026, on affiche 2024)
     * - À partir du 02/03 : année N-1 (en 2026, on affiche 2025)
     *
     * @return int
     */
    private function getReferenceYear(): int
    {
        $now = now();
        $currentYear = $now->year;

        // Date de bascule de l'année en cours (1er mars à 23h59)
        $cutoffDate = Carbon::create(
            $currentYear,
            self::FISCAL_CUTOFF_MONTH,
            self::FISCAL_CUTOFF_DAY,
            23, 59, 59
        );

        // Si on est avant ou égal au 01 mars, on utilise l'année N-2
        if ($now->lessThanOrEqualTo($cutoffDate)) {
            return $currentYear - 2;
        }
        // À partir du 02 mars, on utilise l'année N-1
        return $currentYear - 1;
    }

    /**
     * Retourne un label explicatif de la période fiscale active
     *
     * @return string
     */
    private function getFiscalPeriodLabel(): string
    {
        $now = now();
        $referenceYear = $this->getReferenceYear();
        $currentYear = $now->year;
        $cutoffDate = Carbon::create($currentYear, self::FISCAL_CUTOFF_MONTH, self::FISCAL_CUTOFF_DAY);

        if ($now->lessThanOrEqualTo($cutoffDate)) {
            return sprintf(
                'Période de consolidation (données %d disponibles jusqu\'au 01/03/%d)',
                $referenceYear,
                $currentYear
            );
        }

        return sprintf(
            'Données financières %d (mises à jour depuis le 02/03/%d)',
            $referenceYear,
            $currentYear
        );
    }

    /**
     * Calcule la prochaine date de mise à jour des données
     *
     * @return string
     */
    private function getNextUpdateDate(): string{
        $now = now();
        $currentYear = $now->year;
        $nextCutoff = Carbon::create(
            $currentYear,
            self::FISCAL_CUTOFF_MONTH,
            self::FISCAL_CUTOFF_DAY + 1, // 02/03
            0, 0, 0
        );
        // Si on est déjà passé le 01 mars, la prochaine date est le 02 mars de l'année suivante
        if ($now->greaterThan($nextCutoff->copy()->subDay())) {
            $nextCutoff->addYear();
        }
        return $nextCutoff->toDateString();
    }

    /**
     * Méthode utilitaire pour vérifier si nous sommes en période de consolidation
     * Peut être utilisée pour afficher des avertissements dans l'interface
     *
     * @return bool
     */
    private function isConsolidationPeriod(): bool{
        $now = now();
        $currentYear = $now->year;
        $cutoffDate = Carbon::create($currentYear, self::FISCAL_CUTOFF_MONTH, self::FISCAL_CUTOFF_DAY, 23, 59, 59);
        return $now->lessThanOrEqualTo($cutoffDate);
    }
}
