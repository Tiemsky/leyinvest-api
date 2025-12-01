<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardActionResource;
use App\Http\Resources\HistoricalActionResource;
use App\Models\Action;
use App\Services\IndicatorOrchestrator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller API pour les indicateurs financiers
 *
 * Routes protégées par Sanctum
 * Utilise le 'key' de l'action (pas l'id)
 */
class FinancialIndicatorController extends Controller
{
    public function __construct(
        private IndicatorOrchestrator $orchestrator
    ) { }

    public function test(){
        return response()->json(['message' => 'API FinancialIndicatorController is working']);
    }

    /**
     * GET /api/actions/{action:key}/dashboard
     *
     * Récupère le dashboard complet d'une action
     *
     * @param Action $action Résolu via Route Model Binding (key)
     * @param Request $request
     * @return DashboardActionResource
     */
    public function dashboard(Action $action, Request $request): DashboardActionResource
    {
        $validated = $request->validate([
            'year' => 'nullable|integer|min:2020|max:' . now()->year,
            'horizon' => 'nullable|in:court_terme,moyen_terme,long_terme',
        ]);


        $year = $validated['year'] ?? now()->year - 1;
        $horizon = $validated['horizon'] ?? config('financial_indicators.default_horizon');

        $data = $this->orchestrator->getDashboard($action, $year, $horizon);

        return new DashboardActionResource($data);
    }

    /**
     * GET /api/actions/{action:key}/historical
     *
     * Récupère les données historiques sur plusieurs années
     *
     * @param Action $action
     * @param Request $request
     * @return HistoricalActionResource
     */
    public function historical(Action $action, Request $request): HistoricalActionResource|JsonResponse
    {
        $validated = $request->validate([
            'start_year' => 'nullable|integer|min:2020',
            'end_year' => 'nullable|integer|max:' . now()->year,
            'horizon' => 'nullable|in:court_terme,moyen_terme,long_terme',
        ]);

        $endYear = $validated['end_year'] ?? now()->year - 1;
        $startYear = $validated['start_year'] ?? $endYear - 3;
        $horizon = $validated['horizon'] ?? config('financial_indicators.default_horizon');

        // Validation: start_year < end_year
        if ($startYear >= $endYear) {
            return response()->json([
                'error' => 'start_year doit être inférieur à end_year'
            ], 422);
        }

        $data = $this->orchestrator->getHistorical($action, $startYear, $endYear, $horizon);

        return new HistoricalActionResource($data);
    }

    /**
     * POST /api/actions/{action:key}/indicators/refresh
     *
     * Force le recalcul des indicateurs (invalide cache)
     *
     * @param Action $action
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Action $action, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'nullable|integer|min:2020|max:' . now()->year,
        ]);

        $year = $validated['year'] ?? now()->year - 1;

        // Invalider le cache pour cette action
        app(\App\Services\FinancialCacheService::class)->forgetAction($action->key, $year);

        return response()->json([
            'message' => 'Cache invalidé avec succès',
            'action' => $action->symbole,
            'year' => $year,
        ]);
    }

    /**
     * GET /api/actions/{action:key}/years
     *
     * Liste les années disponibles pour une action
     *
     * @param Action $action
     * @return JsonResponse
     */
    public function availableYears(Action $action): JsonResponse
    {
        $years = $action->stockFinancials()
            ->select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return response()->json([
            'action' => [
                'key' => $action->key,
                'symbole' => $action->symbole,
                'nom' => $action->nom,
            ],
            'years' => $years,
            'count' => $years->count(),
        ]);
    }

    /**
     * GET /api/horizons
     *
     * Liste les horizons d'investissement disponibles
     *
     * @return JsonResponse
     */
    public function horizons(): JsonResponse
    {
        return response()->json([
            'horizons' => config('financial_indicators.horizons'),
            'default' => config('financial_indicators.default_horizon'),
        ]);
    }
}
