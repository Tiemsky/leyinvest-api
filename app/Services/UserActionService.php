<?php

namespace App\Services;

use App\Exceptions\AlreadyFollowingException;
use App\Exceptions\NotFollowingException;
use App\Models\UserAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UserActionService
{
    /**
     * Suivre une action
     */
    public function follow(int $userId, int $actionId, ?float $stopLoss = null, ?float $takeProfit = null): UserAction
    {
        // Vérifier si l'utilisateur suit déjà cette action
        $existing = UserAction::where('user_id', $userId)
            ->where('action_id', $actionId)
            ->first();

        if ($existing) {
            throw new AlreadyFollowingException('Vous suivez déjà cette action');
        }

        return UserAction::create([
            'user_id' => $userId,
            'action_id' => $actionId,
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
        ]);
    }

    /**
     * Ne plus suivre une action
     */
    public function unfollow(int $userId, int $actionId): bool
    {
        $userAction = UserAction::where('user_id', $userId)
            ->where('action_id', $actionId)
            ->first();

        if (! $userAction) {
            throw new NotFollowingException('Vous ne suivez pas cette action');
        }

        return $userAction->delete();
    }

    public function unfollowMultiple(int $userId, array $actionIds): array
    {
        // Récupérer toutes les actions suivies par l'utilisateur parmi celles demandées
        $userActions = UserAction::where('user_id', $userId)
            ->whereIn('action_id', $actionIds)
            ->get();

        $foundActionIds = $userActions->pluck('action_id')->toArray();
        $notFoundActionIds = array_diff($actionIds, $foundActionIds);

        // Supprimer toutes les actions trouvées
        $unfollowedCount = UserAction::where('user_id', $userId)
            ->whereIn('action_id', $foundActionIds)
            ->delete();

        return [
            'unfollowed_count' => $unfollowedCount,
            'not_found_count' => count($notFoundActionIds),
            'not_found_ids' => array_values($notFoundActionIds),
        ];
    }

    /**
     * Mettre à jour les valeurs stop_loss et take_profit
     */
    public function updateParameters(int $userId, int $actionId, ?float $stopLoss = null, ?float $takeProfit = null): UserAction
    {
        $userAction = UserAction::where('user_id', $userId)
            ->where('action_id', $actionId)
            ->firstOrFail();

        $userAction->update([
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
        ]);

        return $userAction->fresh();
    }

    /**
     * Obtenir toutes les actions suivies par un utilisateur
     */
    public function getUserFollowedActions(int $userId): Collection
    {
        return UserAction::with(['user', 'action'])
            ->forUser($userId)
            ->get();
    }

    /**
     * Obtenir tous les followers d'une action
     */
    public function getActionFollowers(int $actionId): Collection
    {
        return UserAction::with('user')
            ->forAction($actionId)
            ->get();
    }

    /**
     * Vérifier si un utilisateur suit une action
     */
    public function isFollowing(int $userId, int $actionId): bool
    {
        return UserAction::where('user_id', $userId)
            ->where('action_id', $actionId)
            ->exists();
    }

    /**
     * Obtenir le nombre de followers d'une action
     */
    public function getFollowersCount(int $actionId): int
    {
        return UserAction::forAction($actionId)->count();
    }

    /**
     * Obtenir le nombre d'actions suivies par un utilisateur
     */
    public function getFollowingCount(int $userId): int
    {
        return UserAction::forUser($userId)->count();
    }

    /**
     * Toggle follow/unfollow
     */
    public function toggle(int $userId, int $actionId, ?float $stopLoss = null, ?float $takeProfit = null): array
    {
        return DB::transaction(function () use ($userId, $actionId, $stopLoss, $takeProfit) {
            $existing = UserAction::where('user_id', $userId)
                ->where('action_id', $actionId)
                ->first();

            if ($existing) {
                $existing->delete();

                return [
                    'action' => 'unfollowed',
                    'data' => null,
                ];
            }

            $userAction = $this->follow($userId, $actionId, $stopLoss, $takeProfit);

            return [
                'action' => 'followed',
                'data' => $userAction,
            ];
        });
    }
}
