<?php

namespace App\Http\Resources;

use App\Models\Action;
use App\Models\SectorFinancialMetric;
use App\Models\StockFinancial;
use App\Models\StockFinancialMetric;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ActionDashboardResource
 *
 * Cette ressource transforme un modèle "Action" pour l'affichage du Tableau de Bord Financier.
 *
 * @package App\Http\Resources
 *
 * Fonctionnalités principales :
 * 1. Filtre les données financières pour une année spécifique ($year).
 * 2. Gère la distinction entre sociétés financières (Banques) et non-financières (Industrie).
 * 3. Compare les indicateurs de l'action avec les moyennes de son secteur (Benchmarking).
 */
class ActionDashboardResource extends JsonResource
{
    /**
     * L'année pour laquelle les données financières doivent être affichées.
     * @var int
     */
    private int $year;

    /**
     * ActionDashboardResource constructor.
     *
     * @param mixed $resource Le modèle Action.
     * @param int $year L'année fiscale concernée.
     */
    public function __construct($resource, int $year)
    {
        parent::__construct($resource);
        $this->year = $year;
    }

    /**
     * Transforme la ressource en tableau JSON.
     *
     * C'est le point d'entrée principal. Il agrège les données de l'action,
     * les états financiers et les métriques comparatives sectorielles.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        /** @var Action $action Typage pour l'autocomplétion IDE */
        $action = $this->resource;

        // 1. Récupération des États Financiers pour l'année demandée
        /** @var StockFinancial|null $financial */
        $financial = $action->financials()->where('year', $this->year)->first();

        // Guard Clause : Si pas de bilan pour cette année, on arrête ici.
        if (!$financial) {
            return [
                'success' => false,
                'message' => 'Aucune donnée financière disponible pour cette année',
                'year' => $this->year
            ];
        }

        // 2. Récupération des Métriques pré-calculées (Ratios, Croissance...)
        $metric = StockFinancialMetric::where('action_id', $action->id)
            ->where('year', $this->year)
            ->first();

        // 3. Récupération des Métriques du Secteur (pour comparaison/benchmark)
        // Comparaison avec le secteur global BRVM (ex: Finance)
        $brvmSectorMetrics = $this->getSectorMetrics('brvm', $action->brvm_sector_id, $this->year);
        // Comparaison avec le secteur classifié plus précis (ex: Banques commerciales)
        $classifiedSectorMetrics = $this->getSectorMetrics('classified', $action->classified_sector_id, $this->year);

