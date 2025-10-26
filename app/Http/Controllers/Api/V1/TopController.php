<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TopFlopResource;
use App\Services\TopFlopService;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Tops",
 *     description="Endpoints relatifs aux actions ayant les plus fortes hausses sur le marché."
 * )
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
     * @OA\Get(
     *     path="/api/v1/tops",
     *     operationId="getTopsList",
     *     tags={"Tops"},
     *     summary="Récupérer la liste des meilleures actions (Tops)",
     *     description="Retourne les 5 actions ayant les plus fortes hausses enregistrées sur le marché pour la journée en cours.",
     *     @OA\Response(
     *         response=200,
     *         description="Liste récupérée avec succès.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/TopFlop")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Utilisateur non authentifié.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Non authentifié.")
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $tops = $this->topFlopService->getTop(5);

        return response()->json([
            'success' => true,
            'data' => TopFlopResource::collection($tops),
        ]);
    }
}
