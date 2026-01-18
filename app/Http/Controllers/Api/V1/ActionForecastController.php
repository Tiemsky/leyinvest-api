<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Action;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActionForecastResource;

/**
 * @tags Prevision de rendement
 */
class ActionForecastController extends Controller
{
    /**
     * Liste toutes les actions avec leurs prévisions (Pour un tableau de bord par exemple)
     */
    public function index(): JsonResponse
    {
        $actions = Action::with('forecast')->paginate(20);
        // La Resource s'adapte automatiquement à une collection
        return response()->json([
            'success' => true,
            'message' => 'Liste des actions avec prévisions',
            'data' => ActionForecastResource::collection($actions)
        ], 200);
    }

    /**
     * Affiche les détails d'une action spécifique
     */
    public function show(Action $action): JsonResponse {
        $action = Action::with(['forecast', 'quarterlyResults'])->findOrFail($id);
         return response()->json([
            'success' => true,
            'message' => 'Détails de l\'action avec prévisions',
            'data' => new ActionForecastResource($action)
        ], 200);
    }
}
