<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
  /**
 * Model Action avec double classification sectorielle
 *
 * Classifications:
 * - brvm_sector_id: Secteur officiel BRVM
 * - classified_sector_id: Secteur reclassé personnalisé (SR)
 * */


class Action extends Model
{
    /** @use HasFactory<\Database\Factories\ActionFactory> */
    use HasFactory, HasKey;

    protected $fillable = [
        'key',
        'symbole',
        'nom',
        'brvm_sector_id',
        'classified_sector_id',
        'description',
        'volume',
        'cours_veille',
        'cours_ouverture',
        'cours_cloture',
        'variation',
    ];

    protected $casts = [
        'cours_veille' => 'decimal:2',
        'cours_ouverture' => 'decimal:2',
        'cours_cloture' => 'decimal:2',
        'variation' => 'decimal:2',
    ];

    /**
     * Route key name pour Route Model Binding
     * Permet d'utiliser 'key' au lieu de 'id' dans les routes
     */
    public function getRouteKeyName(): string{
        return 'key';
    }

    /**
     * Scope pour résolution par key
     */
    public function scopeByKey($query, string $key){
        return $query->where('key', $key);
    }

    /**
     * Relation avec secteur BRVM officiel
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
     * Relation avec données financières annuelles
     */
    public function stockFinancials(): HasMany{
        return $this->hasMany(StockFinancial::class);
    }

    /**
     * Relation avec résultats trimestriels
     */
    public function quarterlyResults(): HasMany{
        return $this->hasMany(QuarterlyResult::class);
    }


    /**
     * Scope pour résolution par symbole
     */
    public function scopeBySymbole($query, string $symbole){
        return $query->where('symbole', $symbole);
    }

    /**
     * Récupère les données financières pour une année
     */
    public function getFinancialForYear(int $year): ?StockFinancial{
        return $this->stockFinancials()
            ->where('year', $year)
            ->first();
    }

    /**
     * Récupère les données financières pour une plage d'années
     */
    public function getFinancialsForYears(int $startYear, int $endYear){
        return $this->stockFinancials()
            ->whereBetween('year', [$startYear, $endYear])
            ->orderBy('year', 'desc')
            ->get();
    }

    /**
     * Vérifie si l'action est dans le secteur services financiers
     */
    public function isFinancialService(): bool{
        return $this->brvmSector &&
               $this->brvmSector->slug === 'services-financiers';
    }

    /**
     * Données financières annuelles
     */
    public function financials(): HasMany{
        return $this->hasMany(StockFinancial::class)->orderBy('year', 'desc');
    }
    /**
     * Actionnaires
     */
    public function shareholders(): HasMany{
        return $this->hasMany(Shareholder::class);
    }
    /**
     * Employés / Direction
     */
    public function employees(): HasMany{
        return $this->hasMany(Employee::class);
    }
     /**
     * Relation: Snapshots quotidiens (historique 10 jours)
     */
    public function dailySnapshots(): HasMany{
        return $this->hasMany(ActionDailySnapshot::class)
            ->orderBy('snapshot_date', 'desc');
    }
    /**
     * Relation: Snapshots des 05 derniers jours uniquement
     */
         public function recentSnapshots(){
        return $this->dailySnapshots()
            ->lastDays(5);
    }
    /**
     * Dernière donnée financière disponible
     */
    public function latestFinancial(){
        return $this->hasOne(StockFinancial::class)->latestOfMany('year');
    }

    /**
     * Positions des employee à l'action
     */
    public function positions(): HasMany{
        return $this->hasMany(Position::class);
    }

    /**
     * Relation avec les prévisions financières | Analyze; prevision de rendements
     */
    public function forecast(){
        return $this->hasOne(FinancialForecast::class);
    }

    // Accessor pour le Rendement (Toujours calculé à la volée)
    // Rendement = (DNPA Prev / Cours Cloture) * 100 [cite: 47]
    public function getRendementPrevisionnelAttribute()
    {
        // On charge la prévision si elle n'est pas chargée
        if (!$this->relationLoaded('forecast')) {
            $this->load('forecast');
        }

        $dnpaPrev = $this->forecast->dnpa_previsionnel ?? 0;
        $cours = $this->cours_cloture;

        if ($cours > 0) {
            return round(($dnpaPrev / $cours) * 100, 2);
        }
        return 0;
    }

}
