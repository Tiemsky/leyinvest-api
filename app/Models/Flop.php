<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Flop extends Model
{
    /** @use HasFactory<\Database\Factories\FlopFactory> */
    use HasFactory,  HasKey;
    protected $guarded = [];
}
