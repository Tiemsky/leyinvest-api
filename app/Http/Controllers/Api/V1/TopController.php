<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TopFlopResource;
use App\Services\TopFlopService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Tops
 */
class TopController extends Controller
{
    /**
     * Service utilisé pour récupérer les tops.
     *
     * @var TopFlopService
     */
    protected TopFlopService $topFlopService;

    /**
     * Injection du service TopFlopService.
     *
     * @param TopFlopService $topFlopService
     */
    public function __construct(TopFlopService $topFlopService)
    {
        $this->topFlopService = $topFlopService;
    }

    /**
     * Retourne la liste des 5 meilleures performances (tops).
     */
    public function index(): JsonResponse
    {
        $tops = $this->topFlopService->getTop(5);
        return response()->json([
            'success' => true,
            'message' => 'Liste des tops récupérée avec succès',
            'data' => TopFlopResource::collection($tops),
        ]);
    }
}
