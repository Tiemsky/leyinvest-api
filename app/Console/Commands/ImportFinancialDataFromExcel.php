<?php

namespace App\Console\Commands;

use App\Models\Action;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Shareholder;
use App\Models\StockFinancial;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ImportFinancialDataFromExcel extends Command
{
    protected $signature = 'import:financial-data
        {file : Chemin vers le fichier Excel}
        {--dry-run : Simule l\'import sans sauvegarder}
        {--truncate : Vide les tables avant import}
        {--log-errors : Journalise les erreurs}';

    protected $description = 'Import complet des données financières, actionnaires et dirigeants depuis un fichier Excel.';

    /**
     * Mapping Excel => colonnes de la table stock_financials
     */
    protected array $indicatorMapping = [
        'Total Immobilisation' => 'total_immobilisation',
        'Crédits à la clientèle' => 'credits_clientele',
        'Dépôts de la clientèle' => 'depots_clientele',
        'Capitaux propres' => 'capitaux_propres',
        'Dette totale' => 'dette_totale',
        'Total Actif' => 'total_actif',
        'Produit Net Bancaire' => 'produit_net_bancaire',
        'EBIT (RE)' => 'ebit',
        'EBITDA (RBE ou EBE)' => 'ebitda',
        'Résultat avant Impôt' => 'resultat_avant_impot',
        'Résultat Net' => 'resultat_net',
        'Coût du Risque' => 'cout_du_risque',
        'PER' => 'per',
        'DNPA' => 'dnpa',
        'Cours au 31/12' => 'cours_31_12',
        'CAPEX' => 'capex',
        'Dividendes Bruts' => 'dividendes_bruts',
    ];

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

        if ($truncate && ! $dryRun) {
            DB::table('shareholders')->truncate();
            DB::table('stock_financials')->truncate();
            DB::table('employees')->truncate();
        }

        foreach ($sheets as $sheetName => $rows) {

            $rows = array_values(array_filter($rows, fn ($r) => is_array($r) && ! empty(array_filter($r, fn ($c) => trim((string) $c) !== ''))
            ));

            if (empty($rows)) {
                $this->warn("⚠️ Feuille vide : $sheetName");

                continue;
            }

            // Symbole = cellule B1
            $symbol = trim((string) ($rows[0][1] ?? ''));
            $symbol = preg_replace('/[«»\"\']/', '', $symbol);

            if (! preg_match('/^[A-Z]{3,6}$/', $symbol)) {
                $this->warn("⚠️ Symbole invalide '$symbol'.");

                continue;
            }

            // --- Votre recommandation : Récupération de l'Action ---
            $action = Action::where('symbole', $symbol)->first();
            if (! $action) {
                $this->warn("⚠️ Action '$symbol' non trouvée.");

                continue;
            }
            // --------------------------------------------------------

            try {
                if ($dryRun) {
                    $this->processSheet($rows, $action, true);
                } else {
                    DB::transaction(fn () => $this->processSheet($rows, $action, false));
                }

                $this->info("✓ Import réussi pour $symbol");

            } catch (\Exception $e) {
                $msg = "Échec import $symbol : ".$e->getMessage();
                $this->error("✗ $msg");

                if ($logErrors) {
                    Log::channel('daily')->error("[IMPORT] $msg");
                }
            }
        }

        $this->info($dryRun ? 'Simulation terminée.' : 'Import terminé.');
    }

    /**
     * Traite une feuille Excel
     */
    protected function processSheet(array $rows, Action $action, bool $dryRun = false): void
    {
        // 1. Description
        $this->importDescription($rows, $action, $dryRun);

        // 2. Données Financières
        $years = $this->extractYears($rows);
        $financialData = $this->extractFinancialData($rows, $years);

        if (! $dryRun) {
            foreach ($financialData as $data) {
                // --- FIX 1: Injection de l'action_id avant updateOrCreate ---
                $data['action_id'] = $action->id;
                // ----------------------------------------------------------

                StockFinancial::updateOrCreate(
                    // Utilisation de l'ID pour la clé de recherche
                    ['action_id' => $action->id, 'year' => $data['year']],
                    // $data contient maintenant l'action_id correct, évitant le NULL
                    $data
                );
            }
        }

        // 3. Actionnaires
        $this->importShareholders($rows, $action, $dryRun);

        // 4. Employés/Dirigeants
        $this->importEmployees($rows, $action, $dryRun);
    }

    /**
     * 1. DESCRIPTION
     */
    protected function importDescription(array $rows, Action $action, bool $dryRun)
    {
        for ($i = 0; $i < count($rows); $i++) {
            $colA = trim($rows[$i][0] ?? '');

            if (strtolower($colA) === 'presentation' || strtolower($colA) === 'présentation') {

                $desc = trim($rows[$i + 1][0] ?? '');

                // S'assurer que la description a une taille suffisante pour être valide
                if (strlen($desc) > 20) {
                    if (! $dryRun) {
                        $action->update(['description' => $desc]);
                    }

                    return;
                }
            }
        }
    }

    /**
     * 2. EXTRACTION DES ANNÉES
     */
    protected function extractYears(array $rows): array
    {
        foreach ($rows as $r) {
            if (trim($r[0] ?? '') === 'Indicateurs') {

                $years = [];
                // Commence à l'index 1 pour lire les années
                for ($i = 1; $i < count($r); $i++) {
                    $val = trim((string) $r[$i]);
                    if (is_numeric($val) && strlen($val) === 4) { // Vérifie que c'est bien une année (4 chiffres)
                        $years[] = (int) $val;
                    }
                }

                if (empty($years)) {
                    throw new \Exception('Aucune année détectée.');
                }

                return $years;
            }
        }

        throw new \Exception("Ligne 'Indicateurs' introuvable.");
    }

    /**
     * 3. DONNÉES FINANCIÈRES
     */
    protected function extractFinancialData(array $rows, array $years): array
    {
        $data = [];

        foreach ($years as $year) {
            $data[$year] = [
                'year' => $year,
                // --- FIX 2: Suppression de l'initialisation de 'action_id' à null ici. ---
                // 'action_id' sera injecté plus tard dans processSheet.
                // ----------------------------------------------------------------------
            ];
        }

        foreach ($rows as $r) {
            $label = trim($r[0] ?? '');

            if (isset($this->indicatorMapping[$label])) {
                $field = $this->indicatorMapping[$label];

                foreach ($years as $index => $year) {
                    $val = $r[$index + 1] ?? null;
                    $data[$year][$field] = $this->parseNumeric($val);
                }
            }

            if (str_contains($label, 'Nombre de titres')) {
                foreach ($years as $index => $year) {
                    $val = $r[$index + 1] ?? null;
                    $data[$year]['nombre_titre'] = $this->parseNumeric($val);
                }
            }
        }

        return $data;
    }

    /**
     * 4. ACTIONNAIRES
     */
    protected function importShareholders(array $rows, Action $action, bool $dryRun)
    {
        $start = null;

        foreach ($rows as $i => $r) {
            if (trim($r[0] ?? '') === 'Actionnaires') {
                // Tente de trouver la ligne d'en-tête (e.g., "Nom" ou "Pourcentage") et commence après
                $start = $i + 1;
                // Assumer que la première colonne de la ligne suivante est l'en-tête.
                if (isset($rows[$i + 1]) && strtolower(trim($rows[$i + 1][0] ?? '')) !== 'fonction' && strtolower(trim($rows[$i + 1][0] ?? '')) !== 'actionnaires' && strtolower(trim($rows[$i + 1][0] ?? '')) !== 'presentation' && ! empty(trim($rows[$i + 1][0] ?? ''))) {
                    // Si la ligne suivante n'est pas un nouveau marqueur, c'est probablement l'en-tête ou la première donnée.
                    // On va tester la première ligne d'actionnaires.
                    $start = $i + 1;
                } else {
                    $start = $i + 1; // Commence directement après "Actionnaires"
                }

                // Ajustement pour sauter l'en-tête s'il y en a un (e.g. "Nom" et "Pourcentage")
                if (isset($rows[$i + 1][1]) && is_string($rows[$i + 1][1]) && strtolower(trim($rows[$i + 1][1])) === 'pourcentage') {
                    $start = $i + 2; // Saute l'en-tête explicite
                }
                break;
            }
        }

        if (! $start) {
            return;
        }

        if (! $dryRun) {
            Shareholder::where('action_id', $action->id)->delete();
        }

        $rank = 1;
        for ($i = $start; $i < count($rows); $i++) {

            $name = trim($rows[$i][0] ?? '');

            // Si on rencontre un marqueur de section suivant ou une ligne de fin de liste vide
            if ($name === 'Fonction' || $name === 'Indicateurs' || $name === 'Presentation' || $name === 'Présentation') {
                break;
            }

            // Si la première colonne est vide, on s'arrête (fin de section)
            if ($name === '') {
                // On pourrait encore avoir des lignes vides, vérifions la colonne 1 aussi
                if (trim($rows[$i][1] ?? '') === '') {
                    break;
                }
            }

            $pctRaw = trim($rows[$i][1] ?? '');

            // Si le nom est là mais le pourcentage est vide, on ignore cette ligne (e.g. ligne de séparateur)
            if ($name !== '' && $pctRaw === '') {
                continue;
            }

            if ($name !== '' && $pctRaw !== '') {
                if (! $dryRun) {
                    Shareholder::create([
                        'action_id' => $action->id,
                        'nom' => $name,
                        'pourcentage' => $this->parsePercentage($pctRaw),
                        'rang' => $rank++,
                    ]);
                }
            }
        }
    }

    /**
     * 5. EMPLOYÉS (DIRIGEANTS)
     * Structure attendue :
     * * Ligne X : Fonction
     * Ligne X+1 : Fonction1 | Fonction2 | Fonction3
     * Ligne X+2 : Nom1      | Nom2      | Nom3
     */
    protected function importEmployees(array $rows, Action $action, bool $dryRun)
    {
        $index = null;

        // Cherche l'index de la ligne "Fonction"
        foreach ($rows as $i => $r) {
            if (trim($r[0] ?? '') === 'Fonction') {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            return;
        }

        // La ligne des libellés de fonction (e.g., "PCA", "DG") est $index + 1
        $funcRow = $rows[$index + 1] ?? [];
        // La ligne des noms de personnes (e.g., "M. DUPONT") est $index + 2
        $nameRow = $rows[$index + 2] ?? [];

        if (! $dryRun) {
            Employee::where('action_id', $action->id)->delete();
        }

        $maxCols = max(count($funcRow), count($nameRow));

        for ($col = 0; $col < $maxCols; $col++) {

            $func = trim($funcRow[$col] ?? '');
            $name = trim($nameRow[$col] ?? '');

            if ($func === '' || $name === '') {
                continue;
            }

            // Mapping intelligent (normalisation)
            $mappedFunc = match ($func) {
                'Directeur Marketing/commercial' => 'Directeur Marketing/Commercial',
                default => $func,
            };

            $position = Position::where('nom', $mappedFunc)->first();

            if (! $position) {
                // Utiliser $func, qui est la valeur réelle du fichier Excel pour le log
                Log::warning("Position inconnue : '$func' (action {$action->symbole}). Vérifiez votre table 'positions'.");

                continue;
            }

            if (! $dryRun) {
                Employee::create([
                    'nom' => $name,
                    'position_id' => $position->id,
                    'action_id' => $action->id,
                ]);
            }
        }
    }

    /**
     * Helpers
     */
    protected function parseNumeric($value): ?float
    {
        if ($value === null || $value === '' || in_array((string) $value, ['-', 'ND'], true)) {
            return null;
        }
        // Supprime les espaces insécables et remplace la virgule par un point
        $clean = str_replace([' ', "\xc2\xa0", ','], ['', '', '.'], (string) $value);

        return is_numeric($clean) ? (float) $clean : null;
    }

    protected function parsePercentage($value): float
    {
        // Supprime %, espaces, et remplace la virgule par un point
        $clean = str_replace(['%', ' ', "\xc2\xa0", ','], ['', '', '', '.'], (string) $value);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }
}
