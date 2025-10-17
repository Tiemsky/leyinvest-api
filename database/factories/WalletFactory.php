<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Wallet>
 */
class WalletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
       // Génère des montants cohérents
       $totalInvested = $this->faker->randomFloat(2, 1000, 100000);
       $gainLoss = $this->faker->randomFloat(2, -5000, 20000);
       $totalValue = $totalInvested + $gainLoss;

       // Calculs économiques simples
       $rendement = $totalInvested > 0
           ? round(($gainLoss / $totalInvested) * 100, 2)
           : 0;

       $rentabilite = $this->faker->randomFloat(2, 0, 15);
       $liquidite = $this->faker->randomFloat(2, 500, 20000);

       return [
        'key' => 'wal-' . strtolower(\Illuminate\Support\Str::random(8)),
           'user_id' => User::factory(),
           'total_value' => $totalValue,
           'total_gain_loss' => $gainLoss,
           'total_invested' => $totalInvested,
           'rendement' => $rendement,
           'rentabilite' => $rentabilite,
           'liquidite' => $liquidite,
       ];
    }
}
