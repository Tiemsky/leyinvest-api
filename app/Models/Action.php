<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Action extends Model
{
    /** @use HasFactory<\Database\Factories\ActionFactory> */
    use HasFactory, HasKey;
    protected $guarded = [];

    public function getRouteKeyName(): string{
        return 'key';
    }

    public function brvmSector(){
        return $this->belongsTo(BrvmSector::class, 'brvm_sector_id');
    }

    public function classifiedSector(){
        return $this->belongsTo(ClassifiedSector::class, 'classified_sector_id');
    }

       /**
     * Données financières annuelles (relation 1-N)
     */
    public function financials(): HasMany
    {
        return $this->hasMany(StockFinancial::class)->orderBy('year', 'desc');
    }

     /**
     * Relation: Snapshots quotidiens (historique 10 jours)
     */
    public function dailySnapshots(): HasMany
    {
        return $this->hasMany(ActionDailySnapshot::class)
            ->orderBy('snapshot_date', 'desc');
    }

    /**
     * Relation: Snapshots des 05 derniers jours uniquement
     */
    public function recentSnapshots()
    {
        return $this->dailySnapshots()
            ->lastDays(5);
    }



       /**
     * Actionnaires (relation 1-N)
     */
    public function shareholders(): HasMany{
        return $this->hasMany(Shareholder::class)->orderBy('rang');
    }

    /**
     * Dernière donnée financière disponible
     */
    public function latestFinancial(){
        return $this->hasOne(StockFinancial::class)->latestOfMany('year');
    }

    public function ratios()
    {
        return $this->hasMany(ActionRatio::class)->orderBy('year', 'desc');
    }

    /**
     * Dernier ratio calculé
     */
    public function latestRatio()
    {
        return $this->hasOne(ActionRatio::class)->latestOfMany('year');
    }

    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    public function employees(){
        return $this->hasMany(Employee::class);
    }



}
