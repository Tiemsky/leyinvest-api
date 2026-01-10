<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Model;

class BocIndicator extends Model
{
    use HasKey;

    protected $fillable = [
        'key',
        'date_rapport',
        'taux_rendement_moyen',
        'per_moyen',
        'taux_rentabilite_moyen',
        'prime_risque_marche',
        'source_pdf',
    ];
}
