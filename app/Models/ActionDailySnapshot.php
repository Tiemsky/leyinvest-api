<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ActionDailySnapshot extends Model
{
     // Pas de updated_at (snapshot immuable)
     const UPDATED_AT = null;

     protected $fillable = [
         'action_id',
         'snapshot_date',
         'symbole',
         'nom',
         'volume',
         'cours_veille',
         'cours_ouverture',
         'cours_cloture',
         'variation',
     ];

     protected $casts = [
         'snapshot_date' => 'date',
         'volume' => 'integer',
         'cours_veille' => 'decimal:2',
         'cours_ouverture' => 'decimal:2',
         'cours_cloture' => 'decimal:2',
         'variation' => 'decimal:2',
         'created_at' => 'datetime',
     ];

     /**
      * Relation inverse vers Action
      */
     public function action()
     {
         return $this->belongsTo(Action::class);
     }

     /**
      * Scope: récupère les N derniers jours
      */
     public function scopeLastDays($query, int $days = 10)
     {
         $startDate = Carbon::now()->subDays($days - 1)->startOfDay();
         return $query->where('snapshot_date', '>=', $startDate);
     }

     /**
      * Scope: récupère pour une action spécifique
      */
     public function scopeForAction($query, $actionId)
     {
         return $query->where('action_id', $actionId);
     }

     /**
      * Scope: récupère pour un symbole spécifique
      */
     public function scopeForSymbol($query, string $symbole)
     {
         return $query->where('symbole', $symbole);
     }
}
