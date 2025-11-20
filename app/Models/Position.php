<?php

namespace App\Models;

use App\Models\Action;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Position extends Model
{
    protected $guarded = [];
    public function action(): BelongsTo{
        return $this->belongsTo(Action::class);
    }

    public function employees(): HasMany{
        return $this->hasMany(Employee::class);
    }
}
