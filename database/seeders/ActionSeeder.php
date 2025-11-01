<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Action;

class ActionSeeder extends Seeder
{
    /**
     * Exécution du seeder des actions BRVM.
     */
    public function run(): void
    {
        $actions = collect([
            ['symbole' => 'ABJC', 'nom' => "SERVAIR ABIDJAN COTE D'IVOIRE", 'volume' => 1634, 'cours_veille' => 2350, 'cours_ouverture' => 2370, 'cours_cloture' => 2320, 'variation' => -2.32],
            ['symbole' => 'BICB', 'nom' => "BANQUE INTERNATIONALE POUR L’INDUSTRIE ET LE COMMERCE DU BENIN", 'volume' => 8329, 'cours_veille' => 5250, 'cours_ouverture' => 5285, 'cours_cloture' => 5250, 'variation' => -0.85],
            ['symbole' => 'BICC', 'nom' => "BICI COTE D'IVOIRE", 'volume' => 838, 'cours_veille' => 19000, 'cours_ouverture' => 19400, 'cours_cloture' => 19345, 'variation' => -0.28],
            ['symbole' => 'BNBC', 'nom' => "BERNABE COTE D'IVOIRE", 'volume' => 1186, 'cours_veille' => 1955, 'cours_ouverture' => 1995, 'cours_cloture' => 1940, 'variation' => -2.76],
            ['symbole' => 'BOAB', 'nom' => "BANK OF AFRICA BENIN", 'volume' => 562, 'cours_veille' => 5000, 'cours_ouverture' => 4900, 'cours_cloture' => 4900, 'variation' => -2.00],
            ['symbole' => 'BOABF', 'nom' => "BANK OF AFRICA BURKINA FASO", 'volume' => 3205, 'cours_veille' => 3670, 'cours_ouverture' => 3670, 'cours_cloture' => 3670, 'variation' => -0.14],
            ['symbole' => 'BOAC', 'nom' => "BANK OF AFRICA COTE D'IVOIRE", 'volume' => 2199, 'cours_veille' => 7110, 'cours_ouverture' => 7110, 'cours_cloture' => 7145, 'variation' => 0.49],
            ['symbole' => 'BOAM', 'nom' => "BANK OF AFRICA MALI", 'volume' => 11119, 'cours_veille' => 3720, 'cours_ouverture' => 3890, 'cours_cloture' => 3715, 'variation' => -7.47],
            ['symbole' => 'BOAN', 'nom' => "BANK OF AFRICA NIGER", 'volume' => 16501, 'cours_veille' => 2590, 'cours_ouverture' => 2580, 'cours_cloture' => 2600, 'variation' => 0.39],
            ['symbole' => 'BOAS', 'nom' => "BANK OF AFRICA SENEGAL", 'volume' => 1418, 'cours_veille' => 5530, 'cours_ouverture' => 5600, 'cours_cloture' => 5510, 'variation' => -0.36],
            ['symbole' => 'CABC', 'nom' => "SICABLE COTE D'IVOIRE", 'volume' => 621, 'cours_veille' => 1850, 'cours_ouverture' => 1800, 'cours_cloture' => 1850, 'variation' => 7.25],
            ['symbole' => 'CBIBF', 'nom' => "CORIS BANK INTERNATIONAL BURKINA FASO", 'volume' => 1420, 'cours_veille' => 9995, 'cours_ouverture' => 9900, 'cours_cloture' => 9995, 'variation' => 0.00],
            ['symbole' => 'CFAC', 'nom' => "CFAO MOTORS COTE D'IVOIRE", 'volume' => 5531, 'cours_veille' => 2310, 'cours_ouverture' => 2310, 'cours_cloture' => 2310, 'variation' => -7.41],
            ['symbole' => 'CIEC', 'nom' => "CIE COTE D'IVOIRE", 'volume' => 4337, 'cours_veille' => 2600, 'cours_ouverture' => 2545, 'cours_cloture' => 2590, 'variation' => 2.37],
            ['symbole' => 'ECOC', 'nom' => "ECOBANK COTE D'IVOIRE", 'volume' => 426, 'cours_veille' => 15150, 'cours_ouverture' => 15015, 'cours_cloture' => 15100, 'variation' => -0.33],
            ['symbole' => 'ETIT', 'nom' => "Ecobank Transnational Incorporated TOGO", 'volume' => 372598, 'cours_veille' => 22, 'cours_ouverture' => 22, 'cours_cloture' => 22, 'variation' => 0.00],
            ['symbole' => 'FTSC', 'nom' => "FILTISAC COTE D'IVOIRE", 'volume' => 13612, 'cours_veille' => 2405, 'cours_ouverture' => 2405, 'cours_cloture' => 2405, 'variation' => -7.50],
            ['symbole' => 'LNBB', 'nom' => "LOTERIE NATIONALE DU BENIN", 'volume' => 112, 'cours_veille' => 3980, 'cours_ouverture' => 3820, 'cours_cloture' => 3980, 'variation' => 4.33],
            ['symbole' => 'NEIC', 'nom' => "NEI-CEDA COTE D'IVOIRE", 'volume' => 20912, 'cours_veille' => 825, 'cours_ouverture' => 770, 'cours_cloture' => 825, 'variation' => 7.14],
            ['symbole' => 'NSBC', 'nom' => "NSIA BANQUE COTE D'IVOIRE", 'volume' => 1415, 'cours_veille' => 11950, 'cours_ouverture' => 11950, 'cours_cloture' => 12050, 'variation' => 0.84],
            ['symbole' => 'NTLC', 'nom' => "NESTLE COTE D'IVOIRE", 'volume' => 348, 'cours_veille' => 14110, 'cours_ouverture' => 14110, 'cours_cloture' => 14110, 'variation' => -7.48],
            ['symbole' => 'ONTBF', 'nom' => "ONATEL BURKINA FASO", 'volume' => 4516, 'cours_veille' => 2410, 'cours_ouverture' => 2410, 'cours_cloture' => 2415, 'variation' => 0.21],
            ['symbole' => 'ORAC', 'nom' => "ORANGE COTE D'IVOIRE", 'volume' => 635, 'cours_veille' => 14490, 'cours_ouverture' => 14490, 'cours_cloture' => 14400, 'variation' => -0.62],
            ['symbole' => 'ORGT', 'nom' => "ORAGROUP TOGO", 'volume' => 1951, 'cours_veille' => 2535, 'cours_ouverture' => 2570, 'cours_cloture' => 2525, 'variation' => -2.51],
            ['symbole' => 'PALC', 'nom' => "PALM COTE D'IVOIRE", 'volume' => 2675, 'cours_veille' => 7930, 'cours_ouverture' => 7930, 'cours_cloture' => 7930, 'variation' => -7.47],
            ['symbole' => 'PRSC', 'nom' => "TRACTAFRIC MOTORS COTE D'IVOIRE", 'volume' => 7485, 'cours_veille' => 4380, 'cours_ouverture' => 4450, 'cours_cloture' => 4380, 'variation' => -7.50],
            ['symbole' => 'SAFC', 'nom' => "SAFCA COTE D'IVOIRE", 'volume' => 10872, 'cours_veille' => 3330, 'cours_ouverture' => 3400, 'cours_cloture' => 3330, 'variation' => -7.50],
            ['symbole' => 'SCRC', 'nom' => "SUCRIVOIRE COTE D'IVOIRE", 'volume' => 9749, 'cours_veille' => 1300, 'cours_ouverture' => 1390, 'cours_cloture' => 1300, 'variation' => -6.81],
            ['symbole' => 'SDCC', 'nom' => "SODE COTE D'IVOIRE", 'volume' => 311, 'cours_veille' => 6100, 'cours_ouverture' => 6020, 'cours_cloture' => 6050, 'variation' => -0.82],
            ['symbole' => 'SDSC', 'nom' => "AFRICA GLOBAL LOGISTICS COTE D'IVOIRE", 'volume' => 52151, 'cours_veille' => 1450, 'cours_ouverture' => 1455, 'cours_cloture' => 1470, 'variation' => 1.38],
            ['symbole' => 'SEMC', 'nom' => "EVIOSYS PACKAGING SIEM COTE D'IVOIRE", 'volume' => 0, 'cours_veille' => 700, 'cours_ouverture' => 0, 'cours_cloture' => 700, 'variation' => 0.00],
            ['symbole' => 'SGBC', 'nom' => "SOCIETE GENERALE COTE D'IVOIRE", 'volume' => 520, 'cours_veille' => 27800, 'cours_ouverture' => 27800, 'cours_cloture' => 27700, 'variation' => -0.36],
            ['symbole' => 'SHEC', 'nom' => "VIVO ENERGY COTE D'IVOIRE", 'volume' => 1900, 'cours_veille' => 1200, 'cours_ouverture' => 1200, 'cours_cloture' => 1200, 'variation' => 0.00],
            ['symbole' => 'SIBC', 'nom' => "SOCIETE IVOIRIENNE DE BANQUE COTE D'IVOIRE", 'volume' => 4987, 'cours_veille' => 5700, 'cours_ouverture' => 5850, 'cours_cloture' => 5700, 'variation' => 1.88],
            ['symbole' => 'SICC', 'nom' => "SICOR COTE D'IVOIRE", 'volume' => 10, 'cours_veille' => 3500, 'cours_ouverture' => 3500, 'cours_cloture' => 3500, 'variation' => 0.00],
            ['symbole' => 'SIVC', 'nom' => "AIR LIQUIDE COTE D'IVOIRE", 'volume' => 4500, 'cours_veille' => 700, 'cours_ouverture' => 700, 'cours_cloture' => 700, 'variation' => 0.00],
            ['symbole' => 'SLBC', 'nom' => "SOLIBRA COTE D'IVOIRE", 'volume' => 951, 'cours_veille' => 22975, 'cours_ouverture' => 22975, 'cours_cloture' => 22955, 'variation' => 7.39],
            ['symbole' => 'SMBC', 'nom' => "SMB COTE D'IVOIRE", 'volume' => 1016, 'cours_veille' => 9920, 'cours_ouverture' => 9650, 'cours_cloture' => 9900, 'variation' => 2.59],
            ['symbole' => 'SNTS', 'nom' => "SONATEL SENEGAL", 'volume' => 2629, 'cours_veille' => 26200, 'cours_ouverture' => 26200, 'cours_cloture' => 26100, 'variation' => -0.38],
            ['symbole' => 'SOGC', 'nom' => "SOGB COTE D'IVOIRE", 'volume' => 25289, 'cours_veille' => 7500, 'cours_ouverture' => 7320, 'cours_cloture' => 7500, 'variation' => -5.18],
            ['symbole' => 'SPHC', 'nom' => "SAPH COTE D'IVOIRE", 'volume' => 34953, 'cours_veille' => 7500, 'cours_ouverture' => 7125, 'cours_cloture' => 7500, 'variation' => -2.53],
            ['symbole' => 'STAC', 'nom' => "SETAO COTE D'IVOIRE", 'volume' => 2843, 'cours_veille' => 1200, 'cours_ouverture' => 1200, 'cours_cloture' => 1205, 'variation' => -3.98],
            ['symbole' => 'STBC', 'nom' => "SITAB COTE D'IVOIRE", 'volume' => 3570, 'cours_veille' => 19600, 'cours_ouverture' => 19795, 'cours_cloture' => 19300, 'variation' => -1.53],
            ['symbole' => 'TTLC', 'nom' => "TOTALENERGIES MARKETING COTE D'IVOIRE", 'volume' => 8014, 'cours_veille' => 2350, 'cours_ouverture' => 2350, 'cours_cloture' => 2355, 'variation' => 0.21],
            ['symbole' => 'TTLS', 'nom' => "TOTALENERGIES MARKETING SENEGAL", 'volume' => 809, 'cours_veille' => 2490, 'cours_ouverture' => 2475, 'cours_cloture' => 2500, 'variation' => 0.40],
            ['symbole' => 'UNLC', 'nom' => "UNILEVER COTE D'IVOIRE", 'volume' => 0, 'cours_veille' => 23000, 'cours_ouverture' => 0, 'cours_cloture' => 23000, 'variation' => 0.00],
            ['symbole' => 'UNXC', 'nom' => "UNIWAX COTE D'IVOIRE", 'volume' => 86939, 'cours_veille' => 1695, 'cours_ouverture' => 1800, 'cours_cloture' => 1695, 'variation' => -7.38],
        ]);

        // On vide la table avant d’insérer les nouvelles données
        Action::truncate();

        // Insertion propre et rapide
        $actions->each(fn($action) =>
            Action::create(array_merge($action, [
                'key' => 'act_' . strtolower(Str::random(8)),
            ]))
        );
    }
}
