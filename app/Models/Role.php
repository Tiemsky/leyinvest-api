<?php

namespace App\Models;

use App\Enums\RoleEnum;
use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'role' => RoleEnum::class
        ];
    }
}
