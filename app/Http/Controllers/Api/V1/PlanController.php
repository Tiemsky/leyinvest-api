<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 * name="Plans",
 * description="Opérations liées aux plans d'abonnement"
 * )
 */
class PlanController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/v1/plans",
     * tags={"Plans"},
     * summary="Récupérer la liste des plans actifs et visibles",
     * description="Retourne la liste des plans destinés à être affichés publiquement.",
     * operationId="getPlans",
     * @OA\Response(
     * response=200,
     * description="Plans récupérés avec succès.",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/PlanResource"))
     * )
     * )
     * )
     */
    public function index(): JsonResponse
    {
        // Utilisation des scopes 'active' et 'visible' pour masquer la logique de requête
        $plans = Plan::active()
            ->visible()
            ->with('activeFeatures')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Plans récupérés avec succès.',
            'data' => PlanResource::collection($plans),
        ]);
    }

    /**
     * @OA\Get(
     * path="/api/v1/plans/{slug}",
     * tags={"Plans"},
     * summary="Afficher un plan spécifique",
     * operationId="showPlan",
     * @OA\Parameter(
     * name="slug",
     * in="path",
     * description="Slug du plan (ex: 'premium-annuel')",
     * required=true,
     * @OA\Schema(type="string")
     * ),
     * @OA\Response(
     * response=200,
     * description="Plan récupéré avec succès.",
     * @OA\JsonContent(ref="#/components/schemas/PlanResource")
     * ),
     * @OA\Response(
     * response=404,
     * description="Plan non trouvé ou non visible."
     * )
     * )
     */
    public function show(string $slug): JsonResponse
    {
        try {
            // Utilisation des scopes et du findOrFail par le slug
            $plan = Plan::active()
                ->visible()
                ->where('slug', $slug)
                ->with('activeFeatures')
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'message' => 'Plan récupéré avec succès.',
                'data' => new PlanResource($plan),
            ]);

        } catch (ModelNotFoundException $e) {
            // Gestion explicite de l'erreur 404
            return response()->json([
                'success' => false,
                'message' => 'Le plan demandé est introuvable ou n\'est pas accessible.',
            ], 404);
        }
    }
}
