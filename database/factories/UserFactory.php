<?php

namespace Database\Factories;

use App\Models\Role;
use App\Enums\RoleEnum;
use App\Models\Country;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        $genre = fake()->randomElement(['Homme', 'Femme']);

        return [
            'key' => 'usr-' . strtolower(Str::random(8)),
            'country_id' => Country::inRandomOrder()->first()->id?? Country::factory(),
            'role' => RoleEnum::USER->value,
            'nom' => fake()->lastName(),
            'prenoms' => fake()->firstName($genre === 'Homme' ? 'male' : 'female'),
            'genre' => $genre,
            'age' => fake()->optional(0.8)->numberBetween(18, 65),
            'situation_professionnelle' => fake()->optional(0.7)->randomElement([
                'Employé',
                'Indépendant',
                'Étudiant',
                'Sans emploi',
                'Retraité',
                'Chef d\'entreprise',
                'Cadre',
                'Fonctionnaire',
            ]),
            'numero' => fake()->optional(0.9)->numerify('+225 ## ## ## ## ##'),
            'whatsapp' => fake()->optional(0.6)->numerify('+225 ## ## ## ## ##'),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'otp_code'=> null,
            'otp_expires_at'=> null,
            'avatar' => fake()->optional(0.3)->imageUrl(200, 200, 'people', true),
            'remember_token' => Str::random(10),
        ];
    }


    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
            'otp_code_verified_at' => null,
        ]);
    }
        /**
     * Indicate that the user is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => now(),
            'otp_code_verified_at' => now(),
            'otp_code' => null,
            'otp_code_send_at' => null,
            'otp_code_expired_at' => null,
        ]);
    }


    /**
     * Create a user with pending OTP verification.
     */
    public function withPendingOtp(): static
    {
        $otpCode = fake()->numerify('######');
        $sendAt = now();

        return $this->state(fn (array $attributes) => [
            'otp_code' => $otpCode,
            'otp_code_send_at' => $sendAt,
            'otp_code_verified_at' => null,
            'otp_code_expired_at' => $sendAt->copy()->addMinutes(10),
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a user with expired OTP.
     */
    public function withExpiredOtp(): static
    {
        $sendAt = now()->subMinutes(15);

        return $this->state(fn (array $attributes) => [
            'otp_code' => fake()->numerify('######'),
            'otp_code_send_at' => $sendAt,
            'otp_code_verified_at' => null,
            'otp_code_expired_at' => $sendAt->copy()->addMinutes(10),
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a user with verified OTP.
     */
    public function withVerifiedOtp(): static
    {
        return $this->state(fn (array $attributes) => [
            'otp_code' => null,
            'otp_code_send_at' => now()->subMinutes(5),
            'otp_code_verified_at' => now(),
            'otp_code_expired_at' => null,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Create a male user.
     */
    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            'genre' => 'Homme',
            'prenoms' => fake()->firstName('male'),
        ]);
    }

    /**
     * Create a female user.
     */
    public function female(): static
    {
        return $this->state(fn (array $attributes) => [
            'genre' => 'Femme',
            'prenoms' => fake()->firstName('female'),
        ]);
    }

     /**
     * Create an admin user.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::where('name', RoleEnum::ADMIN)->first()?->id
                ?? Role::factory()->create(['name' => RoleEnum::ADMIN])->id,
            'nom' => 'Admin',
            'prenoms' => 'Système',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'otp_code_verified_at' => now(),
            'situation_professionnelle' => 'Administrateur',
        ]);
    }

    /**
     * Create a user with a specific role.
     */
    public function withRole(RoleEnum|Role|int $role): static
    {
        return $this->state(function (array $attributes) use ($role) {
            if ($role instanceof RoleEnum) {
                $roleId = Role::where('name', $role)->first()?->id
                    ?? Role::factory()->create(['name' => $role])->id;
            } elseif ($role instanceof Role) {
                $roleId = $role->id;
            } else {
                $roleId = $role;
            }

            return ['role_id' => $roleId];
        });
    }

    /**
     * Create a user with a specific country.
     */
    public function forCountry(Country|int $country): static
    {
        return $this->state(fn (array $attributes) => [
            'country_id' => $country instanceof Country ? $country->id : $country,
        ]);
    }
}
