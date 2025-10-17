<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\CountryResource;

class CountryController extends Controller
{
    public function index(): JsonResponse{
        $countries = Country::query()->get();
        return response()->json([
            'success' => true,
            'data' => CountryResource::collection($countries),
             ]);
    }
}
