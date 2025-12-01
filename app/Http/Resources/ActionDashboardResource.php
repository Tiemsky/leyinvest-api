<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource pour le dashboard d'une action
 * Inclut : présentation, indicateurs, résultats trimestriels, analyse complète
 */
class ActionDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Informations de base
            'action' => [
                'id' => $this->id,
                'key' => $this->key,
                'symbole' => $this->symbole,
                'nom' => $this->nom,
                'description' => $this->description,
            ],

            // Cotation en temps réel
            'cotation' => [
                'volume' => (int) $this->volume,
                'cours_veille' => (float) $this->cours_veille,
                'cours_ouverture' => (float) $this->cours_ouverture,
                'cours_cloture' => (float) $this->cours_cloture,
                'variation' => (float) $this->variation,
                'variation_formatted' => $this->formatVariation($this->variation),
            ],

            // Présentation
            'presentation' => [
                'activite' => $this->description,
                'secteur_brvm' => [
                    'nom' => optional($this->brvmSector)->nom,
                    'slug' => optional($this->brvmSector)->slug,
                ],
                'secteur_reclassifie' => [
                    'nom' => optional($this->classifiedSector)->nom,
                    'slug' => optional($this->classifiedSector)->slug,
                ],
            ],

            // Direction / Employés
            'direction' => $this->whenLoaded('employees', function () {
                return $this->employees->map(function ($employee) {
                    return [
                        'fonction' => $employee->position ? strtoupper($employee->position->nom) : '',
                        'nom_prenoms' => $employee->nom,
                    ];
                })->values()->toArray();
            }),

            // Actionnariat
            'actionnariat' => $this->whenLoaded('shareholders', function () {
                return $this->shareholders->map(function ($shareholder) {
                    return [
                        'nom' => $shareholder->nom,
                        'pourcentage' => (float) $shareholder->percentage,
                        'rang' => (int) $shareholder->rang,
                    ];
                })->sortBy('rang')->values()->toArray();
            }),

            // Bilan (dernière année disponible)
            'bilan' => $this->getLatestBilan(),

            // Compte de résultat (dernière année disponible)
            'compte_resultat' => $this->getLatestCompteResultat(),

            // Indicateurs boursiers
            'indicateurs_boursiers' => $this->getIndicateursBoursiers(),

            // Résultats trimestriels (année en cours)
            'resultats_trimestriels' => $this->getResultatsTrimestriels(),

            // Analyse financière (intégration avec le système d'indicateurs)
            'analyse' => $this->when(
                $request->input('include_analysis', false),
                function () {
                    return [
                        'croissance' => null, // À charger via IndicatorOrchestrator
                        'rentabilite' => null,
                        'remuneration' => null,
                        'valorisation' => null,
                        'solidite_financiere' => null,
                    ];
                }
            ),
        ];
    }

    /**
     * Récupère le bilan de la dernière année
     */
    private function getLatestBilan(): ?array
    {
        $latestFinancial = $this->whenLoaded('financials', function () {
            return $this->financials->sortByDesc('year')->first();
        });

        if (!$latestFinancial) {
            return null;
        }

        return [
            'annee' => $latestFinancial->year,
            'total_immobilisation' => $this->formatMontant($latestFinancial->total_immobilisation),
            'credits_clientele' => $this->formatMontant($latestFinancial->credits_clientele),
            'depots_clientele' => $this->formatMontant($latestFinancial->depots_clientele),
            'total_actif' => $this->formatMontant($latestFinancial->total_actif),
            'dette_totale' => $this->formatMontant($latestFinancial->dette_totale),
            'capitaux_propres' => $this->formatMontant($latestFinancial->capitaux_propres),
            // Valeurs brutes pour calculs
            'raw' => [
                'total_immobilisation' => (float) $latestFinancial->total_immobilisation,
                'credits_clientele' => (float) $latestFinancial->credits_clientele,
                'depots_clientele' => (float) $latestFinancial->depots_clientele,
                'total_actif' => (float) $latestFinancial->total_actif,
                'dette_totale' => (float) $latestFinancial->dette_totale,
                'capitaux_propres' => (float) $latestFinancial->capitaux_propres,
            ],
        ];
    }

    /**
     * Récupère le compte de résultat de la dernière année
     */
    private function getLatestCompteResultat(): ?array
    {
        $latestFinancial = $this->whenLoaded('financials', function () {
            return $this->financials->sortByDesc('year')->first();
        });

        if (!$latestFinancial) {
            return null;
        }

        return [
            'annee' => $latestFinancial->year,
            'chiffre_affaires' => $this->formatMontant($latestFinancial->produit_net_bancaire),
            'ebitda' => $this->formatMontant($latestFinancial->ebitda),
            'ebit' => $this->formatMontant($latestFinancial->ebit),
            'resultat_avant_impot' => $this->formatMontant($latestFinancial->resultat_avant_impot),
            'resultat_net' => $this->formatMontant($latestFinancial->resultat_net),
            'valeur_ajoutee' => $this->formatMontant($latestFinancial->ebitda), // Ou autre calcul si disponible
            'resultat_exploitation' => $this->formatMontant($latestFinancial->ebit),
            // Valeurs brutes
            'raw' => [
                'produit_net_bancaire' => (float) $latestFinancial->produit_net_bancaire,
                'ebitda' => (float) $latestFinancial->ebitda,
                'ebit' => (float) $latestFinancial->ebit,
                'resultat_avant_impot' => (float) $latestFinancial->resultat_avant_impot,
                'resultat_net' => (float) $latestFinancial->resultat_net,
            ],
        ];
    }

    /**
     * Calcule les indicateurs boursiers
     */
    private function getIndicateursBoursiers(): ?array
    {
        $latestFinancial = $this->whenLoaded('financials', function () {
            return $this->financials->sortByDesc('year')->first();
        });

        if (!$latestFinancial) {
            return null;
        }

        $cours = (float) $this->cours_cloture;
        $dnpa = (float) $latestFinancial->dnpa;
        $per = (float) $latestFinancial->per;

        // Calcul BNPA = Cours / PER
        $bnpa = $per > 0 ? $cours / $per : 0;

        // Calcul Rendement actuel = DNPA / Cours
        $rendementActuel = $cours > 0 ? ($dnpa / $cours) * 100 : 0;

        return [
            'rendement_actuel' => [
                'valeur' => number_format($rendementActuel, 2) . '%',
                'raw' => round($rendementActuel, 2),
            ],
            'cours_cible' => [
                'valeur' => 'En attente', // À implémenter avec ta logique
                'raw' => null,
            ],
            'dividendes_total' => [
                'valeur' => $this->formatMontant($latestFinancial->dividendes_bruts),
                'raw' => (float) $latestFinancial->dividendes_bruts,
            ],
            'dnpa' => [
                'valeur' => number_format($dnpa, 2) . ' FCFA',
                'raw' => $dnpa,
            ],
            'bnpa' => [
                'valeur' => number_format($bnpa, 2) . ' FCFA',
                'raw' => round($bnpa, 2),
            ],
            'nombre_titres' => [
                'valeur' => number_format($latestFinancial->nombre_titre, 0, ',', ' '),
                'raw' => (int) $latestFinancial->nombre_titre,
            ],
        ];
    }

    /**
     * Récupère et calcule les résultats trimestriels
     *
     * NOTE: Cette méthode suppose que tu as une table `quarterly_results` ou équivalent
     * Si non, il faudra l'adapter selon ta structure de données
     */
    private function getResultatsTrimestriels(): array
    {
        // Si tu as une relation `quarterlyResults` sur le model Action
        if ($this->relationLoaded('quarterlyResults')) {
            $currentYear = now()->year;
            $quarters = $this->quarterlyResults()
                ->where('year', $currentYear)
                ->orderBy('trimestre')
                ->get();

            $trimestres = [];
            foreach ([1, 2, 3, 4] as $t) {
                $quarter = $quarters->firstWhere('trimestre', $t);

                if ($quarter) {
                    $trimestres["t{$t}"] = [
                        'produit_net_bancaire' => [
                            'valeur' => $this->formatMontant($quarter->produit_net_bancaire),
                            'evolution' => $this->calculateQuarterEvolution(
                                $quarter->produit_net_bancaire,
                                $currentYear - 1,
                                $t,
                                'produit_net_bancaire'
                            ),
                            'raw' => (float) $quarter->produit_net_bancaire,
                        ],
                        'resultat_net' => [
                            'valeur' => $this->formatMontant($quarter->resultat_net),
                            'evolution' => $this->calculateQuarterEvolution(
                                $quarter->resultat_net,
                                $currentYear - 1,
                                $t,
                                'resultat_net'
                            ),
                            'raw' => (float) $quarter->resultat_net,
                        ],
                    ];
                } else {
                    $trimestres["t{$t}"] = null; // Données non disponibles
                }
            }

            return [
                'annee' => $currentYear,
                'trimestres' => $trimestres,
            ];
        }

        // Si pas de relation quarterlyResults, retourner structure vide
        // Tu peux calculer à partir des données annuelles si nécessaire
        return [
            'annee' => now()->year,
            'trimestres' => [
                't1' => null,
                't2' => null,
                't3' => null,
                't4' => null,
            ],
            'note' => 'Résultats trimestriels non disponibles',
        ];
    }

    /**
     * Calcule l'évolution d'un trimestre par rapport à l'année précédente
     */
    private function calculateQuarterEvolution(float $currentValue, int $previousYear, int $trimestre, string $field): ?string
    {
        // Récupérer le résultat du même trimestre de l'année précédente
        if ($this->relationLoaded('quarterlyResults')) {
            $previousQuarter = $this->quarterlyResults()
                ->where('year', $previousYear)
                ->where('trimestre', $trimestre)
                ->first();

            if ($previousQuarter && $previousQuarter->$field > 0) {
                $evolution = (($currentValue - $previousQuarter->$field) / $previousQuarter->$field) * 100;
                $sign = $evolution >= 0 ? '+' : '';
                return $sign . number_format($evolution, 1) . '%';
            }
        }

        return null;
    }

    /**
     * Formate un montant en millions
     */
    private function formatMontant(?float $montant): string
    {
        if (is_null($montant)) {
            return '-';
        }

        // Si le montant est déjà en millions
        if ($montant < 1000) {
            return number_format($montant, 1, ',', ' ') . ' M';
        }

        // Si en milliers, convertir en millions
        return number_format($montant / 1000, 1, ',', ' ') . ' Mds';
    }

    /**
     * Formate la variation avec symbole
     */
    private function formatVariation(float $variation): string
    {
        $sign = $variation >= 0 ? '+' : '';
        return $sign . number_format($variation, 2) . '%';
    }
}
