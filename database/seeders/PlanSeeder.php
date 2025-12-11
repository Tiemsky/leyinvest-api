<?php

// database/seeders/PlanSeeder.php
namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    public function run()
    {
        $plans = [
            [
                'nom' => 'Gratuit',
                'slug' => 'gratuit',
                'prix' => 0,
                'billing_cycle' => 'monthly',
                'features' => [
                    'indicateurs_marches' => true,
                    'actualites' => true,
                    'articles_standard' => true,
                    'ma_liste' => true,
                    'presentation_entreprise' => true,
                    'indicateurs_financiers' => true,
                    'calculateur' => true,
                    'calendrier_dividendes' => true,
                    // Désactivées
                    'evaluations' => false,
                    'articles_premium' => false,
                    'indicateurs_complets' => false,
                    'historique_entreprise' => false,
                    'notifications' => false,
                    'prevision_rendement' => false,
                ]
            ],
            [
                'nom' => 'Pro',
                'slug' => 'pro',
                'prix' => 11900,
                'billing_cycle' => 'monthly',
                'features' => [
                    'indicateurs_marches' => true,
                    'actualites' => true,
                    'evaluations' => true,
                    'articles_standard' => true,
                    'ma_liste' => true,
                    'presentation_entreprise' => true,
                    'indicateurs_financiers' => true,
                    'indicateurs_complets' => true,
                    'historique_entreprise' => true,
                    'notifications' => true,
                    'calculateur' => true,
                    'calendrier_dividendes' => true,
                    // Désactivées
                    'articles_premium' => false,
                    'prevision_rendement' => false,
                ]
            ],
            [
                'nom' => 'Premium',
                'slug' => 'premium',
                'prix' => 14900,
                'billing_cycle' => 'monthly',
                'features' => [
                    'indicateurs_marches' => true,
                    'actualites' => true,
                    'evaluations' => true,
                    'articles_standard' => true,
                    'articles_premium' => true,
                    'ma_liste' => true,
                    'presentation_entreprise' => true,
                    'indicateurs_financiers' => true,
                    'indicateurs_complets' => true,
                    'historique_entreprise' => true,
                    'notifications' => true,
                    'calculateur' => true,
                    'calendrier_dividendes' => true,
                    'prevision_rendement' => true,
                ]
            ]
        ];

        DB::table('plans')->delete();

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                 array_merge(['key' => 'pla_' . strtolower(Str::random(8))], $planData)
            );
        }
    }
}
