<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model StockFinancial - Données financières annuelles
 *
 * Toutes les données sont DÉJÀ EN BASE
 * Les calculateurs calculent uniquement les RATIOS
 *
 * @property int $id
 * @property int $action_id
 * @property int $year
 * @property int|null $nombre_titre
 * @property float|null $total_immobilisation
 * @property float|null $actif_circulant
 * @property float|null $total_actif
 * @property float|null $credits_clientele SF only
 * @property float|null $depots_clientele SF only
 * @property float|null $capitaux_propres
 * @property float|null $passif_circulant
 * @property float|null $dette_totale
 * @property float|null $chiffre_affaires Autres secteurs
 * @property float|null $produit_net_bancaire SF only
 * @property float|null $valeur_ajoutee
 * @property float|null $ebit
 * @property float|null $ebitda
 * @property float|null $resultat_avant_impot
 * @property float|null $resultat_net
 * @property float|null $cout_du_risque SF only
 * @property float|null $capex
 * @property float|null $dividendes_bruts
 * @property float|null $per
 * @property float|null $dnpa
 * @property float|null $cours_31_12
 */
class StockFinancial extends Model
{
    use HasFactory;

    protected $fillable = [
        'action_id',
        'year',
        'nombre_titre',
        // BILAN - ACTIF
        'total_immobilisation',
        'actif_circulant',
        'total_actif',
        'credits_clientele',
        'depots_clientele',
        // BILAN - PASSIF
        'capitaux_propres',
        'passif_circulant',
        'dette_totale',
        // COMPTE DE RÉSULTAT
        'chiffre_affaires',
        'produit_net_bancaire',
        'valeur_ajoutee',
        'ebit',
        'ebitda',
        'resultat_avant_impot',
        'resultat_net',
        'cout_du_risque',
        // INVESTISSEMENTS & DISTRIBUTION
        'capex',
        'dividendes_bruts',
        // INDICATEURS BOURSIERS
        'per',
        'dnpa',
        'cours_31_12',
    ];

    protected $casts = [
        'year' => 'integer',
        'nombre_titre' => 'integer',
        'total_immobilisation' => 'decimal:2',
        'actif_circulant' => 'decimal:2',
        'total_actif' => 'decimal:2',
        'credits_clientele' => 'decimal:2',
        'depots_clientele' => 'decimal:2',
        'capitaux_propres' => 'decimal:2',
        'passif_circulant' => 'decimal:2',
        'dette_totale' => 'decimal:2',
        'chiffre_affaires' => 'decimal:2',
        'produit_net_bancaire' => 'decimal:2',
        'valeur_ajoutee' => 'decimal:2',
        'ebit' => 'decimal:2',
        'ebitda' => 'decimal:2',
        'resultat_avant_impot' => 'decimal:2',
        'resultat_net' => 'decimal:2',
        'cout_du_risque' => 'decimal:2',
        'capex' => 'decimal:2',
        'dividendes_bruts' => 'decimal:2',
        'per' => 'decimal:2',
        'dnpa' => 'decimal:2',
        'cours_31_12' => 'decimal:2',
    ];

    /**
     * Relation avec action
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
     * Scope pour plage d'années
     */
    public function scopeBetweenYears($query, int $startYear, int $endYear)
    {
        return $query->whereBetween('year', [$startYear, $endYear]);
    }

    /**
     * Retourne le revenu principal selon le secteur
     * - Services financiers: produit_net_bancaire
     * - Autres secteurs: chiffre_affaires
     */
    public function getRevenueAttribute(): ?float
    {
        return $this->produit_net_bancaire ?? $this->chiffre_affaires;
    }

    /**
     * Vérifie si c'est un service financier
     */
    public function isFinancialService(): bool
    {
        return ! is_null($this->produit_net_bancaire);
    }

    /**
     * Calcule la capitalisation boursière
     */
    public function getCapitalisationAttribute(): ?float
    {
        if (! $this->cours_31_12 || ! $this->nombre_titre) {
            return null;
        }

        return $this->cours_31_12 * $this->nombre_titre;
    }

    /**
     * Calcule le BNPA (Bénéfice Net Par Action)
     */
    public function getBnpaAttribute(): ?float
    {
        if (! $this->resultat_net || ! $this->nombre_titre || $this->nombre_titre == 0) {
            return null;
        }

        return $this->resultat_net / $this->nombre_titre;
    }

    /**
     * Vérifie la cohérence du bilan (Actif ≈ Passif)
     * Tolérance: 1%
     */
    public function isBilanBalanced(): bool
    {
        if (! $this->total_actif || ! $this->capitaux_propres) {
            return false;
        }

        $totalPassif = $this->capitaux_propres
            + ($this->passif_circulant ?? 0)
            + ($this->dette_totale ?? 0);

        if ($this->total_actif == 0) {
            return false;
        }

        $ecart = abs($this->total_actif - $totalPassif) / $this->total_actif;

        return $ecart < 0.01;
    }
}
