<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\CountryResource;

/**
     * @tags Pays
     */
class CountryController extends Controller
{

    /**
     * Retourne la liste des pays de l'UEMOA
     */
    public function index(): JsonResponse{
        $countries = Country::query()->get();
        return response()->json([
            'success' => true,
            'message' => 'Liste des pays de l\'UEMOA',
            'data' => CountryResource::collection($countries),
             ], 200);
    }
}
