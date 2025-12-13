<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            // Fonctionnalités gratuites
            ['key' => Feature::KEY_MARKET_INDICATORS, 'name' => 'Indicateurs Marchés'],
            ['key' => Feature::KEY_NEWS, 'name' => 'Actualités'],
            ['key' => Feature::KEY_STANDARD_ARTICLES, 'name' => 'Articles Standard'],
            ['key' => Feature::KEY_MY_LIST, 'name' => 'Ma Liste'],
            ['key' => Feature::KEY_COMPANY_PRESENTATION, 'name' => 'Présentation Entreprise'],
            ['key' => Feature::KEY_FINANCIAL_INDICATORS, 'name' => 'Indicateurs Financiers'],
            ['key' => Feature::KEY_CALCULATOR, 'name' => 'Calculateur'],
            ['key' => Feature::KEY_DIVIDEND_CALENDAR, 'name' => 'Calendrier Dividendes'],

            // Fonctionnalités Pro
            ['key' => Feature::KEY_EVALUATIONS, 'name' => 'Évaluations'],
            ['key' => Feature::KEY_COMPLETE_INDICATORS, 'name' => 'Indicateurs Complets'],
            ['key' => Feature::KEY_COMPANY_HISTORY, 'name' => 'Historique Entreprise'],
            ['key' => Feature::KEY_NOTIFICATIONS, 'name' => 'Notifications'],

            // Fonctionnalités Premium
            ['key' => Feature::KEY_PREMIUM_ARTICLES, 'name' => 'Articles Premium'],
            ['key' => Feature::KEY_YIELD_FORECAST, 'name' => 'Prévision Rendement'],
        ];

        foreach ($features as $featureData) {
            Feature::updateOrCreate(
                ['key' => $featureData['key']],
                array_merge([
                    'slug' => Str::slug($featureData['key']),
                    'is_active' => true,
                ], $featureData)
            );
        }

        $this->command->info('✅ Features créées avec succès!');
    }
}
