<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockFinancialMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'action_id',
        'year',
        'is_financial_sector',

        // Croissance SECTEUR FINANCIER
        'croissance_pnb',
        'croissance_ebit_sf',
        'croissance_ebitda_sf',
        'croissance_rn_sf',
        'croissance_capex_sf',
        'moy_croissance_sf',

        // Croissance AUTRES SECTEURS
        'croissance_ca',
        'croissance_ebit_as',
        'croissance_ebitda_as',
        'croissance_rn_as',
        'croissance_capex_as',
        'moy_croissance_as',

        // Rentabilité
        'marge_nette',
        'marge_ebitda',
        'marge_operationnelle',
        'roe',
        'roa',
        'moy_rentabilite',

        // Rémunération
        'dnpa_calculated',
        'rendement_dividendes',
        'taux_distribution',
        'moy_remuneration',

        // Valorisation
        'per',
        'pbr',
        'ratio_ps',
        'ev_ebitda',
        'cours_cible',
        'potentiel_hausse',
        'moy_valorisation',

        // Solidité SECTEUR FINANCIER
        'autonomie_financiere',
        'ratio_prets_depots',
        'loan_to_deposit',
        'endettement_general_sf',
        'cout_du_risque_value',
        'moy_solidite_sf',

        // Solidité AUTRES SECTEURS
        'dette_capitalisation',
        'endettement_actif',
        'endettement_general_as',
        'moy_solidite_as',

        'calculated_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'is_financial_sector' => 'boolean',
        'calculated_at' => 'datetime',
    ];

    /**
     * Relation vers l'action
     */
    public function action(): BelongsTo
    {
        return $this->belongsTo(Action::class);
    }

    /**
     * Scope pour filtrer par année
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope pour filtrer les secteurs financiers
     */
    public function scopeFinancialSector($query)
    {
        return $query->where('is_financial_sector', true);
    }

    /**
     * Scope pour filtrer les autres secteurs
     */
    public function scopeOtherSector($query)
    {
        return $query->where('is_financial_sector', false);
    }
}
