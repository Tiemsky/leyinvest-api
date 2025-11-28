<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuration des Indicateurs Financiers
    |--------------------------------------------------------------------------
    |
    | Configuration des pondérations par horizon d'investissement et par secteur
    | pour le calcul des scores globaux des indicateurs financiers.
    |
    */

    /**
     * Horizons d'investissement disponibles
     */
    'horizons' => [
        'court_terme',
        'moyen_terme',
        'long_terme',
    ],

    /**
     * Horizon par défaut
     */
    'default_horizon' => 'court_terme',

    /**
     * Pondérations des indicateurs par secteur et horizon
     *
     * Structure:
     * - services_financiers: Formules spécifiques SF
     * - autres_secteurs: Formules standards
     */
    'ponderations' => [

        /**
         * SERVICES FINANCIERS
         * Utilise PNB au lieu de CA
         * Solidité financière spécifique (5 indicateurs)
         */
        'services_financiers' => [

            // CROISSANCE
            'croissance' => [
                'croissance_pnb' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'croissance_rn' => [
                    'court_terme' => 3,
                    'moyen_terme' => 2,
                    'long_terme' => 1,
                ],
                'croissance_ebit' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'croissance_ebitda' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'croissance_capex' => [
                    'court_terme' => 1,
                    'moyen_terme' => 2,
                    'long_terme' => 3,
                ],
            ],

            // RENTABILITÉ
            'rentabilite' => [
                'marge_nette' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'marge_ebitda' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'marge_operationnelle' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'roe' => [
                    'court_terme' => 3,
                    'moyen_terme' => 3,
                    'long_terme' => 3,
                ],
                'roa' => [
                    'court_terme' => 1,
                    'moyen_terme' => 1,
                    'long_terme' => 1,
                ],
            ],

            // RÉMUNÉRATION
            'remuneration' => [
                'dnpa' => [
                    'court_terme' => 1,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'rendement_dividende' => [
                    'court_terme' => 3,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'taux_distribution' => [
                    'court_terme' => 1,
                    'moyen_terme' => 1,
                    'long_terme' => 1,
                ],
            ],

            // VALORISATION
            'valorisation' => [
                'per' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'pbr' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'ratio_ps' => [
                    'court_terme' => 1,
                    'moyen_terme' => 1,
                    'long_terme' => 1,
                ],
                'ev_ebitda' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'cours_cible' => [
                    'court_terme' => 3,
                    'moyen_terme' => 3,
                    'long_terme' => 3,
                ],
            ],

            // SOLIDITÉ FINANCIÈRE (Services Financiers)
            'solidite_financiere' => [
                'ratio_autonomie_financiere' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 3,
                ],
                'ratio_prets_depots_capitaux' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'loan_to_deposit' => [
                    'court_terme' => 3,
                    'moyen_terme' => 3,
                    'long_terme' => 3,
                ],
                'ratio_endettement_general' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'cout_du_risque' => [
                    'court_terme' => 1,
                    'moyen_terme' => 1,
                    'long_terme' => 1,
                ],
            ],
        ],

        /**
         * AUTRES SECTEURS
         * Utilise CA (Chiffre d'Affaires)
         * Solidité financière standard (3 indicateurs)
         */
        'autres_secteurs' => [

            // CROISSANCE
            'croissance' => [
                'croissance_ca' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'croissance_rn' => [
                    'court_terme' => 3,
                    'moyen_terme' => 2,
                    'long_terme' => 1,
                ],
                'croissance_ebit' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'croissance_ebitda' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'croissance_capex' => [
                    'court_terme' => 1,
                    'moyen_terme' => 2,
                    'long_terme' => 3,
                ],
            ],

            // RENTABILITÉ (identique SF)
            'rentabilite' => [
                'marge_nette' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'marge_ebitda' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'marge_operationnelle' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'roe' => [
                    'court_terme' => 3,
                    'moyen_terme' => 3,
                    'long_terme' => 3,
                ],
                'roa' => [
                    'court_terme' => 1,
                    'moyen_terme' => 1,
                    'long_terme' => 1,
                ],
            ],

            // RÉMUNÉRATION (identique SF)
            'remuneration' => [
                'dnpa' => [
                    'court_terme' => 1,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'rendement_dividende' => [
                    'court_terme' => 3,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'taux_distribution' => [
                    'court_terme' => 1,
                    'moyen_terme' => 1,
                    'long_terme' => 1,
                ],
            ],

            // VALORISATION (identique SF)
            'valorisation' => [
                'per' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'pbr' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'ratio_ps' => [
                    'court_terme' => 1,
                    'moyen_terme' => 1,
                    'long_terme' => 1,
                ],
                'ev_ebitda' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'cours_cible' => [
                    'court_terme' => 3,
                    'moyen_terme' => 3,
                    'long_terme' => 3,
                ],
            ],

            // SOLIDITÉ FINANCIÈRE (Autres secteurs)
            'solidite_financiere' => [
                'dette_sur_capitalisation' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 3,
                ],
                'endettement_sur_actif' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
                'endettement_general' => [
                    'court_terme' => 2,
                    'moyen_terme' => 2,
                    'long_terme' => 2,
                ],
            ],
        ],
    ],

    /**
     * Configuration du cache
     */
    'cache' => [
        // TTL en secondes (1 heure)
        'ttl' => 3600,

        // Préfixe des clés de cache
        'prefix' => 'financial',

        // Tags pour invalidation groupée
        'tags' => [
            'indicators',
            'benchmarks',
            'dashboard',
            'historical',
        ],
    ],

    /**
     * Configuration des benchmarks
     */
    'benchmarks' => [
        // Nombre minimum d'actions pour calculer un benchmark
        'min_actions' => 3,

        // Recalcul automatique après modification StockFinancial
        'auto_recalculate' => true,
    ],

    /**
     * Années de données disponibles
     */
    'available_years' => [
        'start' => 2021,
        'end' => 2024,
    ],
];
