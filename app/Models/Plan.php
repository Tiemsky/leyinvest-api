<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasKey, SoftDeletes;

    /**
     * Champs autorisés pour l'assignation massive (Mass Assignment)
     */
    protected $fillable = [
        'nom', 'slug', 'prix', 'description', 'billing_cycle',
        'is_active', 'is_visible', 'trial_days', 'sort_order',
        'stripe_price_id', 'features', // 'features' gardé pour la migration
    ];

    protected $casts = [
        'features' => 'array',
        'prix' => 'decimal:2',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
        'trial_days' => 'integer',
        'sort_order' => 'integer',
    ];

    // --- Relations ---

    /**
     * Les abonnements liés à ce plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Les utilisateurs qui ont actuellement souscrit à ce plan.
     * Utilise la table pivot 'subscriptions'.
     */
    public function currentSubscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subscriptions', 'plan_id', 'user_id')
            ->using(Subscription::class) // Optionnel: Si Subscription est un modèle Pivot
            ->wherePivot('status', 'active')
            ->wherePivotNull('canceled_at');
    }

    /**
     * Les fonctionnalités configurées pour ce plan.
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'plan_features')
            ->withPivot(['is_enabled'])
            ->withTimestamps();
    }

    /**
     * Les fonctionnalités actives (où is_enabled est vrai) pour ce plan.
     */
    public function activeFeatures(): BelongsToMany
    {
        return $this->features()
            ->wherePivot('is_enabled', true);
        // On retire la condition 'features.is_active' car la relation features() devrait déjà la gérer si nécessaire
    }

    // --- Scopes ---

    /**
     * Récupère les plans actifs et les trie.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Récupère les plans visibles publiquement.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    /**
     * Récupère les plans gratuits.
     */
    public function scopeFree(Builder $query): Builder
    {
        // Utilise la colonne castée 'prix' pour garantir la comparaison décimale
        return $query->where('prix', 0.00);
    }

    // --- Méthodes utilitaires ---

    /**
     * Vérifie si le plan est gratuit.
     */
    public function isFree(): bool
    {
        return $this->prix == 0.00;
    }

    /**
     * Vérifie si le plan dispose d'une fonctionnalité spécifique.
     * Optimisé pour le nouveau système avec fallback sur l'ancien (JSON).
     */
    public function hasFeature(string $featureKey): bool
    {
        // 1. Nouveau système (Table pivot)
        $hasInNewSystem = $this->activeFeatures()
            ->whereHas('feature', function (Builder $query) use ($featureKey) {
                // Recherche directement par la clé du modèle Feature
                $query->where('key', $featureKey);
            })
            ->exists();

        if ($hasInNewSystem) {
            return true;
        }

        // 2. Fallback sur l'ancien système (Champ JSON)
        if ($this->features && is_array($this->features) && isset($this->features[$featureKey])) {
            // Assurez-vous que la valeur est TRUE dans le JSON
            return $this->features[$featureKey] === true;
        }

        return false;
    }
}
