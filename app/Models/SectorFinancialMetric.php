<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectorFinancialMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'sector_type',
        'sector_id',
        'year',
        'is_financial_sector',
        'companies_count',

        // Croissance - Moyennes
        'croissance_pnb_moy',
        'croissance_ca_moy',
        'croissance_ebit_moy',
        'croissance_ebitda_moy',
        'croissance_rn_moy',
        'croissance_capex_moy',
        'moy_croissance_moy',

        // Croissance - Écart-types
        'croissance_pnb_ecart_type',
        'croissance_ca_ecart_type',
        'croissance_ebit_ecart_type',
        'croissance_ebitda_ecart_type',
        'croissance_rn_ecart_type',
        'croissance_capex_ecart_type',
        'moy_croissance_ecart_type',

        // Rentabilité - Moyennes
        'marge_nette_moy',
        'marge_ebitda_moy',
        'marge_operationnelle_moy',
        'roe_moy',
        'roa_moy',
        'moy_rentabilite_moy',

        // Rentabilité - Écart-types
        'marge_nette_ecart_type',
        'marge_ebitda_ecart_type',
        'marge_operationnelle_ecart_type',
        'roe_ecart_type',
        'roa_ecart_type',
        'moy_rentabilite_ecart_type',

        // Rémunération - Moyennes
        'dnpa_moy',
        'rendement_dividendes_moy',
        'taux_distribution_moy',
        'moy_remuneration_moy',

        // Rémunération - Écart-types
        'dnpa_ecart_type',
        'rendement_dividendes_ecart_type',
        'taux_distribution_ecart_type',
        'moy_remuneration_ecart_type',

        // Valorisation - Moyennes
        'per_moy',
        'pbr_moy',
        'ratio_ps_moy',
        'ev_ebitda_moy',
        'moy_valorisation_moy',

        // Valorisation - Écart-types
        'per_ecart_type',
        'pbr_ecart_type',
        'ratio_ps_ecart_type',
        'ev_ebitda_ecart_type',
        'moy_valorisation_ecart_type',

        // Solidité - Moyennes
        'autonomie_financiere_moy',
        'ratio_prets_depots_moy',
        'loan_to_deposit_moy',
        'dette_capitalisation_moy',
        'endettement_actif_moy',
        'endettement_general_moy',
        'cout_du_risque_moy',
        'moy_solidite_moy',

        // Solidité - Écart-types
        'autonomie_financiere_ecart_type',
        'ratio_prets_depots_ecart_type',
        'loan_to_deposit_ecart_type',
        'dette_capitalisation_ecart_type',
        'endettement_actif_ecart_type',
        'endettement_general_ecart_type',
        'cout_du_risque_ecart_type',
        'moy_solidite_ecart_type',

        'calculated_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'is_financial_sector' => 'boolean',
        'companies_count' => 'integer',
        'calculated_at' => 'datetime',
    ];

    /**
     * Relation vers le secteur BRVM
     */
    public function brvmSector(): BelongsTo
    {
        return $this->belongsTo(BrvmSector::class, 'sector_id')
            ->where('sector_type', 'brvm');
    }

    /**
     * Relation vers le secteur classifié
     */
    public function classifiedSector(): BelongsTo
    {
        return $this->belongsTo(ClassifiedSector::class, 'sector_id')
            ->where('sector_type', 'classified');
    }

    /**
     * Scope pour le secteur BRVM
     */
    public function scopeBrvmSector($query)
    {
        return $query->where('sector_type', 'brvm');
    }

    /**
     * Scope pour le secteur classifié
     */
    public function scopeClassifiedSector($query)
    {
        return $query->where('sector_type', 'classified');
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
}
