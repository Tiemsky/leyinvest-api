<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Action;
use App\Models\StockFinancial;
use App\Models\Shareholder;
use App\Models\Position;
use App\Models\Employee;
use App\Models\QuarterlyResult;
use Maatwebsite\Excel\Facades\Excel;

class ImportSector extends Command
{
    protected $signature = 'import:data
        {file : Chemin vers le fichier Excel}
        {--dry-run : Simule l\'import sans sauvegarder}
        {--truncate : Vide les tables avant import}
        {--log-errors : Journalise les erreurs}';

    protected $description = 'Import complet des données financières, actionnaires, dirigeants et résultats trimestriels depuis un fichier Excel.';

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
        'Actif Circulant' => 'actif_circulant',           // 👈 AJOUTÉ
        'Passif Circulant' => 'passif_circulant',         // 👈 AJOUTÉ
        'Chiffre d\'affaires' => 'chiffre_affaires',      // 👈 AJOUTÉ
        'Valeur Ajoutée' => 'valeur_ajoutee',             // 👈 AJOUTÉ
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

        if (!file_exists($filePath)) {
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

        if ($truncate && !$dryRun) {
            DB::table('shareholders')->truncate();
            DB::table('stock_financials')->truncate();
            DB::table('employees')->truncate();
            DB::table('quarterly_results')->truncate();
        }

        foreach ($sheets as $sheetName => $rows) {

            $rows = array_values(array_filter($rows, fn($r) =>
                is_array($r) && !empty(array_filter($r, fn($c) => trim((string)$c) !== ''))
            ));

            if (empty($rows)) {
                $this->warn("⚠️ Feuille vide : $sheetName");
                continue;
            }

            // Symbole = cellule B1
            $symbol = trim((string)($rows[0][1] ?? ''));
            $symbol = preg_replace('/[«»\"\']/', '', $symbol);

            if (!preg_match('/^[A-Z]{3,6}$/', $symbol)) {
                $this->warn("⚠️ Symbole invalide '$symbol'.");
                continue;
            }

            $action = Action::where('symbole', $symbol)->first();
            if (!$action) {
                $this->warn("⚠️ Action '$symbol' non trouvée.");
                continue;
            }

            try {
                if ($dryRun) {
                    $this->processSheet($rows, $action, true);
                } else {
                    DB::transaction(fn() => $this->processSheet($rows, $action, false));
                }

                $this->info("✓ Import réussi pour $symbol");

            } catch (\Exception $e) {
                $msg = "Échec import $symbol : " . $e->getMessage();
                $this->error("✗ $msg");

                if ($logErrors) {
                    Log::channel('daily')->error("[IMPORT] $msg");
                }
            }
        }

        $this->info($dryRun ? "Simulation terminée." : "Import terminé.");
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
        $financialData = $this->extractFinancialData($rows, $years);

        if (!$dryRun) {
            foreach ($financialData as $data) {
                $data['action_id'] = $action->id;
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

        // 5. Résultats Trimestriels
        $this->importQuarterlyResults($rows, $action, $dryRun);
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
                if (strlen($desc) > 20) {
                    if (!$dryRun) {
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
                for ($i = 1; $i < count($r); $i++) {
                    $val = trim((string)$r[$i]);
                    if (is_numeric($val) && strlen($val) === 4) {
                        $years[] = (int)$val;
                    }
                }
                if (empty($years)) {
                    throw new \Exception("Aucune année détectée.");
                }
                return $years;
            }
        }
        throw new \Exception("Ligne 'Indicateurs' introuvable.");
    }

    /**
     * 3. DONNÉES FINANCIÈRES ANNUELLES
     */
    protected function extractFinancialData(array $rows, array $years): array
    {
        $data = [];

        foreach ($years as $year) {
            $data[$year] = ['year' => $year];
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
                $start = $i + 1;
                if (isset($rows[$i+1][1]) && is_string($rows[$i+1][1]) && strtolower(trim($rows[$i+1][1])) === 'pourcentage') {
                    $start = $i + 2;
                }
                break;
            }
        }

        if (!$start) return;

        if (!$dryRun) {
            Shareholder::where('action_id', $action->id)->delete();
        }

        $rank = 1;
        for ($i = $start; $i < count($rows); $i++) {
            $name = trim($rows[$i][0] ?? '');
            if ($name === 'Fonction' || $name === 'Indicateurs' || $name === 'Presentation' || $name === 'Présentation') break;
            if ($name === '' && trim($rows[$i][1] ?? '') === '') break;

            $pctRaw = trim($rows[$i][1] ?? '');

            if ($name !== '' && $pctRaw !== '') {
                if (!$dryRun) {
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
     */
    protected function importEmployees(array $rows, Action $action, bool $dryRun)
    {
        $index = null;

        foreach ($rows as $i => $r) {
            if (trim($r[0] ?? '') === 'Fonction') {
                $index = $i;
                break;
            }
        }

        if ($index === null) return;

        $funcRow = $rows[$index + 1] ?? [];
        $nameRow = $rows[$index + 2] ?? [];

        if (!$dryRun) {
            Employee::where('action_id', $action->id)->delete();
        }

        $maxCols = max(count($funcRow), count($nameRow));

        for ($col = 0; $col < $maxCols; $col++) {
            $func = trim($funcRow[$col] ?? '');
            $name = trim($nameRow[$col] ?? '');

            if ($func === '' || $name === '') continue;

            $mappedFunc = match ($func) {
                'Directeur Marketing/commercial' => 'Directeur Marketing/Commercial',
                default => $func,
            };

            $position = Position::where('nom', $mappedFunc)->first();

            if (!$position) {
                Log::warning("Position inconnue : '$func' (action {$action->symbole}).");
                continue;
            }

            if (!$dryRun) {
                Employee::create([
                    'nom' => $name,
                    'position_id' => $position->id,
                    'action_id' => $action->id,
                ]);
            }
        }
    }

    /**
     * 6. RÉSULTATS TRIMESTRIELS
     */
    protected function importQuarterlyResults(array $rows, Action $action, bool $dryRun)
    {
        $quarterlyStartRow = null;
        $quarterlyHeaders = [];

        foreach ($rows as $i => $row) {
            $firstCell = trim($row[0] ?? '');
            if (in_array($firstCell, ['Chiffre d\'affaires', 'Résultat Net'])) {
                $quarterlyStartRow = $i;
                for ($j = 1; $j < count($row); $j++) {
                    $header = trim($row[$j] ?? '');
                    if (str_starts_with($header, 'Valeur T')) {
                        $trimestre = (int)substr($header, -1);
                        $quarterlyHeaders[$j] = $trimestre;
                    }
                }
                break;
            }
        }

        if (!$quarterlyStartRow || empty($quarterlyHeaders)) {
            $this->warn("⚠️ Pas de données trimestrielles trouvées.");
            return;
        }

        $years = $this->extractYears($rows);

        foreach ($years as $year) {
            $yearColumnIndex = null;
            foreach ($rows as $row) {
                if (trim($row[0] ?? '') === 'Indicateurs') {
                    for ($j = 1; $j < count($row); $j++) {
                        if ((int)trim($row[$j] ?? '') === $year) {
                            $yearColumnIndex = $j;
                            break;
                        }
                    }
                    break;
                }
            }

            if ($yearColumnIndex === null) {
                continue;
            }

            $indicators = [
                'Chiffre d\'affaires' => 'chiffre_affaires',
                'Résultat Net' => 'resultat_net',
            ];

            foreach ($indicators as $indicatorLabel => $dbField) {
                $foundRow = null;
                foreach ($rows as $i => $row) {
                    if (trim($row[0] ?? '') === $indicatorLabel) {
                        $foundRow = $i;
                        break;
                    }
                }

                if (!$foundRow) continue;

                foreach ($quarterlyHeaders as $colIndex => $trimestre) {
                    $valueCell = $rows[$foundRow][$colIndex] ?? null;
                    $evolCell = $rows[$foundRow][$colIndex + 1] ?? null;

                    $value = $this->parseNumeric($valueCell);
                    $evolution = $this->parsePercentage($evolCell);

                    if ($value !== null || $evolution !== 0.0) {
                        if (!$dryRun) {
                            QuarterlyResult::updateOrCreate(
                                [
                                    'action_id' => $action->id,
                                    'year' => $year,
                                    'trimestre' => $trimestre,
                                ],
                                [
                                    'evolution' => $evolution,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Helpers
     */
    protected function parseNumeric($value): ?float
    {
        if ($value === null || $value === '' || in_array((string)$value, ['-', 'ND'], true)) return null;
        $clean = str_replace([' ', "\xc2\xa0", ','], ['', '', '.'], (string)$value);
        return is_numeric($clean) ? (float)$clean : null;
    }

    protected function parsePercentage($value): float
    {
        $clean = str_replace(['%', ' ', "\xc2\xa0", ','], ['', '', '', '.'], (string)$value);
        return is_numeric($clean) ? (float)$clean : 0.0;
    }
}
