<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Action;
use App\Models\BrvmSector;
use App\Models\UserAction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Services\FiscalYearService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\ActionResource;
use App\Http\Resources\ActionHistoryResource;
use App\Http\Resources\ActionDashboardResource;
use App\Http\Resources\ShowSingleActionResource;
use App\Http\Resources\SectorWithActionsResource;

class ActionController extends Controller
{
    /**
     * Durée du cache en secondes (30 min).
     */
    private const CACHE_TTL = 1800;

    /**
     * Injection du service FiscalYear pour une gestion centralisée des années de référence
     */
    public function __construct(
        private readonly FiscalYearService $fiscalYearService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/actions",
     *     summary="Lister toutes les actions avec statut de suivi",
     *     description="Retourne toutes les actions et indique si l'utilisateur authentifié les suit. Cette route nécessite une authentification.",
     *     operationId="getAllActions",
     *     tags={"Actions"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des actions récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/ActionResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Optimisé : une seule requête pour récupérer les actions suivies
        $followedActionIds = UserAction::where('user_id', $user->id)
            ->pluck('action_id')
            ->all();

        $actions = Action::with([
            'brvmSector',
            'classifiedSector',
            'shareholders',
            'employees.position'
        ])->latest()->get();

        // Crée la resource avec le contexte des actions suivies
        $data = $actions->map(fn ($action) =>
            new ActionResource($action, $followedActionIds)
        );

        return response()->json([
            'success' => true,
            'message' => 'Liste des actions récupérée avec succès',
            'data' => $data,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/actions/analyze",
     *     summary="Analyser les actions par secteur",
     *     description="Retourne la liste des secteurs ainsi que leurs actions associées.",
     *     operationId="analyzeActions",
     *     tags={"Actions"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Listes des actions par secteur récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Listes des actions par secteur récupérée avec succes"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/SectorWithActionsResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function analyze(): JsonResponse
    {
        // Charger tous les secteurs avec leurs actions
        $sectors = BrvmSector::with('actions')->get();

        return response()->json([
            'success' => true,
            'message' => 'Listes des actions par secteur récupérée avec succes',
            'data' => SectorWithActionsResource::collection($sectors),
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/actions/analyze/{actionKey}",
     *     summary="Afficher les détails complet d'une action avec les indicateurs boursiers",
     *     description="Retourne toutes les métriques d'une action, incluant la valeur brute, les statistiques sectorielles et les statistiques BRVM. L'année de référence est calculée automatiquement selon la période fiscale (N-2 avant le 01/03, N-1 après le 02/03).",
     *     operationId="showAction",
     *     tags={"Actions"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="key",
     *         in="path",
     *         required=true,
     *         description="Clé unique de l'action (identifiant logique).",
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Détails d'une action retournés avec succès.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 @OA\Property(property="reference_year", type="integer", example=2024),
     *                 @OA\Property(property="fiscal_period", type="string"),
     *                 @OA\Property(property="is_consolidation_period", type="boolean"),
     *                 @OA\Property(property="from_cache", type="boolean")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Action non trouvée.",
     *         @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="message", type="string", example="Action introuvable.")
     *         )
     *     )
     * )
     */
    public function show(Action $action): JsonResponse
    {
        // Récupération de l'année de référence via le service
        // Avant le 01/03 : N-2 | Après le 02/03 : N-1
        $referenceYear = $this->fiscalYearService->getReferenceYear();

        // Clé de cache unique incluant l'année de référence
        // Pattern: actions:{id}:dashboard:{year}
        $cacheKey = "actions:{$action->id}:dashboard:{$referenceYear}";

        // Utilisation du cache Redis avec TTL de 30 minutes
        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($action, $referenceYear) {

            // Eager loading optimisé : uniquement les données nécessaires
            $action->load([
                'brvmSector',
                'classifiedSector',
                'shareholders',
                'employees.position',
                'quarterlyResults',
                // CRITIQUE : Ne charger que les données financières de l'année de référence
                'financials' => fn($q) => $q->where('year', $referenceYear)
            ]);

            return new ActionDashboardResource($action, $referenceYear);
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'metadata' => array_merge(
                // Métadonnées fiscales enrichies du service
                $this->fiscalYearService->getMetadata(),
                [
                    'calculated_at' => now()->toIso8601String(),
                    'from_cache' => Cache::has($cacheKey),
                    'cache_key' => $cacheKey,
                    'version' => '1.0'
                ]
            )
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/actions/historique/{actionKey}",
     *     summary="Historique des cours d'une action",
     *     description="Retourne l'évolution historique des cours d'une action sur 5 ans. L'année de départ est calculée automatiquement selon la période fiscale.",
     *     operationId="historiqueAction",
     *     tags={"Actions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="key",
     *         in="path",
     *         required=true,
     *         description="Clé de l'action",
     *         @OA\Schema(type="string", example="act_abc123def")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historique récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 @OA\Property(property="years_range", type="object"),
     *                 @OA\Property(property="fiscal_period", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Action introuvable"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function historique(Action $action): JsonResponse
    {
        // Génération des 5 années d'historique via le service
        // Exemple : en 2026 après le 02/03 → [2025, 2024, 2023, 2022, 2021]
        $years = $this->fiscalYearService->getHistoricalYears(5);

        // Clé de cache dynamique basée sur les années calculées
        // Important : la clé change automatiquement après le 02/03
        $cacheKey = "actions:{$action->id}:history:" . implode('-', $years);

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($action, $years) {

            // Eager loading optimisé pour les années d'historique
            $action->load([
                'brvmSector',
                'classifiedSector',
                // Chargement sélectif : uniquement les 5 années concernées
                'financials' => fn($q) => $q->whereIn('year', $years)
                    ->orderBy('year', 'desc')
            ]);

            return new ActionHistoryResource($action, $years);
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'metadata' => [
                'years_range' => [
                    'from' => min($years),
                    'to' => max($years)
                ],
                'years_available' => count($years),
                'years' => $years,
                'fiscal_period' => $this->fiscalYearService->getFiscalPeriodLabel(),
                'is_consolidation_period' => $this->fiscalYearService->isConsolidationPeriod(),
                'calculated_at' => now()->toIso8601String(),
                'from_cache' => Cache::has($cacheKey),
            ]
        ], Response::HTTP_OK);
    }
}
