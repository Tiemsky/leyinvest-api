<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantite = $this->faker->numberBetween(1, 1000);
        $prixParAction = $this->faker->randomFloat(2, 100, 50000);
        $montantAchat = $quantite * $prixParAction;

        return [
            'wallet_id' => Wallet::factory(),
            'user_id' => User::factory(),
            'quantite' => $quantite,
            'prix_par_action' => $prixParAction,
            'montant_achat' => $montantAchat,
            'comment' => $this->faker->optional()->sentence(),
        ];
    }
}
