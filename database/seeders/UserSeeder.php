<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Country;
use App\Models\Role;
use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles first
        $adminRole = Role::factory()->admin()->create();
        $userRole = Role::factory()->user()->create();
        $moderatorRole = Role::factory()->moderator()->create();

        // Create countries
        $coteDivoire = Country::factory()->coteIvoire()->create();
        $france = Country::factory()->france()->create();
        $senegal = Country::factory()->senegal()->create();

        // Create super admin (fully verified)
        User::factory()
            ->admin()
            ->verified()
            ->forCountry($coteDivoire)
            ->create([
                'email' => 'admin@example.com',
                'nom' => 'Admin',
                'prenom' => 'Super',
            ]);

        // Create moderators (verified)
        User::factory()
            ->count(2)
            ->withRole(RoleEnum::MODERATOR)
            ->verified()
            ->complete()
            ->forCountry($coteDivoire)
            ->create();

        // Create verified users from Côte d'Ivoire
        User::factory()
            ->count(20)
            ->verified()
            ->forCountry($coteDivoire)
            ->create();

        // Create users with pending OTP verification
        User::factory()
            ->count(5)
            ->withPendingOtp()
            ->forCountry($coteDivoire)
            ->create();

        // Create users with expired OTP
        User::factory()
            ->count(3)
            ->withExpiredOtp()
            ->forCountry($senegal)
            ->create();

        // Create verified users from France
        User::factory()
            ->count(10)
            ->verified()
            ->complete()
            ->forCountry($france)
            ->create();

        // Create unverified students
        User::factory()
            ->count(5)
            ->unverified()
            ->student()
            ->forCountry($senegal)
            ->create();

        // Create test user for development
        User::factory()
            ->verified()
            ->complete()
            ->withPassword('password')
            ->create([
                'email' => 'test@example.com',
                'nom' => 'Test',
                'prenom' => 'User',
            ]);
    }
}
