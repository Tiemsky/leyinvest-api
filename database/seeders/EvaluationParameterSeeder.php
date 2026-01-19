<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EvaluationParameterSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $parameters = [
            [
                'nom' => 'coeff_k',
                'value' => 0.3000,
                'description' => 'Pondération de l’évolution trimestrielle dans le RN prévisionnel',
            ],
            [
                'nom' => 'coeff_z',
                'value' => 0.7000,
                'description' => 'Pondération de la moyenne historique du résultat net',
            ],
            [
                'nom' => 'irvm',
                'value' => 0.1500,
                'description' => 'Impôt sur le Revenu des Valeurs Mobilières (IRVM)',
            ],
            [
                'nom' => 'weight_x1',
                'value' => 3.0000,
                'description' => 'Coefficient de pondération du résultat net N-1',
            ],
            [
                'nom' => 'weight_x2',
                'value' => 2.0000,
                'description' => 'Coefficient de pondération du résultat net N-2',
            ],
            [
                'nom' => 'weight_x3',
                'value' => 1.0000,
                'description' => 'Coefficient de pondération du résultat net N-3',
            ],
            [
                'nom' => 'weight_x4',
                'value' => 1.0000,
                'description' => 'Coefficient de pondération du résultat net N-4',
            ],
        ];

        foreach ($parameters as $parameter) {
            DB::table('evaluation_parameters')->updateOrInsert(
                ['nom' => $parameter['nom']],
                [
                    'key' => 'eva_'.time().'_'.Str::slug($parameter['nom']),
                    'value' => $parameter['value'],
                    'description' => $parameter['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
