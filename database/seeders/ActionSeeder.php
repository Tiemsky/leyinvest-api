<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Action;
use App\Models\BrvmSector;
use App\Models\ClassifiedSector;

class ActionSeeder extends Seeder
{
    /**
     * Mapping : symbole → [brvm_sector_name, classified_sector_name]
     */
    protected array $actionSectorMap = [
        'NTLC' => ['Consommation de base', 'Biens de consommation'],
        'PALC' => ['Consommation de base', 'Agro Industrie'],
        'SCRC' => ['Consommation de base', 'Agro Industrie'],
        'SICC' => ['Consommation de base', 'Agro Industrie'],
        'SLBC' => ['Consommation de base', 'Biens de consommation'],
        'SOGC' => ['Consommation de base', 'Agro Industrie'],
        'SPHC' => ['Consommation de base', 'Agro Industrie'],
        'STBC' => ['Consommation de base', 'Biens de consommation'],
        'UNLC' => ['Consommation de base', 'Biens de consommation'],

        'ABJC' => ['Consommation discrétionnaire', 'Consommation discrétionnaire '],
        'BNBC' => ['Consommation discrétionnaire', 'BTP'],
        'CFAC' => ['Consommation discrétionnaire', 'Automobile '],
        'LNBB' => ['Consommation discrétionnaire', 'Consommation discrétionnaire '],
        'NEIC' => ['Consommation discrétionnaire', 'Consommation discrétionnaire '],
        'PRSC' => ['Consommation discrétionnaire', 'Automobile '],
        'UNXC' => ['Consommation discrétionnaire', 'Industrie'],

        'SHEC' => ['Énergie', 'Pétrole et Energie'],
        'SMBC' => ['Énergie', 'BTP'],
        'TTLC' => ['Énergie', 'Pétrole et Energie'],
        'TTLS' => ['Énergie', 'Pétrole et Energie'],

        'CABC' => ['Industriels', 'Industrie'],
        'FTSC' => ['Industriels', 'Industrie'],
        'SDSC' => ['Industriels', 'Logistique'],
        'SEMC' => ['Industriels', 'Industrie'],
        'SIVC' => ['Industriels', 'Industrie'],
        'STAC' => ['Industriels', 'BTP'],

        'BICB' => ['Services financiers', 'Services Financiers'],
        'BICC' => ['Services financiers', 'Services Financiers'],
        'BOAB' => ['Services financiers', 'Services Financiers'],
        'BOABF' => ['Services financiers', 'Services Financiers'],
        'BOAC' => ['Services financiers', 'Services Financiers'],
        'BOAM' => ['Services financiers', 'Services Financiers'],
        'BOAN' => ['Services financiers', 'Services Financiers'],
        'BOAS' => ['Services financiers', 'Services Financiers'],
        'CBIBF' => ['Services financiers', 'Services Financiers'],
        'ECOC' => ['Services financiers', 'Services Financiers'],
        'ETIT' => ['Services financiers', 'Services Financiers'],
        'NSBC' => ['Services financiers', 'Services Financiers'],
        'ORGT' => ['Services financiers', 'Services Financiers'],
        'SAFC' => ['Services financiers', 'Services Financiers'],
        'SGBC' => ['Services financiers', 'Services Financiers'],
        'SIBC' => ['Services financiers', 'Services Financiers'],

        'CIEC' => ['Services publics', 'Services publics'],
        'SDCC' => ['Services publics', 'Services publics'],

        'ONTBF' => ['Télécommunications', 'Télécommunications'],
        'ORAC' => ['Télécommunications', 'Télécommunications'],
        'SNTS' => ['Télécommunications', 'Télécommunications'],
    ];

