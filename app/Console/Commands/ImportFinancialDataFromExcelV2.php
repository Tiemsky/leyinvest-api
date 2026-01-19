<?php

namespace App\Console\Commands;

use App\Models\Action;
use App\Models\Employee;
use App\Models\Position;
use App\Models\QuarterlyResult;
use App\Models\Shareholder;
use App\Models\StockFinancial;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ImportFinancialDataFromExcelV2 extends Command
{
    protected $signature = 'import:financial-data-v2
        {file : Chemin vers le fichier Excel}
        {--dry-run : Simule l\'import sans sauvegarder}
        {--truncate : Vide les tables avant import}
        {--log-errors : Journalise les erreurs}';

    protected $description = 'Import complet des données financières, actionnaires, dirigeants et résultats trimestriels depuis un fichier Excel (format V2).';

    /**
     * Mapping Excel => colonnes de la table stock_financials
     */
    protected array $indicatorMapping = [
        'Total Immobilisation' => 'total_immobilisation',
        'Actif Circulant' => 'actif_circulant',
        'Total Actif' => 'total_actif',
        'Capitaux propres' => 'capitaux_propres',
        'Passif Circulant' => 'passif_circulant',
        'Chiffre d\'Affaires' => 'chiffre_affaires',
        'Valeur Ajoutée' => 'valeur_ajoutee',
        'Résultat avant Impôt' => 'resultat_avant_impot',
        'EBIT' => 'ebit',
        'EBITDA (RBE ou EBE)' => 'ebitda',
        'Résultat Net (RN)' => 'resultat_net',
        'PER' => 'per',
        'DNPA' => 'dnpa',
        'Dette totale' => 'dette_totale',
        'Cours au 31/12' => 'cours_31_12',
        'CAPEX' => 'capex',
        'Dividendes bruts' => 'dividendes_bruts',
    ];

    /**
     * Année pour les données trimestrielles (dernière année disponible)
     */
    protected int $quarterlyYear = 2025;

    public function handle(): void
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("❌ Fichier introuvable : $filePath");

            return;
        }

        $sheets = Excel::toArray([], $filePath);
        if (empty($sheets)) {
            $this->error("❌ Le fichier n'a aucune feuille.");

            return;
        }

        $dryRun = $this->option('dry-run');
        $truncate = $this->option('truncate');
        $logErrors = $this->option('log-errors');

        // if ($truncate && !$dryRun) {
        //     $this->warn("🗑️  Truncating tables...");
        //     DB::table('quarterly_results')->truncate();
        //     DB::table('shareholders')->truncate();
        //     DB::table('stock_financials')->truncate();
        //     DB::table('employees')->truncate();
        // }

        foreach ($sheets as $sheetIndex => $rows) {
            // Filtrer les lignes vides
            $rows = array_values(array_filter($rows, fn ($r) => is_array($r) && ! empty(array_filter($r, fn ($c) => trim((string) $c) !== ''))
            ));

            if (empty($rows)) {
                $this->warn("⚠️  Feuille vide : index $sheetIndex");

                continue;
            }

            // Symbole = cellule B2 (ligne 2, colonne 2 => index [1][1])
            $symbol = trim((string) ($rows[1][1] ?? ''));
            $symbol = preg_replace('/[«»\"\']/', '', $symbol);

            if (! preg_match('/^[A-Z]{3,6}$/', $symbol)) {
                $this->warn("⚠️  Symbole invalide '$symbol' dans la feuille $sheetIndex.");

                continue;
            }

            // Récupérer l'Action
            $action = Action::where('symbole', $symbol)->first();
            if (! $action) {
                $this->warn("⚠️  Action '$symbol' non trouvée en base de données.");

                continue;
            }

            try {
                if ($dryRun) {
                    $this->processSheet($rows, $action, true);
                } else {
                    DB::transaction(fn () => $this->processSheet($rows, $action, false));
                }

                $this->info("✅ Import réussi pour $symbol");

            } catch (\Exception $e) {
                $msg = "Échec import $symbol : ".$e->getMessage();
                $this->error("❌ $msg");

                if ($logErrors) {
                    Log::channel('daily')->error("[IMPORT-V2] $msg", [
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        }

        $this->info($dryRun ? '✅ Simulation terminée.' : '✅ Import terminé.');
    }

    /**
     * Traite une feuille Excel
     */
    protected function processSheet(array $rows, Action $action, bool $dryRun = false): void
    {
        // 1. Description
        $this->importDescription($rows, $action, $dryRun);

        // 2. Données Financières Annuelles
        $years = $this->extractYears($rows);
        $financialData = $this->extractFinancialData($rows, $years, $action->id);

        if (! $dryRun) {
            foreach ($financialData as $data) {
                StockFinancial::updateOrCreate(
                    ['action_id' => $action->id, 'year' => $data['year']],
                    $data
                );
            }
        }

        // 3. Actionnaires
        $this->importShareholders($rows, $action, $dryRun);

        // 4. Employés/Dirigeants
        $this->importEmployees($rows, $action, $dryRun);

        // 5. Résultats Trimestriels (NOUVEAU)
        $this->importQuarterlyResults($rows, $action, $dryRun);
    }

    /**
     * 1. DESCRIPTION
     * Ligne 1 : "Presentation | Symbole"
     * Ligne 2 : Description complète | Code boursier
     */
    protected function importDescription(array $rows, Action $action, bool $dryRun): void
    {
        // La description est en cellule A2 (ligne 2, colonne 1 => index [1][0])
        $description = trim((string) ($rows[1][0] ?? ''));

        // Vérifier que la description est suffisamment longue pour être valide
        if (strlen($description) > 20) {
            if (! $dryRun) {
                $action->update(['description' => $description]);
            }
            $this->info('  📝 Description importée');
        }
    }

    /**
     * 2. EXTRACTION DES ANNÉES
     * Ligne 6 : "Indicateurs | 2021 | 2022 | 2023 | 2024"
     */
    protected function extractYears(array $rows): array
    {
        foreach ($rows as $index => $row) {
            // Chercher la ligne qui commence par "Indicateurs"
            if (trim((string) ($row[0] ?? '')) === 'Indicateurs') {
                $years = [];

                // Les années commencent à la colonne 2 (index 1)
                for ($i = 1; $i < count($row); $i++) {
                    $val = trim((string) $row[$i]);
                    if (is_numeric($val) && strlen($val) === 4 && $val >= 2000 && $val <= 2100) {
                        $years[] = (int) $val;
                    }
                }

                if (empty($years)) {
                    throw new \Exception("Aucune année détectée sur la ligne 'Indicateurs'.");
                }

                $this->info('  📅 Années détectées : '.implode(', ', $years));

                return $years;
            }
        }

        throw new \Exception("Ligne 'Indicateurs' introuvable.");
    }

    /**
     * 3. DONNÉES FINANCIÈRES ANNUELLES
     */
    protected function extractFinancialData(array $rows, array $years, int $actionId): array
    {
        $data = [];

        // Initialiser les données pour chaque année
        foreach ($years as $year) {
            $data[$year] = [
                'year' => $year,
                'action_id' => $actionId,
            ];
        }

        // Créer un mapping case-insensitive
        $lowercaseMapping = [];
        foreach ($this->indicatorMapping as $key => $value) {
            $lowercaseMapping[mb_strtolower($key)] = $value;
        }

        // Parcourir les lignes pour extraire les indicateurs
        foreach ($rows as $row) {
            $label = trim((string) ($row[0] ?? ''));
            $labelLower = mb_strtolower($label);

            // Mapping des indicateurs financiers (case-insensitive)
            if (isset($lowercaseMapping[$labelLower])) {
                $field = $lowercaseMapping[$labelLower];

                foreach ($years as $index => $year) {
                    $val = $row[$index + 1] ?? null;
                    $data[$year][$field] = $this->parseNumeric($val);
                }
            }

            // Traitement spécial pour le nombre de titres (case-insensitive)
            if (str_contains($labelLower, 'nombre de titres')) {
                foreach ($years as $index => $year) {
                    $val = $row[$index + 1] ?? null;
                    $data[$year]['nombre_titre'] = $this->parseNumeric($val);
                }
            }
        }

        $this->info('  💰 Données financières extraites pour '.count($years).' année(s)');

        return $data;
    }

    /**
     * 4. ACTIONNAIRES
     * Ligne 25 : "Actionnaires | Pourcentage"
     * Lignes suivantes : Nom | Pourcentage
     */
    protected function importShareholders(array $rows, Action $action, bool $dryRun): void
    {
        $start = null;

        // Trouver la ligne "Actionnaires"
        foreach ($rows as $i => $row) {
            if (trim((string) ($row[0] ?? '')) === 'Actionnaires') {
                $start = $i + 1; // Les données commencent à la ligne suivante
                break;
            }
        }

        if (! $start) {
            $this->warn("  ⚠️  Section 'Actionnaires' non trouvée");

            return;
        }

        if (! $dryRun) {
            Shareholder::where('action_id', $action->id)->delete();
        }

        $rank = 1;
        $count = 0;

        for ($i = $start; $i < count($rows); $i++) {
            $name = trim((string) ($rows[$i][0] ?? ''));
            $pctRaw = trim((string) ($rows[$i][1] ?? ''));

            // Arrêter si on rencontre une nouvelle section
            if (in_array($name, ['Fonction', 'Indicateurs', 'Presentation', 'Présentation', ''])) {
                break;
            }

            // Ignorer les lignes sans nom ou sans pourcentage
            if ($name === '' || $pctRaw === '') {
                continue;
            }

            if (! $dryRun) {
                Shareholder::create([
                    'action_id' => $action->id,
                    'nom' => $name,
                    'pourcentage' => $this->parsePercentage($pctRaw),
                    'rang' => $rank++,
                ]);
            }
            $count++;
        }

        $this->info("  👥 $count actionnaire(s) importé(s)");
    }

    /**
     * 5. EMPLOYÉS (DIRIGEANTS)
     * Structure variable :
     * - Ligne X : "Fonction" (peut être ligne 29, 30 ou 31)
     * - Première ligne non-vide après : PCA | DG | DAF | ... (FONCTIONS)
     * - Deuxième ligne non-vide après : M. X | M. Y | ... (NOMS)
     */
    protected function importEmployees(array $rows, Action $action, bool $dryRun): void
    {
        $index = null;

        // Chercher l'index de la ligne "Fonction"
        foreach ($rows as $i => $row) {
            if (trim((string) ($row[0] ?? '')) === 'Fonction') {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            $this->warn("  ⚠️  Section 'Fonction' non trouvée");

            return;
        }

        // Chercher la PREMIÈRE ligne NON-VIDE après "Fonction" = ligne des FONCTIONS
        $funcRow = null;
        $funcRowIndex = null;

        for ($i = $index + 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            // Vérifier si la ligne contient des données (au moins une cellule non-vide)
            $hasData = false;
            foreach ($row as $cell) {
                if (trim((string) $cell) !== '') {
                    $hasData = true;
                    break;
                }
            }

            if ($hasData) {
                $funcRow = $row;
                $funcRowIndex = $i;
                Log::info("✓ Ligne des fonctions trouvée à l'index $i");
                break;
            }
        }

        if ($funcRow === null) {
            $this->warn("  ⚠️  Ligne des fonctions non trouvée après 'Fonction'");

            return;
        }

        // Chercher la DEUXIÈME ligne NON-VIDE = ligne des NOMS
        $nameRow = null;

        for ($i = $funcRowIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            // Vérifier si la ligne contient des données
            $hasData = false;
            foreach ($row as $cell) {
                if (trim((string) $cell) !== '') {
                    $hasData = true;
                    break;
                }
            }

            if ($hasData) {
                $nameRow = $row;
                Log::info("✓ Ligne des noms trouvée à l'index $i");
                break;
            }
        }

        if ($nameRow === null) {
            $this->warn('  ⚠️  Ligne des noms non trouvée après les fonctions');

            return;
        }

        if (! $dryRun) {
            Employee::where('action_id', $action->id)->delete();
        }

        $maxCols = max(count($funcRow), count($nameRow));
        $count = 0;

        for ($col = 0; $col < $maxCols; $col++) {
            $func = trim((string) ($funcRow[$col] ?? ''));
            $name = trim((string) ($nameRow[$col] ?? ''));

            // Ignorer les colonnes vides
            if ($func === '' || $name === '') {
                continue;
            }

            // Vérifier si c'est vraiment une fonction (pas un nom de personne)
            // Les noms contiennent généralement "M.", "Mme", "Mr.", "Monsieur", "Madame"
            if (preg_match('/^(M\.|Mme|Mr\.|Monsieur|Madame)/i', $func)) {
                Log::warning("⚠️  Confusion détectée : '$func' semble être un NOM, pas une FONCTION (action {$action->symbole}). Colonne ignorée.");

                continue;
            }

            // Normalisation de la fonction avec slug
            $slug = Str::slug($func);

            Log::info("Mapping fonction '$func' => slug '$slug'");

            $position = Position::where('slug', $slug)->first();

            if (! $position) {
                Log::warning("Position inconnue : '$func' (slug: '$slug') pour l'action {$action->symbole}. Créez la position dans la table 'positions'.");

                continue;
            }

            if (! $dryRun) {
                Employee::create([
                    'nom' => $name,
                    'position_id' => $position->id,
                    'action_id' => $action->id,
                ]);
            }
            $count++;
        }

        $this->info("  👔 $count dirigeant(s) importé(s)");
    }

    /**
     * 6. RÉSULTATS TRIMESTRIELS (NOUVEAU)
     * Ligne 35 : En-tête (vide | Valeur T1 | Évol. T1 | Valeur T2 | Évol. T2 | ...)
     * Ligne 36 : Chiffre d'affaires | valeurs...
     * Ligne 37 : Résultat Net | valeurs...
     */
    protected function importQuarterlyResults(array $rows, Action $action, bool $dryRun): void
    {
        $headerIndex = null;

        // Trouver la ligne d'en-tête des trimestres
        foreach ($rows as $i => $row) {
            $firstCell = trim((string) ($row[0] ?? ''));
            $secondCell = trim((string) ($row[1] ?? ''));

            // La ligne d'en-tête contient "Valeur T1" en colonne 2
            if (str_contains($secondCell, 'Valeur T1') || str_contains($secondCell, 'T1')) {
                $headerIndex = $i;
                break;
            }
        }

        if ($headerIndex === null) {
            $this->warn("  ⚠️  Section 'Résultats Trimestriels' non trouvée");

            return;
        }

        if (! $dryRun) {
            QuarterlyResult::where('action_id', $action->id)
                ->where('year', $this->quarterlyYear)
                ->delete();
        }

        // Lignes de données
        $caRow = $rows[$headerIndex + 1] ?? [];
        $rnRow = $rows[$headerIndex + 2] ?? [];

        $count = 0;

        // Parcourir les 4 trimestres
        for ($trimestre = 1; $trimestre <= 4; $trimestre++) {
            // Calcul des index des colonnes
            // T1: col 1 (valeur), col 2 (évol)
            // T2: col 3 (valeur), col 4 (évol)
            // T3: col 5 (valeur), col 6 (évol)
            // T4: col 7 (valeur), col 8 (évol)
            $valIndex = ($trimestre - 1) * 2 + 1;
            $evolIndex = $valIndex + 1;

            $caValue = $this->parseNumeric($caRow[$valIndex] ?? null);
            $caEvol = $this->parseNumeric($caRow[$evolIndex] ?? null);
            $rnValue = $this->parseNumeric($rnRow[$valIndex] ?? null);
            $rnEvol = $this->parseNumeric($rnRow[$evolIndex] ?? null);

            // Créer l'enregistrement uniquement si au moins une valeur est présente
            if ($caValue !== null || $caEvol !== null || $rnValue !== null || $rnEvol !== null) {
                if (! $dryRun) {
                    QuarterlyResult::create([
                        'action_id' => $action->id,
                        'year' => $this->quarterlyYear,
                        'trimestre' => $trimestre,
                        'chiffre_affaires' => $caValue,
                        'evolution_ca' => $caEvol,
                        'resultat_net' => $rnValue,
                        'evolution_rn' => $rnEvol,
                    ]);
                }
                $count++;
            }
        }

        $this->info("  📊 $count trimestre(s) importé(s)");
    }

    /**
     * HELPERS
     */
    protected function parseNumeric($value): ?float
    {
        if ($value === null || $value === '' || in_array((string) $value, ['-', '–', 'ND'], true)) {
            return null;
        }

        // Supprime les espaces, espaces insécables et remplace la virgule par un point
        $clean = str_replace([' ', "\xc2\xa0", "\u{00A0}", ','], ['', '', '', '.'], (string) $value);

        return is_numeric($clean) ? (float) $clean : null;
    }

    protected function parsePercentage($value): float
    {
        // Supprime %, espaces, et remplace la virgule par un point
        $clean = str_replace(['%', ' ', "\xc2\xa0", "\u{00A0}", ','], ['', '', '', '', '.'], (string) $value);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }
}
