<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserDashboardResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

 /**
 * @OA\Tag(
 *     name="UserDashboard",
 *     description="Endpoints qui regroupe tous les elements du tableau de bord utilisateur"
 * )
 */
class UserDashboardController extends Controller
{
    /**
  * @OA\Get(
    *     path="/api/v1/user/dashboard",
    *     summary="Récupérer le tableau de bord de l'utilisateur connecté",
    *     description="Retourne les informations du user connecté, 5 actions suivies aléatoires et sa liste complète d'actions suivies.",
    *     operationId="getUserDashboard",
    *     tags={"UserDashboard"},
    *     security={{"sanctum":{}}},
    *     @OA\Response(
    *         response=200,
    *         description="Dashboard utilisateur récupéré avec succès",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=true),
    *             @OA\Property(property="data", ref="#/components/schemas/UserDashboardResource")
    *         )
    *     ),
    *     @OA\Response(response=401, description="Non authentifié")
    * )
    */
    public function index(): JsonResponse
    {
        $user = Auth::user()->load([
            'followedActions.action',
        ]);

        return response()->json([
            'success' => true,
            'data' => new UserDashboardResource($user),
        ], 200);
    }
}
