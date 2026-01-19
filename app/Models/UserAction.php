<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAction extends Model
{
    /** @use HasFactory<\Database\Factories\UserActionFactory> */
    use HasFactory, HasKey;

    protected $guarded = [];

    protected $casts = [
        'stop_loss' => 'decimal:2',
        'take_profit' => 'decimal:2',
    ];

    /**
     * Relation avec l'utilisateur qui suit
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relation avec l'action suivie
     */
    public function action(): BelongsTo
    {
        return $this->belongsTo(Action::class, 'action_id');
    }

    /**
     * Relation avec l'utilisateur suivi (l'action)
     */
    public function followedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_id');
    }

    /**
     * Scope pour obtenir les actions d'un utilisateur
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour obtenir les followers d'une action
     */
    public function scopeForAction($query, int $actionId)
    {
        return $query->where('action_id', $actionId);
    }
}
