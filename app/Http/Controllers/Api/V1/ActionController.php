<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Action;
use App\Models\BrvmSector;
use App\Models\UserAction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
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

        // Optimisé : une seule requête
        $followedActionIds = UserAction::where('user_id', $user->id)
            ->pluck('action_id')
            ->all();

        $actions = Action::with([
            'brvmSector',
            'classifiedSector',
            'shareholders',
            'employees.position'
        ])->latest()->get();

        //  Crée la resource avec le contexte
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
    public function analyze(){
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
 *     summary="Afficher les détails complet d’une action avec les indicateurs boursiers",
 *     description="Retourne toutes les métriques d’une, incluant la valeur brute, les statistiques sectorielles et les statistiques BRVM.",
 *     operationId="showAction",
 *     tags={"Actions"},
 *     security={{"sanctum":{}}},
 *
 *     @OA\Parameter(
 *         name="key",
 *         in="path",
 *         required=true,
 *         description="Clé unique de l'action  identifiant logique).",
 *         @OA\Schema(type="string")
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Détails d’une actiion retournés avec succès.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="key", type="string", example="roa"),
 *             @OA\Property(
 *                 property="metrics",
 *                 type="object",
 *                 description="Liste des indicateurs formatés",
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=404,
 *         description="action non trouvé.",
 *         @OA\JsonContent(
 *              type="object",
 *              @OA\Property(property="message", type="string", example="Indicateur introuvable.")
 *         )
 *     )
 * )
 */

    public function show(Action $action): JsonResponse
    {
      // Définition de l'année N-1 (Référence)
      $year = now()->year - 1;

      // Clé de cache unique : action_{id}_dashboard_{year}
      $cacheKey = "actions:{$action->id}:dashboard:{$year}";

      // Utilisation du cache Redis via Cache::remember
      $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($action, $year) {

          // Eager loading des relations nécessaires (pour minimiser les requêtes DB si le cache est vide)
          $action->load([
              'brvmSector',
              'classifiedSector',
              'shareholders',
              'employees.position',
              'quarterlyResults',
              // Optimisation : On ne charge que la ligne financière de l'année concernée
              'financials' => fn($q) => $q->where('year', $year)
          ]);

          return new ActionDashboardResource($action, $year);
      });

      return response()->json([
          'success' => true,
          'data' => $data,
          'metadata' => [
              'year' => $year,
              'calculated_at' => now()->toIso8601String(),
              'from_cache' => Cache::has($cacheKey), // Indicateur si la donnée vient du cache
              'version' => '1.0'
          ]
      ], Response::HTTP_OK);
    }


    /**
 * @OA\Get(
 *     path="/api/v1/actions/historique/{actionKey}",
 *     summary="Historique des cours d'une action",
 *     description="Retourne l’évolution historique des cours d’une action. À intégrer avec les données réelles de ton provider.",
 *     operationId="historiqueAction",
 *     tags={"Actions"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="key",
 *         in="path",
 *         required=true,
 *         description="key de l'action",
 *         @OA\Schema(type="string", example="act_abc123def")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Historique récupéré avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Historique des cours récupéré avec succès"),
 *             @OA\Property(
 *                 property="data",
 *                 type="string",
 *                 example="historique des cours pour l'action BOA"
 *             )
 *         )
 *     ),
 *     @OA\Response(response=404, description="Action introuvable"),
 *     @OA\Response(response=401, description="Non authentifié")
 * )
 */
    public function historique(Action $action): JsonResponse
    {
        $cacheKey = "actions:{$action->id}:history:5years";

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($action) {
            // Eager load des données historiques
            $action->load([
                'brvmSector',
                'classifiedSector',
                'financials' => fn($q) => $q->orderBy('year', 'desc')->limit(5)
            ]);

            $currentYear = now()->year - 1;
            $years = range($currentYear, $currentYear - 4);

            return new ActionHistoryResource($action, $years);
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'metadata' => [
                'years_available' => 5,
                'calculated_at' => now()->toIso8601String(),
            ]
        ], Response::HTTP_OK);
    }
}
