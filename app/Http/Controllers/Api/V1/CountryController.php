<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\CountryResource;

class CountryController extends Controller
{
     /**
     * @OA\Get(
     *      path="/api/countries",
     *      operationId="getCountryList",
     *      tags={"Pays de l'UEMOA"},
     *      summary="Liste des pays de L'UEMOA",
     *      description="Retourne la liste de tous les pays de l'UEMOA",
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
     *                      @OA\Property(property="nom", type="string", example="Côte d'Ivoire"),
     *                      @OA\Property(property="slug", type="string", example="cote-d-ivoire"),

     *                  )
     *              )
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
    public function index(): JsonResponse{
        $countries = Country::query()->get();
        return response()->json([
            'success' => true,
            'data' => CountryResource::collection($countries),
             ]);
    }
}
