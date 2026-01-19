<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantite = $this->faker->numberBetween(1, 1000);
        $prixParAction = $this->faker->randomFloat(2, 100, 5000);
        $montantVente = $quantite * $prixParAction;

        return [
            'key' => 'sal-'.strtolower(Str::random(8)),
            'wallet_id' => Wallet::factory(),
            'user_id' => User::factory(),
            'quantite' => $quantite,
            'prix_par_action' => $prixParAction,
            'montant_vente' => $montantVente,
            'comment' => $this->faker->optional()->sentence(),
        ];
    }
}