        // 4. Construction de la réponse structurée
        return [
            // --- INFO GÉNÉRALES ---
            'action' => [
                'id' => $action->id,
                'key'=> $action->key,
                'nom' => $action->nom,
                'variation' => $action->variation,
                'presentation' => $action->description,
                'symbole' => $action->symbole,
                'brvm_sector' => [
                    'nom' => $action->brvmSector->nom,
                    'slug' => $action->brvmSector->slug,
                ],
                'classified_sector' => [
                    'nom' => $action->classifiedSector->nom,
                    'slug' => $action->classifiedSector->slug,
                ],
            ],
            'year' => $this->year,

            // --- ÉTATS FINANCIERS (Logique Bancaire vs Standard gérée dans les méthodes) ---
            'bilan' => $this->formatBilan($financial, $action->isFinancialService()),
            'compte_resultat' => $this->formatCompteResultat($financial, $action->isFinancialService()),

            // --- DATA BOURSIÈRE (Cours, Dividendes, PER brut) ---
            'indicateurs_boursiers' => $this->formatIndicateursBoursiers($financial),

            // --- RATIOS & ANALYSE (Avec comparaison sectorielle) ---
            'indicators' => [
                'croissance' => $this->formatCroissance($metric, $brvmSectorMetrics, $classifiedSectorMetrics, $action->isFinancialService()),
                'rentabilite' => $this->formatRentabilite($metric, $brvmSectorMetrics, $classifiedSectorMetrics),
                'remuneration' => $this->formatRemuneration($metric, $brvmSectorMetrics, $classifiedSectorMetrics),
                'valorisation' => $this->formatValorisation($metric, $brvmSectorMetrics, $classifiedSectorMetrics),
                'solidite_financiere' => $this->formatSolidite($metric, $brvmSectorMetrics, $classifiedSectorMetrics, $action->isFinancialService()),
            ],

            // --- GOUVERNANCE ---
            'governance' => [
                'actionnariat' => $action->shareholders->map(fn($s) => [
                    'nom' => $s->nom,
                    'pourcentage' => $s?->pourcentage !== null ? (float)$s?->pourcentage . ' %' : null,
                ]),
                'employees' => $action->employees->map(fn($e) => [
                    'id' => $e->id,
                    'nom' => $e->nom,
                    'position' => ['nom' => $e->position?->nom,],
                ]),
            ],

            // --- MÉTA-DONNÉES SECTORIELLES ---
            'sector_comparison' => [
                'brvm_sector' => [
                    'nom' => $action?->brvmSector?->nom,
                    'companies_count' => $brvmSectorMetrics->companies_count ?? 0,
                ],
                'classified_sector' => [
                    'nom' => $action?->classifiedSector?->nom,
                    'companies_count' => $classifiedSectorMetrics->companies_count ?? 0,
                ],
            ],
        ];
    }

    /**
     * Formate le Bilan selon le type d'entreprise.
     *
     * Ajoute les champs spécifiques "Crédits/Dépôts clientèle" pour les banques.
     *
     * @param StockFinancial $financial
     * @param bool $isFinancial True si c'est une banque/assurance.
     * @return array
     */
    private function formatBilan(StockFinancial $financial, bool $isFinancial): array
    {
        $data = [
            'total_immobilisation' => [
                'value' => $financial?->total_immobilisation !== null ? (float) $financial->total_immobilisation : null,
                'label' => 'Total Immobilisation'
            ],
            'actif_circulant' => [
                'value' => $financial?->actif_circulant !== null ? (float) $financial->actif_circulant : null,
                'label' => 'Actif Circulant'
            ],
            'total_actif' => [
                'value' => $financial?->total_actif !== null ? (float) $financial->total_actif : null,
                'label' => 'Total Actif'
            ],
            'capitaux_propres' => [
                'value' => $financial?->capitaux_propres !== null ? (float) $financial->capitaux_propres : null,
                'label' => 'Capitaux propres'
            ],
            'passif_circulant' => [
                'value'  => $financial?->passif_circulant !== null ?  (float) $financial->passif_circulant : null,
                 'label' => 'Passif Circulant'
                ],
            'dette_totale' => [
                'value' => $financial?->dette_totale !== null ? (float) $financial->dette_totale : null,
                'label' => 'Dette totale'
            ],
        ];

        // Champs spécifiques aux banques
        if ($isFinancial) {
            $data['credits_clientele'] = [
                'value' => $financial?->credits_clientele !== null ? (float) $financial->credits_clientele : null ,
                'label' => 'Crédits à la clientèle'
            ];
            $data['depots_clientele'] = [
                'value' => $financial?->depots_clientele !== null ? (float) $financial->depots_clientele : null,
                 'label' => 'Dépôts de la clientèle'
            ];
        }

        return $data;
    }

    /**
     * Formate le Compte de Résultat.
     *
     * Affiche "PNB" pour les banques vs "Chiffre d'Affaires" pour les autres.
     */
    private function formatCompteResultat(StockFinancial $financial, bool $isFinancial): array
    {
        $data = [];

        if ($isFinancial) {
            $data['produit_net_bancaire'] = [
                'value' => $financial?->produit_net_bancaire !== null ? (float) $financial->produit_net_bancaire : null,
                'label' => 'Produit Net Bancaire'
            ];
        } else {
            $data['chiffre_affaires'] = [
                'value' => $financial?->chiffre_affaires !== null ? (float) $financial->chiffre_affaires : null,
                'label' => "Chiffre d'Affaires"
            ];
        }

        // Indicateurs communs
        $data['valeur_ajoutee'] = [
            'value' => $financial?->valeur_ajoutee !== null ? (float) $financial->valeur_ajoutee : null,
            'label' => 'Valeur Ajoutée'
        ];
        $data['ebitda'] = [
            'value' => $financial?->ebitda !== null ?  (float) $financial->ebitda : null,
            'label' => 'EBITDA'
        ];
        $data['ebit'] = [
            'value' => $financial?->ebit !== null ?  (float) $financial->ebit : null,
            'label' => 'EBIT'
        ];
        $data['resultat_avant_impot'] = [
            'value' => $financial?->resultat_avant_impot !== null ? (float) $financial->resultat_avant_impot : null,
            'label' => 'Résultat avant Impôt'
        ];
        $data['resultat_net'] = [
            'value' => $financial?->resultat_net !== null ? (float) $financial->resultat_net : null,
            'label' => 'Résultat Net'
        ];

        return $data;
    }

    /**
     * Formate les indicateurs boursiers de base (Cours, Dividendes).
     */
    private function formatIndicateursBoursiers(StockFinancial $financial): array
    {
        return [
            'nombre_titres' => [
                'value' => $financial?->nombre_titre !== null ? (float) $financial?->nombre_titre : null,
                'label' => 'Nombre de titres'
            ],
            'cours_31_12' => [
                'value' => $financial?->cours_31_12 !== null ? (float) $financial->cours_31_12 : null,
                'label' => 'Cours au 31/12'
            ],
            'dnpa' => [
                'value' => $financial?->dnpa !== null ? (float) $financial->dnpa : null,
                'label' => 'DNPA'
            ],
            'dividendes_total' => [
                'value' => $financial?->dividendes_bruts !== null ? (float) $financial->dividendes_bruts : null,
                'label' => 'Dividendes total'
            ],
            'per' => [
                'value' => $financial?->per !== null ? (float) $financial->per : null,
                'label' => 'PER'
            ],
            'capex' => [
                'value' => $financial?->capex !== null ? (float) $financial->capex : null,
                'label' => 'CAPEX'
            ],
        ];
    }

    /**
     * Calcule et formate les ratios de croissance.
     * Utilise des clés différentes (suffixes _sf vs _as) selon le secteur.
     */
    private function formatCroissance($metric, $brvmMetrics, $classifiedMetrics, bool $isFinancial): array
    {
        if (!$metric) return [];

        if ($isFinancial) {
            // Suffixe _sf = Services Financiers
            return [
                'pnb' => $this->formatIndicator('croissance_pnb', $metric, $brvmMetrics, $classifiedMetrics),
                'ebit' => $this->formatIndicator('croissance_ebit_sf', $metric, $brvmMetrics, $classifiedMetrics),
                'ebitda' => $this->formatIndicator('croissance_ebitda_sf', $metric, $brvmMetrics, $classifiedMetrics),
                'resultat_net' => $this->formatIndicator('croissance_rn_sf', $metric, $brvmMetrics, $classifiedMetrics),
                'capex' => $this->formatIndicator('croissance_capex_sf', $metric, $brvmMetrics, $classifiedMetrics),
                'moy_croissance' => $this->formatIndicator('moy_croissance_sf', $metric, $brvmMetrics, $classifiedMetrics),
            ];
        } else {
            // Suffixe _as = Autres Secteurs
            return [
                'chiffre_affaires' => $this->formatIndicator('croissance_ca', $metric, $brvmMetrics, $classifiedMetrics),
                'ebit' => $this->formatIndicator('croissance_ebit_as', $metric, $brvmMetrics, $classifiedMetrics),
                'ebitda' => $this->formatIndicator('croissance_ebitda_as', $metric, $brvmMetrics, $classifiedMetrics),
                'resultat_net' => $this->formatIndicator('croissance_rn_as', $metric, $brvmMetrics, $classifiedMetrics),
                'capex' => $this->formatIndicator('croissance_capex_as', $metric, $brvmMetrics, $classifiedMetrics),
                'moy_croissance' => $this->formatIndicator('moy_croissance_as', $metric, $brvmMetrics, $classifiedMetrics),
            ];
        }
    }

    /**
     * Formate les ratios de rentabilité (ROE, ROA, Marges).
     */
    private function formatRentabilite($metric, $brvmMetrics, $classifiedMetrics): array
    {
        if (!$metric) return [];

        return [
            'marge_nette' => $this->formatIndicator('marge_nette', $metric, $brvmMetrics, $classifiedMetrics),
            'marge_ebitda' => $this->formatIndicator('marge_ebitda', $metric, $brvmMetrics, $classifiedMetrics),
            'marge_operationnelle' => $this->formatIndicator('marge_operationnelle', $metric, $brvmMetrics, $classifiedMetrics),
            'roe' => $this->formatIndicator('roe', $metric, $brvmMetrics, $classifiedMetrics),
            'roa' => $this->formatIndicator('roa', $metric, $brvmMetrics, $classifiedMetrics),
            'moy_rentabilite' => $this->formatIndicator('moy_rentabilite', $metric, $brvmMetrics, $classifiedMetrics),
        ];
    }

    /**
     * Formate les ratios de rémunération actionnaire (Rendement, Pay-out ratio).
     */
    private function formatRemuneration($metric, $brvmMetrics, $classifiedMetrics): array
    {
        if (!$metric) return [];

        return [
            'dnpa' => $this->formatIndicator('dnpa_calculated', $metric, $brvmMetrics, $classifiedMetrics),
            'rendement_dividendes' => $this->formatIndicator('rendement_dividendes', $metric, $brvmMetrics, $classifiedMetrics),
            'taux_distribution' => $this->formatIndicator('taux_distribution', $metric, $brvmMetrics, $classifiedMetrics),
            'moy_remuneration' => $this->formatIndicator('moy_remuneration', $metric, $brvmMetrics, $classifiedMetrics),
        ];
    }

    /**
     * Formate les ratios de valorisation boursière (PER, PBR).
     */
    private function formatValorisation($metric, $brvmMetrics, $classifiedMetrics): array
    {
        if (!$metric) return [];

        return [
            'per' => $this->formatIndicator('per', $metric, $brvmMetrics, $classifiedMetrics),
            'pbr' => $this->formatIndicator('pbr', $metric, $brvmMetrics, $classifiedMetrics),
            'ratio_ps' => $this->formatIndicator('ratio_ps', $metric, $brvmMetrics, $classifiedMetrics),
            'ev_ebitda' => $this->formatIndicator('ev_ebitda', $metric, $brvmMetrics, $classifiedMetrics),
            'cours_cible' => $this->formatIndicator('cours_cible', $metric, $brvmMetrics, $classifiedMetrics, false),
            'moy_valorisation' => $this->formatIndicator('moy_valorisation', $metric, $brvmMetrics, $classifiedMetrics),
        ];
    }

    /**
     * Formate les indicateurs de solidité financière (Dette, Autonomie).
     * Différencie également Banques vs Autres (Dette/Capitaux vs Prêts/Dépôts).
     */
    private function formatSolidite($metric, $brvmMetrics, $classifiedMetrics, bool $isFinancial): array
    {
        if (!$metric) return [];

        if ($isFinancial) {
            return [
                'autonomie_financiere' => $this->formatIndicator('autonomie_financiere', $metric, $brvmMetrics, $classifiedMetrics),
                'ratio_prets_depots' => $this->formatIndicator('ratio_prets_depots', $metric, $brvmMetrics, $classifiedMetrics),
                'loan_to_deposit' => $this->formatIndicator('loan_to_deposit', $metric, $brvmMetrics, $classifiedMetrics),
                'endettement_general' => $this->formatIndicator('endettement_general_sf', $metric, $brvmMetrics, $classifiedMetrics),
                'cout_du_risque' => $this->formatIndicator('cout_du_risque_value', $metric, $brvmMetrics, $classifiedMetrics),
                'moy_solidite_financiere' => $this->formatIndicator('moy_solidite_sf', $metric, $brvmMetrics, $classifiedMetrics),
            ];
        } else {
            return [
                'dette_capitalisation' => $this->formatIndicator('dette_capitalisation', $metric, $brvmMetrics, $classifiedMetrics),
                'endettement_actif' => $this->formatIndicator('endettement_actif', $metric, $brvmMetrics, $classifiedMetrics),
                'endettement_general' => $this->formatIndicator('endettement_general_as', $metric, $brvmMetrics, $classifiedMetrics),
                'moy_solidite_financiere' => $this->formatIndicator('moy_solidite_as', $metric, $brvmMetrics, $classifiedMetrics),
            ];
        }
    }

    /**
     * Méthode Helper générique pour formater un indicateur.
     *
     * Retourne la valeur de l'entreprise + les moyennes sectorielles.
     *
     * @param string $field Le nom de la colonne dans la base de données.
     * @param mixed $metric L'objet métrique de l'entreprise.
     * @param mixed $brvmMetrics L'objet métrique du secteur BRVM.
     * @param mixed $classifiedMetrics L'objet métrique du secteur classifié.
     * @param bool $includeSector Faut-il inclure les stats sectorielles ? (Défaut: true).
     *
     * @return array Structure : { value, sector_brvm_moy, sector_brvm_ecart_type, ... }
     */
    private function formatIndicator(string $field, $metric, $brvmMetrics, $classifiedMetrics, bool $includeSector = true): array
    {
        // On récupère la donnée brute de l'action
        $data = [
            'value' => $metric?->$field !== null ? (float) $metric->$field : null,
        ];

        // On ajoute les données de benchmark si demandé
        if ($includeSector) {
            // Moyenne et Écart-type BRVM
            $data['sector_brvm_moy'] = $brvmMetrics?->{$field . '_moy'} !== null ? (float)$brvmMetrics->{$field . '_moy'} : null;
            $data['sector_brvm_ecart_type'] = $brvmMetrics?->{$field . '_ecart_type'} !== null ? (float)$brvmMetrics->{$field . '_ecart_type'} : null;

            // Moyenne et Écart-type Secteur Classifié
            $data['sector_classified_moy'] = $classifiedMetrics?->{$field . '_moy'} !== null ? (float) $classifiedMetrics->{$field . '_moy'} : null;
            $data['sector_classified_ecart_type'] = $classifiedMetrics?->{$field . '_ecart_type'} !== null ? (float) $classifiedMetrics->{$field . '_ecart_type'} :null;
        }

        return $data;
    }

    /**
     * Récupère les métriques moyennes d'un secteur pour une année donnée.
     *
     * @param string $type Type de secteur ('brvm' ou 'classified').
     * @param int $sectorId L'ID du secteur.
     * @param int $year L'année.
     * @return SectorFinancialMetric|null
     */
    private function getSectorMetrics(string $type, int $sectorId, int $year): ?SectorFinancialMetric
    {
        return SectorFinancialMetric::where('sector_type', $type)
            ->where('sector_id', $sectorId)
            ->where('year', $year)
            ->first();
    }
}
