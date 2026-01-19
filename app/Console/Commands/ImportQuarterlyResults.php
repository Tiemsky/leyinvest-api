<?php

namespace App\Console\Commands;

use App\Models\Action;
use App\Models\QuarterlyResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportQuarterlyResults extends Command
{
    protected $signature = 'import:import-quarterly-results';

    protected $description = 'Importe l’intégralité des résultats trimestriels 2025 extraits du PDF';

    public function handle()
    {
        $this->info('Initialisation de l\'importation des données 2025...');

        $datasets = [
            'BOAC' => [
                ['trimestre' => 1, 'ca' => 18392, 'ev_ca' => null, 'rn' => 10703, 'ev_rn' => 14.60],
                ['trimestre' => 2, 'ca' => 36214, 'ev_ca' => null, 'rn' => 18396, 'ev_rn' => 4.30],
            ],
            'BOAM' => [
                ['trimestre' => 1, 'ca' => 8394, 'ev_ca' => null, 'rn' => 3021, 'ev_rn' => 91.20],
                ['trimestre' => 2, 'ca' => 18713, 'ev_ca' => null, 'rn' => 6206000, 'ev_rn' => 28.80],
                ['trimestre' => 3, 'ca' => 28517, 'ev_ca' => 26.20, 'rn' => 9161, 'ev_rn' => null],
            ],
            'BOAB' => [
                ['trimestre' => 1, 'ca' => 11081, 'ev_ca' => null, 'rn' => 4446, 'ev_rn' => -8.00],
                ['trimestre' => 2, 'ca' => 24556, 'ev_ca' => null, 'rn' => 11012, 'ev_rn' => 0.80],
            ],
            'BOAS' => [
                ['trimestre' => 1, 'ca' => 11776, 'ev_ca' => null, 'rn' => 5230, 'ev_rn' => 9.25],
                ['trimestre' => 2, 'ca' => 25366, 'ev_ca' => null, 'rn' => 11617, 'ev_rn' => 12.70],
            ],
            'BOAN' => [
                ['trimestre' => 1, 'ca' => 4718.2, 'ev_ca' => null, 'rn' => 1138, 'ev_rn' => -50.20],
                ['trimestre' => 2, 'ca' => 10475.2, 'ev_ca' => null, 'rn' => 2386.4, 'ev_rn' => -25.20],
                ['trimestre' => 3, 'ca' => 15626, 'ev_ca' => null, 'rn' => 2836, 'ev_rn' => -33.10],
            ],
            'BOABF' => [
                ['trimestre' => 1, 'ca' => 14223, 'ev_ca' => null, 'rn' => 5195, 'ev_rn' => -16.60],
                ['trimestre' => 2, 'ca' => 29062, 'ev_ca' => null, 'rn' => 8225, 'ev_rn' => -37.70],
                ['trimestre' => 3, 'ca' => 43276, 'ev_ca' => null, 'rn' => 13445, 'ev_rn' => -27.70],
            ],
            'SIBC' => [
                ['trimestre' => 1, 'ca' => 265000, 'ev_ca' => null, 'rn' => 13600, 'ev_rn' => 10.00],
                ['trimestre' => 2, 'ca' => 54300, 'ev_ca' => null, 'rn' => 29400, 'ev_rn' => 15.00],
                ['trimestre' => 3, 'ca' => 81400, 'ev_ca' => null, 'rn' => 43000, 'ev_rn' => 11.00],
            ],
            'CBIBF' => [
                ['trimestre' => 1, 'ca' => 28671, 'ev_ca' => null, 'rn' => 17147090, 'ev_rn' => 12.6],
                ['trimestre' => 2, 'ca' => 65135, 'ev_ca' => null, 'rn' => 33754, 'ev_rn' => 0.70],
                ['trimestre' => 3, 'ca' => 101324, 'ev_ca' => null, 'rn' => 52917, 'ev_rn' => 6.20],
            ],
            'ECOC' => [
                ['trimestre' => 1, 'ca' => 29738, 'ev_ca' => null, 'rn' => 13258, 'ev_rn' => 6.90],
                ['trimestre' => 2, 'ca' => 62842, 'ev_ca' => null, 'rn' => 28904, 'ev_rn' => 13.80],
                ['trimestre' => 3, 'ca' => 96447, 'ev_ca' => null, 'rn' => 44437, 'ev_rn' => 15.40],
            ],
            'NSBC' => [
                ['trimestre' => 1, 'ca' => 22402, 'ev_ca' => null, 'rn' => 7138, 'ev_rn' => 0.50],
                ['trimestre' => 2, 'ca' => 50500, 'ev_ca' => null, 'rn' => 16314, 'ev_rn' => 12.30],
                ['trimestre' => 3, 'ca' => 77366, 'ev_ca' => null, 'rn' => 25037, 'ev_rn' => 6.00],
            ],
            'BICC' => [
                ['trimestre' => 1, 'ca' => 18130, 'ev_ca' => null, 'rn' => 6511, 'ev_rn' => 23.00],
                ['trimestre' => 2, 'ca' => 37146, 'ev_ca' => null, 'rn' => 16253, 'ev_rn' => 42.50],
                ['trimestre' => 3, 'ca' => 58119, 'ev_ca' => null, 'rn' => 27402, 'ev_rn' => 50.80],
            ],
            'ETIT' => [
                ['trimestre' => 1, 'ca' => 311941, 'ev_ca' => null, 'rn' => 74009, 'ev_rn' => 17.00],
                ['trimestre' => 2, 'ca' => 671278, 'ev_ca' => null, 'rn' => 167593, 'ev_rn' => 23.00],
                ['trimestre' => 3, 'ca' => 1029249, 'ev_ca' => null, 'rn' => 267019, 'ev_rn' => 34.00],
            ],
            'ORAC' => [
                ['trimestre' => 1, 'ca' => 51125, 'ev_ca' => null, 'rn' => 4797, 'ev_rn' => 259],
                ['trimestre' => 2, 'ca' => 99749, 'ev_ca' => null, 'rn' => 18282, 'ev_rn' => 232],
                ['trimestre' => 3, 'ca' => 152559, 'ev_ca' => null, 'rn' => 19604, 'ev_rn' => 248],
            ],
        ];

        DB::transaction(function () use ($datasets) {
            foreach ($datasets as $symbole => $results) {
                // Utilisation du scopeBySymbole défini dans le modèle Action [cite: 39]
                $action = Action::bySymbole($symbole)->first();

                if (! $action) {
                    $this->error("Action {$symbole} absente de la base de données.");

                    continue;
                }

                foreach ($results as $res) {
                    QuarterlyResult::updateOrCreate(
                        [
                            'action_id' => $action->id, // [cite: 50, 76]
                            'year' => 2025,       // [cite: 51, 78]
                            'trimestre' => $res['trimestre'], // [cite: 52, 79]
                        ],
                        [
                            'chiffre_affaires' => $res['ca'],    // [cite: 53, 82]
                            'evolution_ca' => $res['ev_ca'], // [cite: 54, 84]
                            'resultat_net' => $res['rn'],    // [cite: 55, 87]
                            'evolution_rn' => $res['ev_rn'], // [cite: 56, 89]
                        ]
                    );
                }
                $this->info("✓ Symbol {$symbole} mis à jour.");
            }
        });

        $this->info('Importation complétée.');
    }
}
