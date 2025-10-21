<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActionResource;
use App\Models\Action;
use App\Models\UserAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        // Récupère tous les IDs d'actions suivies par l'utilisateur
        $followedIds = UserAction::where('user_id', $user->id)->pluck('action_id')->toArray();

        // Récupère toutes les actions
        $actions = Action::all();

        // Transforme chaque ressource en lui injectant les actions suivies
        $data = $actions->map(function ($action) use ($followedIds) {
            return (new ActionResource($action))->withFollowedIds($followedIds);
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }
}
