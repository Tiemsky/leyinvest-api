<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionRatio extends Model
{

    protected $guarded = [];
    protected $casts = [
        'year' => 'integer',
        'calculated_at' => 'datetime',
    ];

    public function action()
    {
        return $this->belongsTo(Action::class);
    }

    public function financial()
    {
        return $this->hasOne(StockFinancial::class, 'action_id', 'action_id')
            ->where('year', $this->year);
    }
}
