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
        'SMBC' => ['Énergie', 'BTP'], // Note : SMBC est aussi dans BTP → mais BrvmSector = Énergie
        'TTLC' => ['Énergie', 'Pétrole et Energie'],
        'TTLS' => ['Énergie', 'Pétrole et Energie'],

        'CABC' => ['Industriels', 'Industrie'],
        'FTSC' => ['Industriels', 'Industrie'],
        'SDSC' => ['Industriels', 'Logistique'],
        'SEMC' => ['Industriels', 'Industrie'],
        'SIVC' => ['Industriels', 'Industrie'],
        'STAC' => ['Industriels', 'BTP'],

        // Services Financiers – correspondance exacte
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

        // Données des actions (à partir des données BRVM fournies)
        $rawActions = [
            ['symbole' => 'NTLC', 'nom' => "NESTLE COTE D'IVOIRE", 'volume' => 1197, 'cours_veille' => 13060, 'cours_ouverture' => 13060, 'cours_cloture' => 13055, 'variation' => -7.48],
            ['symbole' => 'PALC', 'nom' => "PALM COTE D'IVOIRE", 'volume' => 1986, 'cours_veille' => 7750, 'cours_ouverture' => 7750, 'cours_cloture' => 7725, 'variation' => -7.49],
            ['symbole' => 'SCRC', 'nom' => "SUCRIVOIRE COTE D'IVOIRE", 'volume' => 7375, 'cours_veille' => 1240, 'cours_ouverture' => 1200, 'cours_cloture' => 1245, 'variation' => -3.86],
            ['symbole' => 'SICC', 'nom' => "SICOR COTE D'IVOIRE", 'volume' => 38, 'cours_veille' => 3300, 'cours_ouverture' => 3300, 'cours_cloture' => 3300, 'variation' => -5.71],
            ['symbole' => 'SLBC', 'nom' => "SOLIBRA COTE D'IVOIRE", 'volume' => 352, 'cours_veille' => 24695, 'cours_ouverture' => 24695, 'cours_cloture' => 24695, 'variation' => 7.49],
            ['symbole' => 'SOGC', 'nom' => "SOGB COTE D'IVOIRE", 'volume' => 10067, 'cours_veille' => 7550, 'cours_ouverture' => 7840, 'cours_cloture' => 7505, 'variation' => -4.27],
            ['symbole' => 'SPHC', 'nom' => "SAPH COTE D'IVOIRE", 'volume' => 2424, 'cours_veille' => 7650, 'cours_ouverture' => 7890, 'cours_cloture' => 7795, 'variation' => -1.20],
            ['symbole' => 'STBC', 'nom' => "SITAB COTE D'IVOIRE", 'volume' => 4939, 'cours_veille' => 19795, 'cours_ouverture' => 19300, 'cours_cloture' => 19795, 'variation' => 2.56],
            ['symbole' => 'UNLC', 'nom' => "UNILEVER COTE D'IVOIRE", 'volume' => 0, 'cours_veille' => 23000, 'cours_ouverture' => 0, 'cours_cloture' => 23000, 'variation' => 0.00],

            ['symbole' => 'ABJC', 'nom' => "SERVAIR ABIDJAN COTE D'IVOIRE", 'volume' => 2711, 'cours_veille' => 2130, 'cours_ouverture' => 2155, 'cours_cloture' => 2130, 'variation' => -7.39],
            ['symbole' => 'BNBC', 'nom' => "BERNABE COTE D'IVOIRE", 'volume' => 1627, 'cours_veille' => 1805, 'cours_ouverture' => 1805, 'cours_cloture' => 1805, 'variation' => -7.44],
            ['symbole' => 'CFAC', 'nom' => "CFAO MOTORS COTE D'IVOIRE", 'volume' => 1281, 'cours_veille' => 2140, 'cours_ouverture' => 2140, 'cours_cloture' => 2140, 'variation' => -7.36],
            ['symbole' => 'LNBB', 'nom' => "LOTERIE NATIONALE DU BENIN", 'volume' => 367, 'cours_veille' => 3850, 'cours_ouverture' => 3800, 'cours_cloture' => 3850, 'variation' => 0.00],
            ['symbole' => 'NEIC', 'nom' => "NEI-CEDA COTE D'IVOIRE", 'volume' => 13787, 'cours_veille' => 885, 'cours_ouverture' => 885, 'cours_cloture' => 885, 'variation' => 7.27],
            ['symbole' => 'PRSC', 'nom' => "TRACTAFRIC MOTORS COTE D'IVOIRE", 'volume' => 3419, 'cours_veille' => 4055, 'cours_ouverture' => 4055, 'cours_cloture' => 4055, 'variation' => -7.42],
            ['symbole' => 'UNXC', 'nom' => "UNIWAX COTE D'IVOIRE", 'volume' => 49853, 'cours_veille' => 1570, 'cours_ouverture' => 1570, 'cours_cloture' => 1570, 'variation' => -7.37],

            ['symbole' => 'SHEC', 'nom' => "VIVO ENERGY COTE D'IVOIRE", 'volume' => 7476, 'cours_veille' => 1200, 'cours_ouverture' => 1200, 'cours_cloture' => 1190, 'variation' => -0.83],
            ['symbole' => 'SMBC', 'nom' => "SMB COTE D'IVOIRE", 'volume' => 453, 'cours_veille' => 9650, 'cours_ouverture' => 9880, 'cours_cloture' => 9500, 'variation' => -3.85],
            ['symbole' => 'TTLC', 'nom' => "TOTALENERGIES MARKETING COTE D'IVOIRE", 'volume' => 4924, 'cours_veille' => 2325, 'cours_ouverture' => 2380, 'cours_cloture' => 2325, 'variation' => -2.31],
            ['symbole' => 'TTLS', 'nom' => "TOTALENERGIES MARKETING SENEGAL", 'volume' => 8150, 'cours_veille' => 2500, 'cours_ouverture' => 2495, 'cours_cloture' => 2475, 'variation' => -1.00],

            ['symbole' => 'CABC', 'nom' => "SICABLE COTE D'IVOIRE", 'volume' => 627, 'cours_veille' => 1950, 'cours_ouverture' => 1950, 'cours_cloture' => 1985, 'variation' => 7.30],
            ['symbole' => 'FTSC', 'nom' => "FILTISAC COTE D'IVOIRE", 'volume' => 8749, 'cours_veille' => 2230, 'cours_ouverture' => 2230, 'cours_cloture' => 2225, 'variation' => -7.48],
            ['symbole' => 'SDSC', 'nom' => "AFRICA GLOBAL LOGISTICS COTE D'IVOIRE", 'volume' => 42861, 'cours_veille' => 1470, 'cours_ouverture' => 1470, 'cours_cloture' => 1490, 'variation' => 1.36],
            ['symbole' => 'SEMC', 'nom' => "EVIOSYS PACKAGING SIEM COTE D'IVOIRE", 'volume' => 0, 'cours_veille' => 700, 'cours_ouverture' => 0, 'cours_cloture' => 700, 'variation' => 0.00],
            ['symbole' => 'SIVC', 'nom' => "AIR LIQUIDE COTE D'IVOIRE", 'volume' => 1250, 'cours_veille' => 695, 'cours_ouverture' => 695, 'cours_cloture' => 690, 'variation' => -1.43],
            ['symbole' => 'STAC', 'nom' => "SETAO COTE D'IVOIRE", 'volume' => 11072, 'cours_veille' => 1180, 'cours_ouverture' => 1180, 'cours_cloture' => 1160, 'variation' => -7.20],

            ['symbole' => 'BICB', 'nom' => "BANQUE INTERNATIONALE POUR L’INDUSTRIE ET LE COMMERCE DU BENIN", 'volume' => 1367, 'cours_veille' => 5200, 'cours_ouverture' => 5110, 'cours_cloture' => 5200, 'variation' => -0.95],
            ['symbole' => 'BICC', 'nom' => "BICI COTE D'IVOIRE", 'volume' => 2319, 'cours_veille' => 18750, 'cours_ouverture' => 19330, 'cours_cloture' => 18900, 'variation' => -2.28],
            ['symbole' => 'BOAB', 'nom' => "BANK OF AFRICA BENIN", 'volume' => 1624, 'cours_veille' => 5000, 'cours_ouverture' => 5000, 'cours_cloture' => 4975, 'variation' => -0.50],
            ['symbole' => 'BOABF', 'nom' => "BANK OF AFRICA BURKINA FASO", 'volume' => 12440, 'cours_veille' => 3450, 'cours_ouverture' => 3500, 'cours_cloture' => 3495, 'variation' => -0.14],
            ['symbole' => 'BOAC', 'nom' => "BANK OF AFRICA COTE D'IVOIRE", 'volume' => 9141, 'cours_veille' => 7000, 'cours_ouverture' => 7095, 'cours_cloture' => 6960, 'variation' => -2.66],
            ['symbole' => 'BOAM', 'nom' => "BANK OF AFRICA MALI", 'volume' => 8024, 'cours_veille' => 3440, 'cours_ouverture' => 3440, 'cours_cloture' => 3440, 'variation' => -7.40],
            ['symbole' => 'BOAN', 'nom' => "BANK OF AFRICA NIGER", 'volume' => 2474, 'cours_veille' => 2595, 'cours_ouverture' => 2595, 'cours_cloture' => 2560, 'variation' => -1.35],
            ['symbole' => 'BOAS', 'nom' => "BANK OF AFRICA SENEGAL", 'volume' => 4362, 'cours_veille' => 5290, 'cours_ouverture' => 5405, 'cours_cloture' => 5300, 'variation' => -3.72],
            ['symbole' => 'CBIBF', 'nom' => "CORIS BANK INTERNATIONAL BURKINA FASO", 'volume' => 215, 'cours_veille' => 9995, 'cours_ouverture' => 9995, 'cours_cloture' => 9995, 'variation' => 0.00],
            ['symbole' => 'ECOC', 'nom' => "ECOBANK COTE D'IVOIRE", 'volume' => 1743, 'cours_veille' => 15110, 'cours_ouverture' => 15110, 'cours_cloture' => 15200, 'variation' => 0.60],
            ['symbole' => 'ETIT', 'nom' => "Ecobank Transnational Incorporated TOGO", 'volume' => 441472, 'cours_veille' => 22, 'cours_ouverture' => 22, 'cours_cloture' => 22, 'variation' => 0.00],
            ['symbole' => 'NSBC', 'nom' => "NSIA BANQUE COTE D'IVOIRE", 'volume' => 1907, 'cours_veille' => 11225, 'cours_ouverture' => 11490, 'cours_cloture' => 11300, 'variation' => -5.83],
            ['symbole' => 'ORGT', 'nom' => "ORAGROUP TOGO", 'volume' => 3340, 'cours_veille' => 2505, 'cours_ouverture' => 2500, 'cours_cloture' => 2490, 'variation' => -0.60],
            ['symbole' => 'SAFC', 'nom' => "SAFCA COTE D'IVOIRE", 'volume' => 1676, 'cours_veille' => 3085, 'cours_ouverture' => 3085, 'cours_cloture' => 3085, 'variation' => -7.36],
            ['symbole' => 'SGBC', 'nom' => "SOCIETE GENERALE COTE D'IVOIRE", 'volume' => 446, 'cours_veille' => 27700, 'cours_ouverture' => 27500, 'cours_cloture' => 27500, 'variation' => -0.72],
            ['symbole' => 'SIBC', 'nom' => "SOCIETE IVOIRIENNE DE BANQUE COTE D'IVOIRE", 'volume' => 4066, 'cours_veille' => 5730, 'cours_ouverture' => 5550, 'cours_cloture' => 5630, 'variation' => -1.92],

            ['symbole' => 'CIEC', 'nom' => "CIE COTE D'IVOIRE", 'volume' => 6790, 'cours_veille' => 2540, 'cours_ouverture' => 2560, 'cours_cloture' => 2580, 'variation' => -0.39],
            ['symbole' => 'SDCC', 'nom' => "SODE COTE D'IVOIRE", 'volume' => 10391, 'cours_veille' => 6025, 'cours_ouverture' => 5900, 'cours_cloture' => 6045, 'variation' => -1.63],

            ['symbole' => 'ONTBF', 'nom' => "ONATEL BURKINA FASO", 'volume' => 5636, 'cours_veille' => 2420, 'cours_ouverture' => 2420, 'cours_cloture' => 2415, 'variation' => -0.21],
            ['symbole' => 'ORAC', 'nom' => "ORANGE COTE D'IVOIRE", 'volume' => 886, 'cours_veille' => 14480, 'cours_ouverture' => 14400, 'cours_cloture' => 14405, 'variation' => -0.52],
            ['symbole' => 'SNTS', 'nom' => "SONATEL SENEGAL", 'volume' => 33115, 'cours_veille' => 26105, 'cours_ouverture' => 26105, 'cours_cloture' => 26100, 'variation' => -0.02],
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
