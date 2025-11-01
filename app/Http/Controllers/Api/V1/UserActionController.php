<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\UserActionService;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFollowingException;
use App\Http\Requests\FollowActionRequest;
use App\Http\Resources\UserActionResource;
use App\Exceptions\AlreadyFollowingException;
use App\Http\Requests\UpdateUserActionRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
* @OA\Tag(
  *     name="User and Actions",
  *     description="Endpoints pour gérer le système de follow/unfollow des actions"
  * )
*/
class UserActionController extends Controller
{
    public function __construct(
        private UserActionService $userActionService
    ) {}

    /**
     * Obtenir toutes les actions suivies par l'utilisateur connecté
     */

/**
     * @OA\Get(
     *     path="/api/v1/user/actions",
     *     operationId="getFollowedActions",
     *     tags={"User Actions"},
     *     summary="Lister les actions suivies par l'utilisateur authentifié",
     *     description="Récupère toutes les actions suivies par l'utilisateur authentifié",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste récupérée avec succès",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/UserActionResource")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function index(): JsonResponse
    {
        $actions = $this->userActionService->getUserFollowedActions(auth()->id());

        return response()->json([
            'success' => true,
            'data' => UserActionResource::collection($actions),
        ], 200);
    }

    /**
     * Suivre une action
     */
       /**
     * @OA\Post(
     *     path="/api/v1/user/action/follow",
     *     operationId="followAction",
     *     tags={"User Actions"},
     *     summary="Suivre une action",
     *     description="Permet à l'utilisateur de suivre une action avec des valeurs optionnelles de stop_loss et take_profit",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/FollowActionRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Action suivie avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/UserActionResource")
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=409, description="Action déjà suivie"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
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

     /**
     * @OA\Delete(
     *     path="/api/v1/user/action/{actionId}/unfollow",
     *     operationId="unfollowAction",
     *     tags={"User Actions"},
     *     summary="Ne plus suivre une action",
     *     description="Permet de ne plus suivre une action précédemment suivie",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="actionId",
     *         in="path",
     *         required=true,
     *         description="ID de l'action à unfollow",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(response=200, description="Action retirée avec succès"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=404, description="Action non trouvée ou non suivie")
     * )
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

 /**
 * @OA\Post(
 *     path="/api/v1/user/actions/unfollow",
 *     operationId="unfollowMultipleActions",
 *     tags={"User Actions"},
 *     summary="Ne plus suivre plusieurs actions",
 *     description="Permet à un utilisateur authentifié de ne plus suivre une ou plusieurs actions en une seule requête.",
 *     security={{"sanctum": {}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"action_ids"},
 *             @OA\Property(
 *                 property="action_ids",
 *                 type="array",
 *                 description="Liste des IDs des actions à ne plus suivre.",
 *                 @OA\Items(type="integer", example=10),
 *                 example={10, 15, 23}
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Actions retirées avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="3 action(s) retirée(s) avec succès"),
 *             @OA\Property(property="unfollowed_count", type="integer", example=3),
 *             @OA\Property(
 *                 property="not_found_ids",
 *                 type="array",
 *                 description="Liste des IDs non trouvés (si certains IDs ne correspondent pas à des actions suivies).",
 *                 @OA\Items(type="integer"),
 *                 example={}
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=401,
 *         description="Non authentifié — jeton d'accès manquant ou invalide.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Données de validation invalides.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 @OA\Property(
 *                     property="action_ids",
 *                     type="array",
 *                     @OA\Items(type="string", example="The action_ids field is required.")
 *                 )
 *             )
 *         )
 *     )
 * )
 */
public function unfollowMultiple(Request $request): JsonResponse
{
    $validated = $request->validate([
        'action_ids' => 'required|array|min:1',
        'action_ids.*' => 'required|integer|exists:actions,id'
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

     /**
     * @OA\Patch(
     *     path="/api/v1/user/action/{actionId}",
     *     operationId="updateFollowParameters",
     *     tags={"User Actions"},
     *     summary="Mettre à jour les paramètres de suivi",
     *     description="Permet à l'utilisateur de mettre à jour les valeurs stop_loss et take_profit d'une action déjà suivie",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="actionId",
     *         in="path",
     *         required=true,
     *         description="ID de l'action suivie",
     *         @OA\Schema(type="integer", example=8)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateUserActionRequest")
     *     ),
     *     @OA\Response(response=200, description="Paramètres mis à jour avec succès"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=404, description="Action non suivie"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
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
