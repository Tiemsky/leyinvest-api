<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Models\Purchase;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // RoleSeeder::class,
            ActionSeeder::class,
            TopSeeder::class,
            FlopSeeder::class,
        ]);
         User::factory(500)->create();
         Wallet::factory()->count(500)->create();
         Purchase::factory()->count(700)->create();
         Sale::factory()->count(700)->create();
    }
}
