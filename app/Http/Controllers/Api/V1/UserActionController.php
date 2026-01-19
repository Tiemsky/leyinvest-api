<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\AlreadyFollowingException;
use App\Exceptions\NotFollowingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\FollowActionRequest;
use App\Http\Requests\UpdateUserActionRequest;
use App\Http\Resources\UserActionResource;
use App\Services\UserActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags User Actions - Follow & Unfollow
 */
class UserActionController extends Controller
{
    public function __construct(
        private UserActionService $userActionService
    ) {}

    /**
     * Obtenir toutes les actions suivies par l'utilisateur connecté
     */
    public function index(): JsonResponse
    {
        $actions = $this->userActionService->getUserFollowedActions(auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Liste des actions suivies récupérée avec succès',
            'data' => UserActionResource::collection($actions),
        ], 200);
    }

    /**
     * Suivre une action
     */
    public function follow(FollowActionRequest $request): JsonResponse
    {
        try {
            $userAction = $this->userActionService->follow(
                userId: auth()->id(),
                actionId: $request->validated('action_id'),
                stopLoss: $request->validated('stop_loss'),
                takeProfit: $request->validated('take_profit')
            );

            return response()->json([
                'success' => true,
                'message' => 'Action suivie avec succès',
                'data' => new UserActionResource($userAction),
            ], 201);
        } catch (AlreadyFollowingException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    /**
     * Ne plus suivre une action
     */
    public function unfollow(int $actionId): JsonResponse
    {
        try {
            $this->userActionService->unfollow(auth()->id(), $actionId);

            return response()->json([
                'success' => true,
                'message' => 'Action retirée avec succès',
            ], 200);
        } catch (NotFollowingException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Ne plus suivre plusieurs actions
     */
    public function unfollowMultiple(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action_ids' => 'required|array|min:1',
            'action_ids.*' => 'required|integer|exists:actions,id',
        ]);

        $result = $this->userActionService->unfollowMultiple(
            auth()->id(),
            $validated['action_ids']
        );

        return response()->json([
            'success' => true,
            'message' => "{$result['unfollowed_count']} action(s) retirée(s) avec succès",
            'unfollowed_count' => $result['unfollowed_count'],
        ], 200);
    }

    /**
     * Mettre à jour les paramètres (stop_loss, take_profit)
     */
    public function update(UpdateUserActionRequest $request, int $actionId): JsonResponse
    {
        try {
            $userAction = $this->userActionService->updateParameters(
                userId: auth()->id(),
                actionId: $actionId,
                stopLoss: $request->validated('stop_loss'),
                takeProfit: $request->validated('take_profit')
            );

            return response()->json([
                'success' => true,
                'message' => 'Paramètres mis à jour avec succès',
                'data' => new UserActionResource($userAction),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne suivez pas cette action',
            ], 404);
        }
    }

    /**
     * Toggle follow/unfollow
     */
    public function toggle(FollowActionRequest $request): JsonResponse
    {
        $result = $this->userActionService->toggle(
            userId: auth()->id(),
            actionId: $request->validated('action_id'),
            stopLoss: $request->validated('stop_loss'),
            takeProfit: $request->validated('take_profit')
        );

        if ($result['action'] === 'followed') {
            return response()->json([
                'success' => true,
                'message' => 'Action suivie avec succès',
                'action' => 'followed',
                'data' => new UserActionResource($result['data']),
            ], 201);
        }

        return response()->json([
            'success' => true,
            'message' => 'Action retirée avec succès',
            'action' => 'unfollowed',
        ], 200);
    }

    /**
     * Vérifier si l'utilisateur suit une action
     */
    public function checkFollowing(int $actionId): JsonResponse
    {
        $isFollowing = $this->userActionService->isFollowing(auth()->id(), $actionId);

        return response()->json([
            'success' => true,
            'is_following' => $isFollowing,
        ], 200);
    }

    /**
     * Obtenir les statistiques de suivi
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'following_count' => $this->userActionService->getFollowingCount(auth()->id()),
        ], 200);
    }

    /**
     * Obtenir les followers d'une action
     */
    public function followers(int $actionId): JsonResponse
    {
        $followers = $this->userActionService->getActionFollowers($actionId);

        return response()->json([
            'success' => true,
            'count' => $followers->count(),
            'followers' => $followers,
        ], 200);
    }
}
