<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    protected $guarded = [];

    public function action(): BelongsTo
    {
        return $this->belongsTo(Action::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
