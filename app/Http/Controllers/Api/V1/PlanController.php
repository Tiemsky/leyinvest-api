<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

/**
 * @tags Plans de souscription
*/
class PlanController extends Controller
{
    /**
    * Retourne la liste des plans actifs et visibles avec leurs fonctionnalités actives
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
     * Retourne les détails d'un plan spécifique par son slug
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
