<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasKey;

    protected $guarded = [];
    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relations
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'subscriptions')
            ->withPivot(['status', 'starts_at', 'ends_at', 'canceled_at'])
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeFree($query)
    {
        return $query->where('price', 0);
    }

    // Méthodes utilitaires
    public function isFree()
    {
        return $this->price == 0;
    }

    public function hasFeature($feature)
    {
        return isset($this->features[$feature]) && $this->features[$feature] === true;
    }

    public function getFeaturesList()
    {
        return collect($this->features)
            ->filter(fn($value) => $value === true)
            ->keys()
            ->toArray();
    }
}
