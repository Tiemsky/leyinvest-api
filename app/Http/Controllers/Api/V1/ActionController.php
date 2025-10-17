<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Action;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActionResource;

class ActionController extends Controller
{
    public function index(Request $request)
    {
        $actions = Action::query()->getl();
        return response()->json([
            'success' => true,
            'data' => ActionResource::collection($actions),
        ]);
    }
}
