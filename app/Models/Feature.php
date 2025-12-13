<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Feature extends Model
{
    use HasKey,HasFactory;
    protected $fillable = ['key', 'name', 'slug', 'is_active'];

    // --- CONSTANTES DES CLÉS DE FEATURES (Crucial pour la maintenance) ---
    const KEY_MARKET_INDICATORS = 'indicateurs_marches';
    const KEY_NEWS = 'actualites';
    const KEY_STANDARD_ARTICLES = 'articles_standard';
    const KEY_MY_LIST = 'ma_liste';
    const KEY_COMPANY_PRESENTATION = 'presentation_entreprise';
    const KEY_FINANCIAL_INDICATORS = 'indicateurs_financiers';
    const KEY_CALCULATOR = 'calculateur';
    const KEY_DIVIDEND_CALENDAR = 'calendrier_dividendes';
    const KEY_EVALUATIONS = 'evaluations';
    const KEY_COMPLETE_INDICATORS = 'indicateurs_complets';
    const KEY_COMPANY_HISTORY = 'historique_entreprise';
    const KEY_NOTIFICATIONS = 'notifications';
    const KEY_PREMIUM_ARTICLES = 'articles_premium';
    const KEY_YIELD_FORECAST = 'prevision_rendement';

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relations
     */
    public function plans(): BelongsToMany{
        return $this->belongsToMany(Plan::class, 'plan_features')
            ->withPivot(['is_enabled'])
            ->withTimestamps();
    }

    /**
     * Scopes
     */
    public function scopeActive($query){
        return $query->where('is_active', true);
    }

}
