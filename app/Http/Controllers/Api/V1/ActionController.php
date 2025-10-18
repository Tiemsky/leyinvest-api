<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActionResource;
use App\Models\Action;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActionController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v1/actions",
     *      operationId="getActionsList",
     *      tags={"Actions Boursières"},
     *      summary="Liste des actions",
     *      description="Retourne la liste de toutes les actions boursières disponibles",
     *      @OA\Response(
     *          response=200,
     *          description="Opération réussie",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="key", type="string", example="ORGT_CI"),
     *                      @OA\Property(property="symbole", type="string", example="ORGT"),
     *                      @OA\Property(property="nom", type="string", example="Orange Côte d'Ivoire"),
     *                      @OA\Property(property="volume", type="string", example="125000"),
     *                      @OA\Property(property="cours_veille", type="number", format="float", example=2500.00),
     *                      @OA\Property(property="cours_ouverture", type="number", format="float", example=2520.00),
     *                      @OA\Property(property="cours_cloture", type="number", format="float", example=2550.00),
     *                      @OA\Property(property="variation", type="number", format="float", example=2.00),
     *                      @OA\Property(property="categorie", type="string", nullable=true, example="Technologie"),
     *                      @OA\Property(property="created_at", type="string", format="datetime", example="2024-01-15 10:30:00"),
     *                      @OA\Property(property="updated_at", type="string", format="datetime", example="2024-01-15 16:45:00")
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Non authentifié",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Non authentifié.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Erreur serveur",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Une erreur est survenue")
     *          )
     *      ),
     *      security={{"sanctum":{}}}
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $actions = Action::query()->get();

        return response()->json([
            'success' => true,
            'data' => ActionResource::collection($actions),
        ]);
    }
}
