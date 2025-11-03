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

    public function brvmSector(){
        return $this->belongsTo(BrvmSector::class, 'brvm_sector_id');
    }

    public function classifiedSector(){
        return $this->belongsTo(ClassifiedSector::class, 'classified_sector_id');
    }

}
