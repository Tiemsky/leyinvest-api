<?php

namespace Database\Factories;

use App\Enums\RoleEnum;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
            'key' => 'usr-'.strtolower(Str::random(8)),
            'country_id' => Country::inRandomOrder()->first()->id ?? Country::factory(),
            'role' => RoleEnum::USER->value,
            'nom' => $this->faker->lastName,
            'prenom' => $this->faker->firstName,
            'age' => $this->faker->optional(0.8)->numberBetween(18, 65),
            'situation_professionnelle' => $this->faker->optional(0.7)->randomElement([
                'Employé',
                'Indépendant',
                'Étudiant',
                'Sans emploi',
                'Retraité',
                'Chef d\'entreprise',
                'Cadre',
                'Fonctionnaire',
            ]),
            'numero' => $this->faker->optional(0.9)->numerify('+225 ## ## ## ## ##'),
            'whatsapp' => $this->faker->optional(0.6)->numerify('+225 ## ## ## ## ##'),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'otp_code' => null,
            'otp_expires_at' => null,
            'avatar' => $this->faker->optional(0.3)->imageUrl(200, 200, 'people', true),
            'remember_token' => Str::random(10),
        ];
    }
}