    public function run(): void
    {
        // On vide la table
        Action::truncate();

        // Récupérer tous les secteurs avec leurs noms → ID
        $brvmSectors = BrvmSector::pluck('id', 'nom')->toArray();
        $classifiedSectors = ClassifiedSector::pluck('id', 'nom')->toArray();

        // Données des actions actualisées
        $rawActions = [
            ['symbole' => 'NTLC', 'nom' => "NESTLE COTE D'IVOIRE", 'volume' => 1406, 'cours_veille' => 9580, 'cours_ouverture' => 9580, 'cours_cloture' => 9660, 'variation' => -6.58],
            ['symbole' => 'PALC', 'nom' => "PALM COTE D'IVOIRE", 'volume' => 52, 'cours_veille' => 7500, 'cours_ouverture' => 7550, 'cours_cloture' => 7600, 'variation' => 1.33],
            ['symbole' => 'SCRC', 'nom' => "SUCRIVOIRE COTE D'IVOIRE", 'volume' => 8323, 'cours_veille' => 1015, 'cours_ouverture' => 995, 'cours_cloture' => 995, 'variation' => -4.33],
            ['symbole' => 'SICC', 'nom' => "SICOR COTE D'IVOIRE", 'volume' => 0, 'cours_veille' => 3500, 'cours_ouverture' => 0, 'cours_cloture' => 3500, 'variation' => 0.00],
            ['symbole' => 'SLBC', 'nom' => "SOLIBRA COTE D'IVOIRE", 'volume' => 1, 'cours_veille' => 24500, 'cours_ouverture' => 24500, 'cours_cloture' => 24500, 'variation' => 2.08],
            ['symbole' => 'SOGC', 'nom' => "SOGB COTE D'IVOIRE", 'volume' => 743, 'cours_veille' => 7500, 'cours_ouverture' => 7505, 'cours_cloture' => 7540, 'variation' => 0.53],
            ['symbole' => 'SPHC', 'nom' => "SAPH COTE D'IVOIRE", 'volume' => 755, 'cours_veille' => 7350, 'cours_ouverture' => 7350, 'cours_cloture' => 7350, 'variation' => 0.00],
            ['symbole' => 'STBC', 'nom' => "SITAB COTE D'IVOIRE", 'volume' => 2168, 'cours_veille' => 19990, 'cours_ouverture' => 19990, 'cours_cloture' => 19985, 'variation' => -0.03],
            ['symbole' => 'UNLC', 'nom' => "UNILEVER COTE D'IVOIRE", 'volume' => 4, 'cours_veille' => 23005, 'cours_ouverture' => 23005, 'cours_cloture' => 23005, 'variation' => 0.00],

            ['symbole' => 'ABJC', 'nom' => "SERVAIR ABIDJAN COTE D'IVOIRE", 'volume' => 613, 'cours_veille' => 2365, 'cours_ouverture' => 2370, 'cours_cloture' => 2365, 'variation' => 4.65],
            ['symbole' => 'BNBC', 'nom' => "BERNABE COTE D'IVOIRE", 'volume' => 825, 'cours_veille' => 1325, 'cours_ouverture' => 1325, 'cours_cloture' => 1325, 'variation' => -7.34],
            ['symbole' => 'CFAC', 'nom' => "CFAO MOTORS COTE D'IVOIRE", 'volume' => 3108, 'cours_veille' => 1600, 'cours_ouverture' => 1600, 'cours_cloture' => 1575, 'variation' => -7.35],
            ['symbole' => 'LNBB', 'nom' => "LOTERIE NATIONALE DU BENIN", 'volume' => 47, 'cours_veille' => 3875, 'cours_ouverture' => 3875, 'cours_cloture' => 3875, 'variation' => 4.31],
            ['symbole' => 'NEIC', 'nom' => "NEI-CEDA COTE D'IVOIRE", 'volume' => 7364, 'cours_veille' => 1020, 'cours_ouverture' => 1020, 'cours_cloture' => 1015, 'variation' => -7.31],
            ['symbole' => 'PRSC', 'nom' => "TRACTAFRIC MOTORS COTE D'IVOIRE", 'volume' => 74, 'cours_veille' => 3455, 'cours_ouverture' => 3455, 'cours_cloture' => 3670, 'variation' => -0.27],
            ['symbole' => 'UNXC', 'nom' => "UNIWAX COTE D'IVOIRE", 'volume' => 3109, 'cours_veille' => 1460, 'cours_ouverture' => 1450, 'cours_cloture' => 1460, 'variation' => 0.00],

            ['symbole' => 'SHEC', 'nom' => "VIVO ENERGY COTE D'IVOIRE", 'volume' => 2512, 'cours_veille' => 1105, 'cours_ouverture' => 1150, 'cours_cloture' => 1100, 'variation' => 0.92],
            ['symbole' => 'SMBC', 'nom' => "SMB COTE D'IVOIRE", 'volume' => 56, 'cours_veille' => 9500, 'cours_ouverture' => 9650, 'cours_cloture' => 9500, 'variation' => -3.06],
            ['symbole' => 'TTLC', 'nom' => "TOTALENERGIES MARKETING COTE D'IVOIRE", 'volume' => 700, 'cours_veille' => 2335, 'cours_ouverture' => 2320, 'cours_cloture' => 2320, 'variation' => -0.64],
            ['symbole' => 'TTLS', 'nom' => "TOTALENERGIES MARKETING SENEGAL", 'volume' => 33, 'cours_veille' => 2480, 'cours_ouverture' => 2480, 'cours_cloture' => 2480, 'variation' => 0.00],

            ['symbole' => 'CABC', 'nom' => "SICABLE COTE D'IVOIRE", 'volume' => 113, 'cours_veille' => 1930, 'cours_ouverture' => 1860, 'cours_cloture' => 1930, 'variation' => 3.76],
            ['symbole' => 'FTSC', 'nom' => "FILTISAC COTE D'IVOIRE", 'volume' => 88, 'cours_veille' => 2200, 'cours_ouverture' => 2200, 'cours_cloture' => 2200, 'variation' => 7.32],
            ['symbole' => 'SDSC', 'nom' => "AFRICA GLOBAL LOGISTICS COTE D'IVOIRE", 'volume' => 12792, 'cours_veille' => 1730, 'cours_ouverture' => 1650, 'cours_cloture' => 1700, 'variation' => 2.41],
            ['symbole' => 'SEMC', 'nom' => "EVIOSYS PACKAGING SIEM COTE D'IVOIRE", 'volume' => 0, 'cours_veille' => 700, 'cours_ouverture' => 0, 'cours_cloture' => 700, 'variation' => 0.00],
            ['symbole' => 'SIVC', 'nom' => "AIR LIQUIDE COTE D'IVOIRE", 'volume' => 1080, 'cours_veille' => 695, 'cours_ouverture' => 695, 'cours_cloture' => 700, 'variation' => 0.72],
            ['symbole' => 'STAC', 'nom' => "SETAO COTE D'IVOIRE", 'volume' => 1907, 'cours_veille' => 975, 'cours_ouverture' => 925, 'cours_cloture' => 925, 'variation' => 0.00],

            ['symbole' => 'BICB', 'nom' => "BANQUE INTERNATIONALE POUR L'INDUSTRIE ET LE COMMERCE DU BENIN", 'volume' => 952, 'cours_veille' => 5245, 'cours_ouverture' => 5245, 'cours_cloture' => 5245, 'variation' => 0.00],
            ['symbole' => 'BICC', 'nom' => "BICI COTE D'IVOIRE", 'volume' => 597, 'cours_veille' => 19490, 'cours_ouverture' => 19500, 'cours_cloture' => 19600, 'variation' => 0.56],
            ['symbole' => 'BOAB', 'nom' => "BANK OF AFRICA BENIN", 'volume' => 1447, 'cours_veille' => 4945, 'cours_ouverture' => 4945, 'cours_cloture' => 5000, 'variation' => 1.11],
            ['symbole' => 'BOABF', 'nom' => "BANK OF AFRICA BURKINA FASO", 'volume' => 2883, 'cours_veille' => 3450, 'cours_ouverture' => 3460, 'cours_cloture' => 3450, 'variation' => 0.00],
            ['symbole' => 'BOAC', 'nom' => "BANK OF AFRICA COTE D'IVOIRE", 'volume' => 3627, 'cours_veille' => 7005, 'cours_ouverture' => 7100, 'cours_cloture' => 7095, 'variation' => -1.46],
            ['symbole' => 'BOAM', 'nom' => "BANK OF AFRICA MALI", 'volume' => 1678, 'cours_veille' => 3800, 'cours_ouverture' => 3750, 'cours_cloture' => 3800, 'variation' => 4.11],
            ['symbole' => 'BOAN', 'nom' => "BANK OF AFRICA NIGER", 'volume' => 2277, 'cours_veille' => 2500, 'cours_ouverture' => 2500, 'cours_cloture' => 2550, 'variation' => -0.39],
            ['symbole' => 'BOAS', 'nom' => "BANK OF AFRICA SENEGAL", 'volume' => 735, 'cours_veille' => 5250, 'cours_ouverture' => 5290, 'cours_cloture' => 5290, 'variation' => 0.76],
            ['symbole' => 'CBIBF', 'nom' => "CORIS BANK INTERNATIONAL BURKINA FASO", 'volume' => 2911, 'cours_veille' => 10000, 'cours_ouverture' => 10000, 'cours_cloture' => 9995, 'variation' => -0.05],
            ['symbole' => 'ECOC', 'nom' => "ECOBANK COTE D'IVOIRE", 'volume' => 33, 'cours_veille' => 15700, 'cours_ouverture' => 15700, 'cours_cloture' => 15700, 'variation' => 0.00],
            ['symbole' => 'ETIT', 'nom' => "Ecobank Transnational Incorporated TOGO", 'volume' => 1338295, 'cours_veille' => 23, 'cours_ouverture' => 24, 'cours_cloture' => 23, 'variation' => -4.17],
            ['symbole' => 'NSBC', 'nom' => "NSIA BANQUE COTE D'IVOIRE", 'volume' => 261, 'cours_veille' => 11995, 'cours_ouverture' => 11995, 'cours_cloture' => 11995, 'variation' => 0.00],
            ['symbole' => 'ORGT', 'nom' => "ORAGROUP TOGO", 'volume' => 403, 'cours_veille' => 2495, 'cours_ouverture' => 2500, 'cours_cloture' => 2500, 'variation' => 0.20],
            ['symbole' => 'SAFC', 'nom' => "SAFCA COTE D'IVOIRE", 'volume' => 9082, 'cours_veille' => 2270, 'cours_ouverture' => 2270, 'cours_cloture' => 2270, 'variation' => -7.35],
            ['symbole' => 'SGBC', 'nom' => "SOCIETE GENERALE COTE D'IVOIRE", 'volume' => 142, 'cours_veille' => 26955, 'cours_ouverture' => 26955, 'cours_cloture' => 27000, 'variation' => 0.17],
            ['symbole' => 'SIBC', 'nom' => "SOCIETE IVOIRIENNE DE BANQUE COTE D'IVOIRE", 'volume' => 392, 'cours_veille' => 5600, 'cours_ouverture' => 5630, 'cours_cloture' => 5630, 'variation' => 0.54],

            ['symbole' => 'CIEC', 'nom' => "CIE COTE D'IVOIRE", 'volume' => 1672, 'cours_veille' => 2620, 'cours_ouverture' => 2620, 'cours_cloture' => 2600, 'variation' => -0.76],
            ['symbole' => 'SDCC', 'nom' => "SODE COTE D'IVOIRE", 'volume' => 215, 'cours_veille' => 5945, 'cours_ouverture' => 5945, 'cours_cloture' => 5945, 'variation' => 0.00],

            ['symbole' => 'ONTBF', 'nom' => "ONATEL BURKINA FASO", 'volume' => 420, 'cours_veille' => 2500, 'cours_ouverture' => 2490, 'cours_cloture' => 2490, 'variation' => -0.40],
            ['symbole' => 'ORAC', 'nom' => "ORANGE COTE D'IVOIRE", 'volume' => 53, 'cours_veille' => 14605, 'cours_ouverture' => 14495, 'cours_cloture' => 14550, 'variation' => -0.38],
            ['symbole' => 'SNTS', 'nom' => "SONATEL SENEGAL", 'volume' => 182, 'cours_veille' => 25990, 'cours_ouverture' => 25995, 'cours_cloture' => 26000, 'variation' => 0.04],
        ];

        foreach ($rawActions as $data) {
            $symbole = $data['symbole'];

            if (!isset($this->actionSectorMap[$symbole])) {
                $this->command->warn("Secteur non défini pour l'action {$symbole}");
                continue;
            }

            [$brvmName, $classifiedName] = $this->actionSectorMap[$symbole];

            $brvmId = $brvmSectors[$brvmName] ?? null;
            $classifiedId = $classifiedSectors[$classifiedName] ?? null;

            if (!$brvmId || !$classifiedId) {
                $this->command->error("Secteur introuvable pour {$symbole}: BRVM={$brvmName}, Classified={$classifiedName}");
                continue;
            }

            Action::create(array_merge($data, [
                'key' => 'act_' . strtolower(Str::random(8)),
                'brvm_sector_id' => $brvmId,
                'classified_sector_id' => $classifiedId,
            ]));
        }
    }
}
