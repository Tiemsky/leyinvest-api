<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuarterlyResult extends Model
{
    protected $fillable = [
        'action_id',
        'year',
        'trimestre',
        'chiffre_affaires',
        'evolution_ca',
        'resultat_net',
        'evolution_rn',
    ];

    protected $casts = [
        'year' => 'integer',
        'trimestre' => 'integer',
        'chiffre_affaires' => 'decimal:2',
        'evolution_ca' => 'decimal:2',
        'resultat_net' => 'decimal:2',
        'evolution_rn' => 'decimal:2',
    ];

    public function action(): BelongsTo
    {
        return $this->belongsTo(Action::class);
    }
}
