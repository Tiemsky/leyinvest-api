<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAction;

class ActionService
{
    public function getAllActions(User $user)
    {
        // Récupère tous les IDs d'actions suivies par l'utilisateur
        $followedIds = UserAction::where('user_id', $user->id)->pluck('action_id')->toArray();

    }
}
