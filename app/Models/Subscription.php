<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasKey;

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'canceled_at',
        'paused_at',
        'payment_method',
        'payment_status',
        'amount_paid',
        'currency',
        'coupon_id',
        'metadata',
        'cancellation_reason',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'paused_at' => 'datetime',
        'amount_paid' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Constantes pour les statuts
    public const STATUS_ACTIVE = 'active';

    public const STATUS_TRIALING = 'trialing';

    public const STATUS_CANCELED = 'canceled';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_PENDING = 'pending';

    /**
     * Relations
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Scopes
     * Le but des scopes est d'isoler la logique de requête.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            // L'abonnement actif doit être dans sa période payée, ou n'avoir aucune date de fin définie.
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }

    public function scopeTrialing(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_TRIALING)
            ->where('trial_ends_at', '>', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        // Une souscription est expirée si son statut est explicitement 'expired' ou si sa période est passée.
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_EXPIRED)
                ->orWhere(function ($subQ) {
                    $subQ->whereNotNull('ends_at')->where('ends_at', '<', now());
                });
        });
    }

    public function scopePaused(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAUSED);
    }

    public function scopeCanceled(Builder $query): Builder
    {
        // Canceled ne signifie pas nécessairement Expired (peut être en période de grâce)
        return $query->whereNotNull('canceled_at');
    }

    public function scopeValid(Builder $query): Builder
    {
        // Un abonnement est valide s'il est actif ou en période d'essai valide.
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_TRIALING])
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->orWhere(function ($q) {
                $q->where('status', self::STATUS_TRIALING)
                    ->where('trial_ends_at', '>', now());
            });
    }

    /**
     * Vérifications d'état (Helpers du Modèle)
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE &&
               ! $this->hasEnded() &&
               ! $this->isPaused();
    }

    public function isTrialing(): bool
    {
        return $this->status === self::STATUS_TRIALING &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    public function isValid(): bool
    {
        return $this->isActive() || $this->isTrialing();
    }

    public function isCanceled(): bool
    {
        // Vrai si une annulation a été initiée (canceled_at est rempli)
        return ! is_null($this->canceled_at);
    }

    public function isPaused(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }

    /**
     * Vérifie si l'abonnement est totalement terminé (expiré).
     * ESSENTIEL pour la logique de la ressource.
     */
    public function hasEnded(): bool
    {
        // 1. Statut est explicitement 'expired'
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        // 2. Fin de période dépassée (pour les statuts actif, annulé ou en pause)
        if ($this->ends_at && $this->ends_at->isPast()) {
            return true;
        }

        // 3. Fin de la période d'essai dépassée
        if ($this->status === self::STATUS_TRIALING && $this->trial_ends_at && $this->trial_ends_at->isPast()) {
            return true;
        }

        return false;
    }

    /**
     * Vrai si l'abonnement est annulé mais toujours dans sa période payée/d'essai.
     */
    public function onGracePeriod(): bool
    {
        return $this->isCanceled() && $this->ends_at && $this->ends_at->isFuture();
    }

    /**
     * Annule l'abonnement à la fin de la période actuelle (grace period).
     */
    public function cancelAtPeriodEnd(?string $reason = null): bool
    {
        return $this->update([
            // Le statut reste 'active' ou 'trialing' jusqu'à ends_at
            'canceled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Annule l'abonnement immédiatement et le marque comme expiré.
     * Utilisé notamment pour les proratas/upgrades.
     */
    public function cancelImmediately(?string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_EXPIRED,
            'canceled_at' => now(),
            'ends_at' => now(), // Fin immédiate
            'cancellation_reason' => $reason,
        ]);
    }

    public function resume(): bool
    {
        // Ne peut résumer que si annulé ou en pause
        if (! $this->isCanceled() && ! $this->isPaused()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_ACTIVE,
            'canceled_at' => null,
            'paused_at' => null,
            'cancellation_reason' => null,
            // 'ends_at' ne doit pas être touché si on résume une annulation planifiée
        ]);
    }

    public function pause(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE && $this->status !== self::STATUS_TRIALING) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_PAUSED,
            'paused_at' => now(),
        ]);
    }

    public function unpause(): bool
    {
        if (! $this->isPaused()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_ACTIVE,
            'paused_at' => null,
        ]);
    }

    public function markAsExpired(): bool
    {
        return $this->update([
            'status' => self::STATUS_EXPIRED,
            // Optionnel: s'assurer que ends_at est bien dans le passé
            'ends_at' => $this->ends_at && $this->ends_at->isFuture() ? now() : $this->ends_at,
        ]);
    }
}
