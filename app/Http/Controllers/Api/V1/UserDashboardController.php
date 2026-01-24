<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserDashboardResource;
use App\Services\TopFlopService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @tags UserDashboard
 */
class UserDashboardController extends Controller
{
    /**
     * Retourne les informations du tableau de bord utilisateur.
     */
    public function index(TopFlopService $topFlopService): JsonResponse
    {
        $user = Auth::user()->load(['followedActions.action', 'activeSubscription']);

        // On récupère les tops et flops ici
        $topActions = $topFlopService->getTop(5);
        $flopActions = $topFlopService->getFlop(5);

        return response()->json([
            'success' => true,
            'message' => 'Données du tableau de bord utilisateur récupérées avec succès',
            'data' => new UserDashboardResource($user, $topActions, $flopActions),
        ], 200);
    }
}
