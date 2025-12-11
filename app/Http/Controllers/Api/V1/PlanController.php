<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Plan;
use Http;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;

/**
 * @OA\Tag(
 *     name="Plans",
 *     description="Opérations liées aux plans d'abonnement"
 * )
 */
class PlanController extends Controller
{
        /**
     * @OA\Get(
     *     path="/api/v1/plans",
     *     tags={"Plans"},
     *     summary="Récupérer la liste des plans actifs",
     *     description="Retourne la liste des plans où is_active = true.",
     *     operationId="getPlans",
     *     @OA\Response(
     *         response=200,
     *         description="Plans récupérés avec succès.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Plans récupérés avec succès."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="key", type="string", example="starter"),
     *                     @OA\Property(property="name", type="string", example="Starter"),
     *                     @OA\Property(property="slug", type="string", example="starter"),
     *                     @OA\Property(property="price", type="number", format="float", example=9.99),
     *                     @OA\Property(property="billing_cycle", type="string", example="monthly"),
     *                     @OA\Property(property="features", type="array", @OA\Items(type="string"), example={"10 projets","5 utilisateurs"}),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Une erreur est survenue.")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $plans = Plan::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'message' => 'Plans récupérés avec succès.',
            'data' => PlanResource::collection($plans),
        ], 200);
    }
}
