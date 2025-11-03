<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Model;

class ClassifiedSector extends Model
{
    use HasKey;
    protected $guarded = [];

    public function actions(){
        return $this->hasMany(Action::class, 'classified_sector_id');
    }
}
