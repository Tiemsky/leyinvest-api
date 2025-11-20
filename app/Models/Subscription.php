<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasKey;
    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'canceled_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function($q) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>', now());
            });
    }


    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<', now());
    }

    // Vérifications d'état
    public function isActive()
    {
        return $this->status === 'active' &&
               ($this->ends_at === null || $this->ends_at->isFuture());
    }


    public function canceled()
    {
        return !is_null($this->canceled_at);
    }


    // Actions
    public function cancel()
    {
        $this->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'ends_at' => now()->endOfMonth() // Grace period
        ]);
    }

    public function resume()
    {
        $this->update([
            'status' => 'active',
            'canceled_at' => null,
            'ends_at' => null
        ]);
    }
}
