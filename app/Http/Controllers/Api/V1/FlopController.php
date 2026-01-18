<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TopFlopResource;
use App\Services\TopFlopService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Flops
*/
class FlopController extends Controller
{
    /**
     * Service utilisé pour récupérer les données des flops.
     *
     * @var TopFlopService
     */
    protected TopFlopService $topFlopService;

    /**
     * Injection du service dans le contrôleur.
     *
     * @param TopFlopService $topFlopService
     */
    public function __construct(TopFlopService $topFlopService)
    {
        $this->topFlopService = $topFlopService;
    }

    /**
     * Retourne la liste des 5 pires performances (flops).
     */
    public function index(): JsonResponse
    {
        $flops = $this->topFlopService->getFlop(5);
        return response()->json([
            'success' => true,
            'message' => 'Liste des flops récupérée avec succès',
            'data' => TopFlopResource::collection($flops),
        ], 200);
    }
}
