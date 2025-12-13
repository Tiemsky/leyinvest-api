<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Exemples de coupons (optionnel - vous pouvez créer des coupons via l'admin)
        $coupons = [
            [
                'code' => 'BIENVENUE2025',
                'type' => 'percentage',
                'value' => 20,
                'max_discount' => null,
                'max_uses' => null,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(3),
                'is_active' => true,
                'applicable_plans' => null, // Tous les plans
            ],
            [
                'code' => 'PREMIUM50',
                'type' => 'percentage',
                'value' => 50,
                'max_discount' => 7500,
                'max_uses' => 100,
                'starts_at' => now(),
                'expires_at' => now()->addMonth(),
                'is_active' => true,
                'applicable_plans' => [3], // Uniquement Premium (ID 3)
            ],
            [
                'code' => 'FIRST5000',
                'type' => 'fixed',
                'value' => 5000,
                'max_discount' => null,
                'max_uses' => 50,
                'starts_at' => now(),
                'expires_at' => now()->addWeeks(2),
                'is_active' => true,
                'applicable_plans' => [2, 3], // Pro et Premium
            ],
        ];

        foreach ($coupons as $couponData) {
            Coupon::updateOrCreate(
                ['code' => $couponData['code']],
                $couponData
            );
        }

        $this->command->info('Coupons d\'exemple créés avec succès!');
    }
}
