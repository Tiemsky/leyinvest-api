<?php

namespace Database\Seeders;
use App\Models\Role;
use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{

    public function run(): void
    {
        $roles = [
            ['name' => RoleEnum::ADMIN->value],
            ['name' => RoleEnum::USER->value],

        ];

        Role::truncate();
        foreach($roles as $role){
            Role::create($role);
        }
    }

}
