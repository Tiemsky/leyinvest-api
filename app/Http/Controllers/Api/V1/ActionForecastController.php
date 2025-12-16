<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Action;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActionForecastResource;

class ActionForecastController extends Controller
{
    /**
     * Liste toutes les actions avec leurs prévisions (Pour un tableau de bord par exemple)
     */
    /**
 * @OA\Get(
 *     path="/api/v1/actions/forecasts",
 *     summary="Lister les actions avec prévisions",
 *     description="Retourne une liste paginée des actions financières avec leurs données de prévision.",
 *     operationId="listActionForecasts",
 *     tags={"Actions", "Forecasts"},
 *
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Numéro de page",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Liste des actions avec prévisions",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/ActionForecastResource")
 *             ),
 *             @OA\Property(property="links", type="object"),
 *             @OA\Property(property="meta", type="object")
 *         )
 *     )
 * )
 */

    public function index(): JsonResponse
    {
        // On charge 'forecast' pour éviter le problème N+1
        $actions = Action::with('forecast')->paginate(20);
        // La Resource s'adapte automatiquement à une collection
        return response()->json([
            'success' => true,
            'message' => 'Liste des actions avec prévisions',
            'data' => ActionForecastResource::collection($actions)
        ]);
    }

    /**
     * Affiche les détails d'une action spécifique
     */
    public function show($id)
    {
        $action = Action::with(['forecast', 'quarterlyResults'])->findOrFail($id);

        return new ActionForecastResource($action);
    }
}
