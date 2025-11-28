<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model SectorBenchmark - Benchmarks sectoriels
 *
 * 2 Types:
 * - secteur_brvm: Moyenne secteur BRVM (brvm_sector_id NOT NULL)
 * - secteur_reclasse: Moyenne secteur reclassé (classified_sector_id NOT NULL)
 *
 * @property int $id
 * @property int|null $brvm_sector_id
 * @property int|null $classified_sector_id
 * @property int $year
 * @property string $horizon
 * @property string $type
 * @property array $croissance_avg
 * @property array $croissance_std
 * @property array $rentabilite_avg
 * @property array $rentabilite_std
 * @property array $remuneration_avg
 * @property array $remuneration_std
 * @property array $valorisation_avg
 * @property array $valorisation_std
 * @property array $solidite_avg
 * @property array $solidite_std
 * @property \Carbon\Carbon $calculated_at
 */
class SectorBenchmark extends Model
{
    use HasFactory;

    protected $fillable = [
        'brvm_sector_id',
        'classified_sector_id',
        'year',
        'horizon',
        'type',
        'croissance_avg',
        'croissance_std',
        'rentabilite_avg',
        'rentabilite_std',
        'remuneration_avg',
        'remuneration_std',
        'valorisation_avg',
        'valorisation_std',
        'solidite_avg',
        'solidite_std',
        'calculated_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'croissance_avg' => 'array',
        'croissance_std' => 'array',
        'rentabilite_avg' => 'array',
        'rentabilite_std' => 'array',
        'remuneration_avg' => 'array',
        'remuneration_std' => 'array',
        'valorisation_avg' => 'array',
        'valorisation_std' => 'array',
        'solidite_avg' => 'array',
        'solidite_std' => 'array',
        'calculated_at' => 'datetime',
    ];

    /**
     * Relation avec secteur BRVM
     */
    public function brvmSector(): BelongsTo{
        return $this->belongsTo(BrvmSector::class, 'brvm_sector_id');
    }

    /**
     * Relation avec secteur reclassé
     */
    public function classifiedSector(): BelongsTo{
        return $this->belongsTo(ClassifiedSector::class, 'classified_sector_id');
    }

    /**
     * Scope pour benchmarks secteur BRVM
     */
    public function scopeBrvmSector($query, int $sectorId, int $year, string $horizon){
        return $query->where('type', 'secteur_brvm')
            ->where('brvm_sector_id', $sectorId)
            ->where('year', $year)
            ->where('horizon', $horizon);
    }

    /**
     * Scope pour benchmarks secteur reclassé
     */
    public function scopeClassifiedSector($query, int $sectorId, int $year, string $horizon){
        return $query->where('type', 'secteur_reclasse')
            ->where('classified_sector_id', $sectorId)
            ->where('year', $year)
            ->where('horizon', $horizon);
    }

    /**
     * Récupère la valeur d'un indicateur
     */
    public function getIndicatorAverage(string $category, string $indicator): ?float{
        $categoryKey = $category . '_avg';
        $data = $this->$categoryKey ?? [];
        return $data[$indicator] ?? null;
    }

    /**
     * Récupère l'écart-type d'un indicateur
     */
    public function getIndicatorStd(string $category, string $indicator): ?float{
        $categoryKey = $category . '_std';
        $data = $this->$categoryKey ?? [];
        return $data[$indicator] ?? null;
    }
}
