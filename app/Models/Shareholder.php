<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shareholder extends Model
{
    // Champs modifiables en masse
    protected $guarded = [];

    // Conversions de types
    protected $casts = [
        'percentage' => 'decimal:2',
        'rang' => 'integer',
    ];

    public function action(): BelongsTo
    {
        return $this->belongsTo(Action::class);
    }
}
