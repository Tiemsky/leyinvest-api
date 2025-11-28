<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model QuarterlyResult - Résultats trimestriels
 *
 * @property int $id
 * @property int $action_id
 * @property int $year
 * @property int $quarter
 * @property float|null $produit_net_bancaire
 * @property float|null $chiffre_affaires
 * @property float|null $resultat_net
 * @property float|null $ebit
 * @property float|null $ebitda
 * @property float|null $evolution_pnb
 * @property float|null $evolution_ca
 * @property float|null $evolution_rn
 * @property float|null $evolution_yoy_pnb
 * @property float|null $evolution_yoy_ca
 * @property float|null $evolution_yoy_rn
 */
class QuarterlyResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'action_id',
        'year',
        'quarter',
        'produit_net_bancaire',
        'chiffre_affaires',
        'resultat_net',
        'ebit',
        'ebitda',
        'evolution_pnb',
        'evolution_ca',
        'evolution_rn',
        'evolution_yoy_pnb',
        'evolution_yoy_ca',
        'evolution_yoy_rn',
    ];

    protected $casts = [
        'year' => 'integer',
        'quarter' => 'integer',
        'produit_net_bancaire' => 'decimal:2',
        'chiffre_affaires' => 'decimal:2',
        'resultat_net' => 'decimal:2',
        'ebit' => 'decimal:2',
        'ebitda' => 'decimal:2',
        'evolution_pnb' => 'decimal:2',
        'evolution_ca' => 'decimal:2',
        'evolution_rn' => 'decimal:2',
        'evolution_yoy_pnb' => 'decimal:2',
        'evolution_yoy_ca' => 'decimal:2',
        'evolution_yoy_rn' => 'decimal:2',
    ];

    /**
     * Relation avec action
     */
    public function action(): BelongsTo{
        return $this->belongsTo(Action::class);
    }

    /**
     * Scope pour une année
     */
    public function scopeForYear($query, int $year){
        return $query->where('year', $year)->orderBy('quarter');
    }

    /**
     * Scope pour un trimestre spécifique
     */
    public function scopeForQuarter($query, int $year, int $quarter){
        return $query->where('year', $year)->where('quarter', $quarter);
    }

    /**
     * Retourne le revenu selon le secteur
     */
    public function getRevenueAttribute(): ?float{
        return $this->produit_net_bancaire ?? $this->chiffre_affaires;
    }

    /**
     * Calcule l'évolution vs trimestre précédent
     */
    public static function calculateEvolution(Action $action, int $year, int $quarter): array{
        $current = self::forQuarter($year, $quarter)
            ->where('action_id', $action->id)
            ->first();

        if (!$current) {
            return [];
        }

        // Trimestre précédent
        $previousQuarter = $quarter - 1;
        $previousYear = $year;
        if ($previousQuarter < 1) {
            $previousQuarter = 4;
            $previousYear = $year - 1;
        }

        $previous = self::forQuarter($previousYear, $previousQuarter)
            ->where('action_id', $action->id)
            ->first();

        $evolutions = [];

        if ($previous) {
            if ($current->produit_net_bancaire && $previous->produit_net_bancaire) {
                $evolutions['evolution_pnb'] = (($current->produit_net_bancaire - $previous->produit_net_bancaire) / abs($previous->produit_net_bancaire)) * 100;
            }
            if ($current->chiffre_affaires && $previous->chiffre_affaires) {
                $evolutions['evolution_ca'] = (($current->chiffre_affaires - $previous->chiffre_affaires) / abs($previous->chiffre_affaires)) * 100;
            }
            if ($current->resultat_net && $previous->resultat_net) {
                $evolutions['evolution_rn'] = (($current->resultat_net - $previous->resultat_net) / abs($previous->resultat_net)) * 100;
            }
        }

        // Même trimestre année précédente (YoY)
        $previousYearSameQuarter = self::forQuarter($year - 1, $quarter)
            ->where('action_id', $action->id)
            ->first();

        if ($previousYearSameQuarter) {
            if ($current->produit_net_bancaire && $previousYearSameQuarter->produit_net_bancaire) {
                $evolutions['evolution_yoy_pnb'] = (($current->produit_net_bancaire - $previousYearSameQuarter->produit_net_bancaire) / abs($previousYearSameQuarter->produit_net_bancaire)) * 100;
            }
            if ($current->chiffre_affaires && $previousYearSameQuarter->chiffre_affaires) {
                $evolutions['evolution_yoy_ca'] = (($current->chiffre_affaires - $previousYearSameQuarter->chiffre_affaires) / abs($previousYearSameQuarter->chiffre_affaires)) * 100;
            }
            if ($current->resultat_net && $previousYearSameQuarter->resultat_net) {
                $evolutions['evolution_yoy_rn'] = (($current->resultat_net - $previousYearSameQuarter->resultat_net) / abs($previousYearSameQuarter->resultat_net)) * 100;
            }
        }

        return $evolutions;
    }
}
