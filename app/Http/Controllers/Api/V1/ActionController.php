<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Action;
use App\Models\BrvmSector;
use App\Models\UserAction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActionResource;
use App\Http\Resources\ShowSingleActionResource;
use App\Http\Resources\SectorWithActionsResource;
use Illuminate\Http\Response;

class ActionController extends Controller
{
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

        // ✅ Optimisé : une seule requête
        $followedActionIds = UserAction::where('user_id', $user->id)
            ->pluck('action_id')
            ->all(); // all() au lieu de toArray() → plus propre

        // ✅ Charge les relations nécessaires (évite N+1)
        $actions = Action::with([
            'brvmSector',
            'classifiedSector',
            'shareholders',
            'employees.position'
        ])->latest()->get();

        // ✅ Crée la resource avec le contexte
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
 *     summary="Afficher les détails d'une action",
 *     description="Retourne les détails complets d’une action : secteurs, employés, actionnaires, etc.",
 *     operationId="showAction",
 *     tags={"Actions"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="key",
 *         in="path",
 *         required=true,
 *         description="Key de l'action",
 *         @OA\Schema(type="string", example="act_abc123def")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Détails de l'action récupérés avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Détails de l'action récupérés avec succès"),
 *             @OA\Property(
 *                 property="data",
 *                 ref="#/components/schemas/ShowSingleActionResource"
 *             )
 *         )
 *     ),
 *     @OA\Response(response=404, description="Action introuvable"),
 *     @OA\Response(response=401, description="Non authentifié")
 * )
 */
    public function show(Action $action): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => "détails de l'action récupérés avec succès",
            'data' => new ShowSingleActionResource($action->load(
                [
                                'brvmSector',
                                'classifiedSector',
                                'shareholders',
                                'financials' => fn($q) => $q->where('year', '>=', now()->year - 1),
                                'employees.position'])),
        ], 200);
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
        // Récupérer l'historique des cours pour l'action donnée
        $historique = "historique des cours pour l'action {$action->nom}"; // Remplacez ceci par la logique réelle pour obtenir l'historique

        return response()->json([
            'success' => true,
            'message' => "Historique des cours récupéré avec succès",
            'data' => $historique,
        ], 200);
    }
}
