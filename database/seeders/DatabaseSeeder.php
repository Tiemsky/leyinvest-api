<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Database\Seeders\RoleSeeder;
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
            CountrySeeder::class,
            RoleSeeder::class,
            PositionSeeder::class,
            FeatureSeeder::class,
            PlanSeeder::class,
            CouponSeeder::class,
            BrvmSectorSeeder::class,
            ClassifiedSectorSeeder::class,
            ActionSeeder::class,
            TopSeeder::class,
            FlopSeeder::class,
            BocIndicatorSeeder::class,
            UserSeeder::class,
        ]);
        //  User::factory(5)->create();
    }
}
