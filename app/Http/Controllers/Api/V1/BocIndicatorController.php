<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\BocIndicatorResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\BocIndicator;

class BocIndicatorController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/v1/indicators",
     *     summary="Lister tous les indicateurs financiers",
     *     description="Retourne la liste complète des indicateurs disponibles dans le système.",
     *     operationId="getIndicator",
     *     tags={"Indicators"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Liste des indicateurs récupérés avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/BocIndicatorResource")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur interne du serveur.")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        try {
            $indicators = BocIndicator::query()->latest()->get();

            return response()->json([
                'success' => true,
                'data' => BocIndicatorResource::collection($indicators),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne du serveur.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
