<?php

namespace Database\Seeders;

use App\Models\Action;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ActionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $actions = [
            ['symbole' => 'ABJC', 'nom' => "SERVAIR ABIDJAN COTE D'IVOIRE", 'volume' => 5759, 'cours_veille' => 2265, 'cours_ouverture' => 2265, 'cours_cloture' => 2265, 'variation' => 7.35],
            ['symbole' => 'BICB', 'nom' => "BANQUE INTERNATIONALE POUR L’INDUSTRIE ET LE COMMERCE DU BENIN", 'volume' => 356, 'cours_veille' => 5240, 'cours_ouverture' => 5245, 'cours_cloture' => 5150, 'variation' => -1.90],
            ['symbole' => 'BICC', 'nom' => "BICI COTE D'IVOIRE", 'volume' => 724, 'cours_veille' => 18490, 'cours_ouverture' => 18490, 'cours_cloture' => 18600, 'variation' => 0.59],
            ['symbole' => 'BNBC', 'nom' => "BERNABE COTE D'IVOIRE", 'volume' => 4233, 'cours_veille' => 2020, 'cours_ouverture' => 2010, 'cours_cloture' => 2020, 'variation' => 1.00],
            ['symbole' => 'BOAB', 'nom' => "BANK OF AFRICA BENIN", 'volume' => 6150, 'cours_veille' => 5050, 'cours_ouverture' => 5100, 'cours_cloture' => 5095, 'variation' => 0.89],
            ['symbole' => 'BOABF', 'nom' => "BANK OF AFRICA BURKINA FASO", 'volume' => 2260, 'cours_veille' => 3805, 'cours_ouverture' => 3805, 'cours_cloture' => 3810, 'variation' => 0.13],
            ['symbole' => 'BOAC', 'nom' => "BANK OF AFRICA COTE D'IVOIRE", 'volume' => 5419, 'cours_veille' => 7300, 'cours_ouverture' => 7300, 'cours_cloture' => 7270, 'variation' => -0.41],
            ['symbole' => 'BOAM', 'nom' => "BANK OF AFRICA MALI", 'volume' => 7820, 'cours_veille' => 4250, 'cours_ouverture' => 4295, 'cours_cloture' => 4235, 'variation' => -0.35],
            ['symbole' => 'BOAN', 'nom' => "BANK OF AFRICA NIGER", 'volume' => 4177, 'cours_veille' => 2600, 'cours_ouverture' => 2595, 'cours_cloture' => 2600, 'variation' => 0.00],
            ['symbole' => 'BOAS', 'nom' => "BANK OF AFRICA SENEGAL", 'volume' => 5091, 'cours_veille' => 5600, 'cours_ouverture' => 5875, 'cours_cloture' => 5650, 'variation' => -3.09],
            ['symbole' => 'CABC', 'nom' => "SICABLE COTE D'IVOIRE", 'volume' => 1025, 'cours_veille' => 2170, 'cours_ouverture' => 2100, 'cours_cloture' => 2170, 'variation' => -1.36],
            ['symbole' => 'CBIBF', 'nom' => "CORIS BANK INTERNATIONAL BURKINA FASO", 'volume' => 296, 'cours_veille' => 9975, 'cours_ouverture' => 9980, 'cours_cloture' => 9980, 'variation' => 0.05],
            ['symbole' => 'CFAC', 'nom' => "CFAO MOTORS COTE D'IVOIRE", 'volume' => 10475, 'cours_veille' => 1565, 'cours_ouverture' => 1565, 'cours_cloture' => 1565, 'variation' => -7.40],
            ['symbole' => 'CIEC', 'nom' => "CIE COTE D'IVOIRE", 'volume' => 5010, 'cours_veille' => 2365, 'cours_ouverture' => 2495, 'cours_cloture' => 2365, 'variation' => -3.47],
            ['symbole' => 'ECOC', 'nom' => "ECOBANK COTE D'IVOIRE", 'volume' => 2487, 'cours_veille' => 13750, 'cours_ouverture' => 14300, 'cours_cloture' => 13750, 'variation' => -3.85],
            ['symbole' => 'ETIT', 'nom' => "Ecobank Transnational Incorporated TOGO", 'volume' => 10001512, 'cours_veille' => 20, 'cours_ouverture' => 20, 'cours_cloture' => 20, 'variation' => 0.00],
            ['symbole' => 'FTSC', 'nom' => "FILTISAC COTE D'IVOIRE", 'volume' => 3336, 'cours_veille' => 2940, 'cours_ouverture' => 2995, 'cours_cloture' => 2935, 'variation' => -2.00],
            ['symbole' => 'LNBB', 'nom' => "LOTERIE NATIONALE DU BENIN", 'volume' => 503, 'cours_veille' => 4000, 'cours_ouverture' => 4000, 'cours_cloture' => 4015, 'variation' => 0.38],
            ['symbole' => 'NEIC', 'nom' => "NEI-CEDA COTE D'IVOIRE", 'volume' => 2584, 'cours_veille' => 705, 'cours_ouverture' => 700, 'cours_cloture' => 700, 'variation' => 1.45],
        ];

        Action::truncate();

        foreach ($actions as $action) {
            Action::create(array_merge($action, [
                'key' => 'act-' . strtolower(Str::random(8)),
            ]));
        }
    }
}
