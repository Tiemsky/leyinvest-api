<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActionDashboardResource;
use App\Http\Resources\ActionHistoryResource;
use App\Models\Action;
use Illuminate\Http\JsonResponse;

class ActionDashboardController extends Controller
{
    /**
     * GET /api/actions/{key}/dashboard
     *
     * Retourne le dashboard de l'année N-1 avec :
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
            'financials' => fn($q) => $q->orderBy('year', 'desc')
        ]);

        // Année N-1
        $year = now()->year - 1;

        // Eager load des métriques et des données sectorielles
        $action->load([
            'financials' => fn($q) => $q->where('year', $year)->first()
        ]);

        return response()->json([
            'success' => true,
            'data' => new ActionDashboardResource($action, $year),
            'metadata' => [
                'year' => $year,
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
            'financials' => fn($q) => $q->orderBy('year', 'desc')->limit(5)
        ]);

        $currentYear = now()->year - 1;
        $years = range($currentYear, $currentYear - 4);

        return response()->json([
            'success' => true,
            'data' => new ActionHistoryResource($action, $years),
            'metadata' => [
                'years_available' => 5,
                'calculated_at' => now()->toIso8601String(),
            ]
        ]);
    }
}
