<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Action;
use App\Services\FiscalYearService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActionHistoryResource;
use App\Http\Resources\ActionDashboardResource;

/**
 * @tags Tableau de bord des actions
 */
class ActionDashboardController extends Controller
{

    public function __construct(
        private readonly FiscalYearService $fiscalYearService
    ) {}

    /**
     * GET /api/actions/{key}/dashboard
     *
     * Retourne le dashboard avec gestion intelligente de l'année fiscale :
     * - Du 01/01 au 01/03 inclus : données de l'année N-2 (ex: en 2026 → 2024)
     * - Du 02/03 au 31/12 : données de l'année N-1 (ex: en 2026 → 2025)
     */
    public function dashboard(Action $action): JsonResponse
    {
        // Chargement des relations nécessaires
        $action->load([
            'brvmSector',
            'classifiedSector',
            'shareholders',
            'employees.position',
        ]);

        // Récupération de l'année de référence via le service
        $referenceYear = $this->fiscalYearService->getReferenceYear();

        // Chargement des données financières de l'année de référence
        $action->load([
            'financials' => fn($q) => $q->where('year', $referenceYear)
                ->orderBy('year', 'desc')
        ]);

        return response()->json([
            'success' => true,
            'data' => new ActionDashboardResource($action, $referenceYear),
            'metadata' => array_merge(
                $this->fiscalYearService->getMetadata(),
                [
                    'calculated_at' => now()->toIso8601String(),
                    'data_source' => 'stock_financials',
                    'calculation_version' => '1.0'
                ]
            )
        ]);
    }

    /**
     * GET /api/actions/{id}/history
     *
     * Retourne l'historique sur 5 ans à partir de l'année de référence
     */
    public function history(Action $action): JsonResponse
    {
        $action->load([
            'brvmSector',
            'classifiedSector',
        ]);

        // Génération des 5 dernières années via le service
        $years = $this->fiscalYearService->getHistoricalYears(5);

        // Chargement des données financières pour ces années
        $action->load([
            'financials' => fn($q) => $q->whereIn('year', $years)
                ->orderBy('year', 'desc')
        ]);

        return response()->json([
            'success' => true,
            'data' => new ActionHistoryResource($action, $years),
            'metadata' => [
                'years_range' => [
                    'from' => min($years),
                    'to' => max($years)
                ],
                'years_available' => count($years),
                'fiscal_period' => $this->fiscalYearService->getFiscalPeriodLabel(),
                'calculated_at' => now()->toIso8601String(),
            ]
        ]);
    }
}
