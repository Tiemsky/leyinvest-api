<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Model;

class SharedHolding extends Model
{
    use HasKey;
    protected $guarded = [];

    public function action(){
        return $this->belongsTo(Action::class, 'action_id');
    }
}
