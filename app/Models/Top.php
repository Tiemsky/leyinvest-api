<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Top extends Model
{
    /** @use HasFactory<\Database\Factories\TopFactory> */
    use HasFactory, HasKey;

    protected $guarded = [];
}
