<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\RoleEnum;
use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Exemple de récupération d’un pays (optionnel)
        $countryId = Country::inRandomOrder()->value('id');

        // USER 1
        User::create([
            'key' => "use". time(),
            'google_id' => null,
            'country_id' => $countryId,
            'role' =>  RoleEnum::ADMIN->value,
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'tiafranck31@yahoo.fr',
            'genre' => 'Homme',
            'age' => '28',
            'situation_professionnelle' => 'Développeur',
            'numero' => '0102030405',
            'whatsapp' => '0102030405',
            'password' => Hash::make('password'),
            'otp_code' => null,
            'otp_expires_at' => null,
            'email_verified' => true,
            'registration_completed' => true,
            'avatar' => null,
            'auth_provider' => 'email',
        ]);

        // USER 2
        User::create([
            'key' => "use". (time() + 1),
            'google_id' => null,
            'country_id' => $countryId,
            'role' =>  RoleEnum::USER->value,
            'nom' => 'Smith',
            'prenom' => 'Anna',
            'email' => 'tiemksy@gmail.com',
            'genre' => 'Homme',
            'age' => '25',
            'situation_professionnelle' => 'Analyste',
            'numero' => '0708091011',
            'whatsapp' => '0708091011',
            'password' => Hash::make('password'),
            'otp_code' => null,
            'otp_expires_at' => null,
            'email_verified' => true,
            'registration_completed' => true,
            'avatar' => null,
            'auth_provider' => 'email',
        ]);

        User::create([
            'key' => "use". (time() + 2),
            'google_id' => null,
            'country_id' => $countryId,
            'role' =>  RoleEnum::USER->value,
            'nom' => 'Pro',
            'prenom' => 'User',
            'email' => 'tiemsky@yahoo.com',
            'genre' => 'Homme',
            'age' => '25',
            'situation_professionnelle' => 'Analyste',
            'numero' => '0708091011',
            'whatsapp' => '0708091011',
            'password' => Hash::make('password'),
            'otp_code' => null,
            'otp_expires_at' => null,
            'email_verified' => true,
            'registration_completed' => true,
            'avatar' => null,
            'auth_provider' => 'email',
        ]);
    }
}
