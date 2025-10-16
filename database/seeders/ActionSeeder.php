<?php

namespace Database\Seeders;

use App\Models\Action;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
            ['symbole' => 'NSBC', 'nom' => "NSIA BANQUE COTE D'IVOIRE", 'volume' => 815, 'cours_veille' => 11250, 'cours_ouverture' => 11250, 'cours_cloture' => 11200, 'variation' => -0.44],
            ['symbole' => 'NTLC', 'nom' => "NESTLE COTE D'IVOIRE", 'volume' => 1080, 'cours_veille' => 13200, 'cours_ouverture' => 13280, 'cours_cloture' => 13180, 'variation' => -0.15],
            ['symbole' => 'ONTBF', 'nom' => "ONATEL BURKINA FASO", 'volume' => 3255, 'cours_veille' => 2370, 'cours_ouverture' => 2525, 'cours_cloture' => 2370, 'variation' => -6.14],
            ['symbole' => 'ORAC', 'nom' => "ORANGE COTE D'IVOIRE", 'volume' => 1352, 'cours_veille' => 14500, 'cours_ouverture' => 14500, 'cours_cloture' => 14400, 'variation' => -0.69],
            ['symbole' => 'ORGT', 'nom' => "ORAGROUP TOGO", 'volume' => 7888, 'cours_veille' => 2550, 'cours_ouverture' => 2610, 'cours_cloture' => 2530, 'variation' => -2.88],
            ['symbole' => 'PALC', 'nom' => "PALM COTE D'IVOIRE", 'volume' => 4838, 'cours_veille' => 9690, 'cours_ouverture' => 9715, 'cours_cloture' => 9700, 'variation' => 0.10],
            ['symbole' => 'PRSC', 'nom' => "TRACTAFRIC MOTORS COTE D'IVOIRE", 'volume' => 5575, 'cours_veille' => 3750, 'cours_ouverture' => 3605, 'cours_cloture' => 3710, 'variation' => 3.06],
            ['symbole' => 'SAFC', 'nom' => "SAFCA COTE D'IVOIRE", 'volume' => 16326, 'cours_veille' => 2480, 'cours_ouverture' => 2800, 'cours_cloture' => 2470, 'variation' => -7.32],
            ['symbole' => 'SCRC', 'nom' => "SUCRIVOIRE COTE D'IVOIRE", 'volume' => 3858, 'cours_veille' => 1260, 'cours_ouverture' => 1300, 'cours_cloture' => 1260, 'variation' => -2.70],
            ['symbole' => 'SDCC', 'nom' => "SODE COTE D'IVOIRE", 'volume' => 249, 'cours_veille' => 6085, 'cours_ouverture' => 6015, 'cours_cloture' => 6050, 'variation' => -0.58],
            ['symbole' => 'SDSC', 'nom' => "AFRICA GLOBAL LOGISTICS COTE D'IVOIRE", 'volume' => 64617, 'cours_veille' => 1450, 'cours_ouverture' => 1490, 'cours_cloture' => 1450, 'variation' => -2.03],
            ['symbole' => 'SEMC', 'nom' => "EVIOSYS PACKAGING SIEM COTE D'IVOIRE", 'volume' => 0, 'cours_veille' => 700, 'cours_ouverture' => 0, 'cours_cloture' => 700, 'variation' => 0.00],
            ['symbole' => 'SGBC', 'nom' => "SOCIETE GENERALE COTE D'IVOIRE", 'volume' => 720, 'cours_veille' => 27500, 'cours_ouverture' => 27500, 'cours_cloture' => 27000, 'variation' => -1.82],
            ['symbole' => 'SHEC', 'nom' => "VIVO ENERGY COTE D'IVOIRE", 'volume' => 2800, 'cours_veille' => 1375, 'cours_ouverture' => 1395, 'cours_cloture' => 1380, 'variation' => 0.36],
            ['symbole' => 'SIBC', 'nom' => "SOCIETE IVOIRIENNE DE BANQUE COTE D'IVOIRE", 'volume' => 11808, 'cours_veille' => 5605, 'cours_ouverture' => 5700, 'cours_cloture' => 5605, 'variation' => -1.67],
            ['symbole' => 'SICC', 'nom' => "SICOR COTE D'IVOIRE", 'volume' => 0, 'cours_veille' => 3350, 'cours_ouverture' => 0, 'cours_cloture' => 3350, 'variation' => 0.00],
            ['symbole' => 'SIVC', 'nom' => "AIR LIQUIDE COTE D'IVOIRE", 'volume' => 10469, 'cours_veille' => 690, 'cours_ouverture' => 720, 'cours_cloture' => 690, 'variation' => -3.50],
            ['symbole' => 'SLBC', 'nom' => "SOLIBRA COTE D'IVOIRE", 'volume' => 161, 'cours_veille' => 19185, 'cours_ouverture' => 19355, 'cours_cloture' => 19185, 'variation' => 0.00],
            ['symbole' => 'SMBC', 'nom' => "SMB COTE D'IVOIRE", 'volume' => 2880, 'cours_veille' => 9795, 'cours_ouverture' => 9900, 'cours_cloture' => 9800, 'variation' => -2.00],
            ['symbole' => 'SNTS', 'nom' => "SONATEL SENEGAL", 'volume' => 8209, 'cours_veille' => 26500, 'cours_ouverture' => 26300, 'cours_cloture' => 26000, 'variation' => -1.89],
            ['symbole' => 'SOGC', 'nom' => "SOGB COTE D'IVOIRE", 'volume' => 430, 'cours_veille' => 8890, 'cours_ouverture' => 8720, 'cours_cloture' => 8890, 'variation' => 2.07],
            ['symbole' => 'SPHC', 'nom' => "SAPH COTE D'IVOIRE", 'volume' => 10704, 'cours_veille' => 8195, 'cours_ouverture' => 7995, 'cours_cloture' => 8100, 'variation' => 1.44],
            ['symbole' => 'STAC', 'nom' => "SETAO COTE D'IVOIRE", 'volume' => 2754, 'cours_veille' => 1200, 'cours_ouverture' => 1190, 'cours_cloture' => 1200, 'variation' => 0.84],
            ['symbole' => 'STBC', 'nom' => "SITAB COTE D'IVOIRE", 'volume' => 1268, 'cours_veille' => 19550, 'cours_ouverture' => 20000, 'cours_cloture' => 19550, 'variation' => -2.25],
            ['symbole' => 'TTLC', 'nom' => "TOTALENERGIES MARKETING COTE D'IVOIRE", 'volume' => 26355, 'cours_veille' => 2430, 'cours_ouverture' => 2445, 'cours_cloture' => 2450, 'variation' => 0.20],
            ['symbole' => 'TTLS', 'nom' => "TOTALENERGIES MARKETING SENEGAL", 'volume' => 2349, 'cours_veille' => 2470, 'cours_ouverture' => 2470, 'cours_cloture' => 2480, 'variation' => 0.40],
            ['symbole' => 'UNLC', 'nom' => "UNILEVER COTE D'IVOIRE", 'volume' => 0, 'cours_veille' => 18510, 'cours_ouverture' => 0, 'cours_cloture' => 18510, 'variation' => 0.00],
            ['symbole' => 'UNXC', 'nom' => "UNIWAX COTE D'IVOIRE", 'volume' => 18303, 'cours_veille' => 1790, 'cours_ouverture' => 1785, 'cours_cloture' => 1785, 'variation' => 0.00],
        ];

        Action::truncate();
        foreach($actions as $action){
            Action::create($action);
        }

    }
}
