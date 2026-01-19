<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BocIndicatorResource;
use App\Models\BocIndicator;
use Illuminate\Http\JsonResponse;

/**
 * @tags Indicateur du Bulletin Officiel des Cours (BOC)
 */
class BocIndicatorController extends Controller
{
    /**
     * Récupère la liste des indicateurs du BOC.
     */
    public function index(): JsonResponse
    {
        try {
            $indicators = BocIndicator::query()->latest()->get();

            return response()->json([
                'success' => true,
                'message' => 'Liste des indicateurs récupérés avec succès',
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
