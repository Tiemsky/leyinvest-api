<?php

namespace App\Services;

use App\Models\User;
use App\Models\Action;
use Illuminate\Database\Eloquent\Collection;

class FollowService
{
    /**
     * Toggle follow/unfollow logic + handle stop_loss & take_profit
     */
    public function toggleFollow(User $user, Action $action, ?float $stopLoss = null, ?float $takeProfit = null): string
    {
        $existing = $user->actions()->where('action_id', $action->id)->first();

        if ($existing) {
            $user->actions()->detach($action->id);
            return 'unfollowed';
        }

        $user->actions()->attach($action->id, [
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
        ]);

        return 'followed';
    }

    /**
     * Update stop_loss or take_profit for a followed action
     */
    public function updateFollowParams(User $user, Action $action, ?float $stopLoss = null, ?float $takeProfit = null): bool
    {
        if (!$user->actions()->where('action_id', $action->id)->exists()) {
            return false;
        }

        $user->actions()->updateExistingPivot($action->id, [
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
        ]);

        return true;
    }

    public function getFollowedActions(User $user): Collection
    {
        return $user->actions()->latest('action_user.created_at')->get();
    }

    public function isFollowing(User $user, Action $action): bool
    {
        return $user->actions()->where('action_id', $action->id)->exists();
    }
}
