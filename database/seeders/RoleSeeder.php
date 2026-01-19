<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => RoleEnum::ADMIN->value],
            ['name' => RoleEnum::USER->value],

        ];

        Role::truncate();
        foreach ($roles as $role) {
            Role::create(array_merge($role, [
                'key' => 'rol-'.strtolower(Str::random(8)),
            ]));
        }
    }
}
