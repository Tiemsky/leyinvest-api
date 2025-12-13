<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Feature; // Nécessaire pour les constantes et la recherche
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run()
    {
        // Utilisation des constantes du modèle Feature pour la liste des fonctionnalités
        $plans = [
            [
                'nom' => 'Gratuit',
                'slug' => 'gratuit',
                'prix' => 0,
                'billing_cycle' => 'monthly',
                'trial_days' => 0,
                'sort_order' => 1,
                'features' => [
                    Feature::KEY_MARKET_INDICATORS => ['enabled' => true],
                    Feature::KEY_NEWS => ['enabled' => true],
                    Feature::KEY_STANDARD_ARTICLES => ['enabled' => true],
                    Feature::KEY_MY_LIST => ['enabled' => true],
                    Feature::KEY_COMPANY_PRESENTATION => ['enabled' => true],
                    Feature::KEY_FINANCIAL_INDICATORS => ['enabled' => true],
                    Feature::KEY_CALCULATOR => ['enabled' => true],
                    Feature::KEY_DIVIDEND_CALENDAR => ['enabled' => true],
                ]
            ],
            [
                'nom' => 'Pro',
                'slug' => 'pro',
                'prix' => 11900,
                'billing_cycle' => 'monthly',
                'trial_days' => 7,
                'sort_order' => 2,
                'features' => [
                    Feature::KEY_MARKET_INDICATORS => ['enabled' => true],
                    Feature::KEY_NEWS => ['enabled' => true],
                    Feature::KEY_STANDARD_ARTICLES => ['enabled' => true],
                    Feature::KEY_MY_LIST => ['enabled' => true],
                    Feature::KEY_COMPANY_PRESENTATION => ['enabled' => true],
                    Feature::KEY_FINANCIAL_INDICATORS => ['enabled' => true],
                    Feature::KEY_CALCULATOR => ['enabled' => true],
                    Feature::KEY_DIVIDEND_CALENDAR => ['enabled' => true],

                    // Features Pro (Ajoutées)
                    Feature::KEY_EVALUATIONS => ['enabled' => true],
                    Feature::KEY_COMPLETE_INDICATORS => ['enabled' => true],
                    Feature::KEY_COMPANY_HISTORY => ['enabled' => true],
                    Feature::KEY_NOTIFICATIONS => ['enabled' => true],
                ]
            ],
            [
                'nom' => 'Premium',
                'slug' => 'premium',
                'prix' => 14900,
                'billing_cycle' => 'monthly',
                'trial_days' => 14,
                'sort_order' => 3,
                'features' => [
                    // Features Gratuites/Pro (Inclues)
                    Feature::KEY_MARKET_INDICATORS => ['enabled' => true],
                    Feature::KEY_NEWS => ['enabled' => true],
                    Feature::KEY_STANDARD_ARTICLES => ['enabled' => true],
                    Feature::KEY_MY_LIST => ['enabled' => true],
                    Feature::KEY_COMPANY_PRESENTATION => ['enabled' => true],
                    Feature::KEY_FINANCIAL_INDICATORS => ['enabled' => true],
                    Feature::KEY_CALCULATOR => ['enabled' => true],
                    Feature::KEY_DIVIDEND_CALENDAR => ['enabled' => true],
                    Feature::KEY_EVALUATIONS => ['enabled' => true],
                    Feature::KEY_COMPLETE_INDICATORS => ['enabled' => true],
                    Feature::KEY_COMPANY_HISTORY => ['enabled' => true],
                    Feature::KEY_NOTIFICATIONS => ['enabled' => true],

                    // Features Premium (Ajoutées)
                    Feature::KEY_PREMIUM_ARTICLES => ['enabled' => true],
                    Feature::KEY_YIELD_FORECAST => ['enabled' => true],
                ]
            ]
        ];

        // Charger toutes les Features existantes en mémoire par leur clé
        $existingFeatures = Feature::all()->keyBy('key');

        foreach ($plans as $planData) {
            $planFeatures = $planData['features'];
            unset($planData['features']);

            // 1. Définition des valeurs du Plan
            $planValues = array_merge($planData, [
                'is_visible' => true,
                // On pourrait retirer le champ 'features' obsolète du PlanSeeder optimisé
            ]);

            // 2. Préparation des attributs de recherche et des valeurs de mise à jour
            $attributes = ['slug' => $planValues['slug']];

            // Si le plan n'existe pas, on génère sa clé unique
            if (!Plan::where('slug', $planValues['slug'])->exists()) {
                $planValues['key'] = 'pla_' . time();
            }

            // 3. Création ou Mise à jour du Plan
            $plan = Plan::updateOrCreate($attributes, $planValues);

            // 4. Collecte des features à synchroniser (pour une seule requête)
            $featuresToSync = [];
            foreach ($planFeatures as $featureKey => $config) {
                // Utilisation du tableau préchargé pour une recherche rapide en mémoire
                $feature = $existingFeatures->get($featureKey);

                if ($feature) {
                    $featuresToSync[$feature->id] = [
                        'is_enabled' => $config['enabled'] ?? true,
                    ];
                } else {
                    $this->command->warn("⚠️ Feature '{$featureKey}' non trouvée. Assurez-vous qu'elle existe dans le FeatureSeeder.");
                }
            }

            // 5. Synchronisation en une seule requête pour toutes les features du plan
            // (sync() garantit que toute ancienne feature non listée sera retirée)
            $plan->features()->sync($featuresToSync);
        }

        $this->command->info('🎉 Plans créés et features attachées avec succès!');
    }
}
