<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TopResource;
use App\Models\Flop;
use Illuminate\Http\Request;

class TopController extends Controller
{
 /**
     * @OA\Get(
     *      path="/api/v1/tops",
     *      operationId="getTopsList",
     *      tags={"Tops"},
     *      summary="Liste des tops",
     *      description="Retourne la liste des actions avec les plus fortes hausses (Top 5 des meilleures performances)",
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
     *                      @OA\Property(property="key", type="string", example="SICC_CI"),
     *                      @OA\Property(property="symbole", type="string", example="SICC"),
     *                      @OA\Property(property="cours", type="string", example="6 007"),
     *                      @OA\Property(property="variation", type="number", format="float", example=7.46),
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
     *      security={{"sanctum":{}}}
     * )
     */

    public function index(){
        $flops = Flop::query()->get();
        return response()->json([
            'success' => true,
            'data' => TopResource::collection($flops),
             ]);
    }
}
