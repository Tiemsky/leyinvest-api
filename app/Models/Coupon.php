<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'type',
        'value',
        'max_discount',
        'max_uses',
        'times_used',
        'starts_at',
        'expires_at',
        'is_active',
        'applicable_plans',
        'metadata',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'applicable_plans' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Relations
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')
                  ->orWhereRaw('times_used < max_uses');
            });
    }

    /**
     * Vérifier si le coupon est valide
     */
    public function isValid(): bool
    {
        // Vérifier si actif
        if (!$this->is_active) {
            return false;
        }

        // Vérifier date de début
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        // Vérifier date d'expiration
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        // Vérifier nombre d'utilisations
        if ($this->max_uses && $this->times_used >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Vérifier si applicable à un plan
     */
    public function isApplicableToPlan(int $planId): bool
    {
        // Si null, applicable à tous les plans
        if (empty($this->applicable_plans)) {
            return true;
        }

        return in_array($planId, $this->applicable_plans);
    }

    /**
     * Calculer la réduction
     */
    public function calculateDiscount(float $amount): float
    {
        if ($this->type === 'percentage') {
            $discount = ($amount * $this->value) / 100;

            // Appliquer la réduction maximale si définie
            if ($this->max_discount && $discount > $this->max_discount) {
                return $this->max_discount;
            }

            return $discount;
        }

        // Type 'fixed'
        return min($this->value, $amount);
    }

    /**
     * Incrémenter l'utilisation
     */
    public function incrementUsage(): void
    {
        $this->increment('times_used');
    }
}
