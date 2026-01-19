<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasKey;

    protected $guarded = [];
}
