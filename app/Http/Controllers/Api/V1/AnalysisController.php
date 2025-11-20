<?php
/**
 * ============================================================================
 * CONTRÔLEUR API: Analyse Financière
 * ============================================================================
 */
namespace App\Http\Controllers\Api\V1;

use App\Models\Action;
use App\Models\ActionRatio;
use App\Models\SectorAverage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AnalysisController extends Controller
{
    /**
     * Tableau d'analyse complet pour une action
     * GET /api/analysis/{code}?year=2024
     */
    public function getStockAnalysis(string $code, Request $request): JsonResponse
    {
        $year = $request->input('year', date('Y'));

        $action = Action::where('code', $code)->firstOrFail();

        // Récupère les données financières et ratios pour les 5 dernières années
        $years = range($year, $year - 4);

        $analysis = [
            'action' => [
                'code' => $action->code,
                'name' => $action->name,
                'sector' => $action->sector,
            ],
            'years' => [],
            'moyennes' => [],
            'ecarts_types' => [],
        ];

        foreach ($years as $y) {
            $financial = $action->financials()->where('year', $y)->first();
            $ratios = $action->ratios()->where('year', $y)->first();

            if (!$financial || !$ratios) continue;

            $analysis['years'][$y] = [
                // Section Croissance
                'croissance' => [
                    'pnb' => $ratios->pnb_growth,
                    'rn' => $ratios->rn_growth,
                    'ebit' => $ratios->ebit_growth,
                    'ebitda' => $ratios->ebitda_growth,
                    'capex' => $ratios->capex_growth,
                    'moyenne' => $ratios->avg_growth_3y,
                ],

                // Section Rentabilité
                'rentabilite' => [
                    'marge_nette' => $ratios->net_margin,
                    'marge_ebitda' => $ratios->ebitda_margin,
                    'marge_operationnelle' => $ratios->operating_margin,
                    'roe' => $ratios->roe,
                    'roa' => $ratios->roa,
                    'moyenne' => $ratios->avg_profitability,
                ],

                // Section Rémunération
                'remuneration' => [
                    'dnpa' => $ratios->dnpa,
                    'rendement_dividende' => $ratios->dividend_yield,
                    'taux_distribution' => $ratios->payout_ratio,
                    'moyenne' => $ratios->avg_dividend_yield,
                ],

                // Données brutes pour référence
                'donnees_brutes' => [
                    'pnb' => $financial->produit_net_bancaire,
                    'rn' => $financial->resultat_net,
                    'ebit' => $financial->ebit,
                    'ebitda' => $financial->ebitda,
                    'cours' => $financial->cours_31_12,
                    'capitalisation' => $ratios->market_cap,
                ]
            ];
        }

        // Calcule les moyennes sur 5 ans
        $analysis['moyennes'] = $this->calculateAverages($analysis['years']);

        // Calcule les écarts-types pour mesurer la volatilité
        $analysis['ecarts_types'] = $this->calculateStandardDeviations($analysis['years']);

        return response()->json([
            'success' => true,
            'data' => $analysis
        ]);
    }

    /**
     * Comparaison sectorielle
     * GET /api/analysis/sector/{sector}?year=2024
     */
    public function getSectorAnalysis(string $sector, Request $request): JsonResponse
    {
        $year = $request->input('year', date('Y'));

        // Moyennes du secteur
        $sectorAvg = SectorAverage::where('sector', $sector)
            ->where('year', $year)
            ->first();

        // Toutes les actions du secteur
        $actions = Action::where('sector', $sector)
            ->where('is_active', true)
            ->with(['ratios' => fn($q) => $q->where('year', $year)])
            ->get();

        $actionsData = $actions->map(function($action) use ($year) {
            $ratio = $action->ratios->first();

            return [
                'code' => $action->code,
                'name' => $action->name,
                'roe' => $ratio?->roe,
                'roa' => $ratio?->roa,
                'net_margin' => $ratio?->net_margin,
                'debt_ratio' => $ratio?->debt_ratio,
                'dividend_yield' => $ratio?->dividend_yield,
                'per' => $ratio?->per,
                'market_cap' => $ratio?->market_cap,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'sector' => $sector,
                'year' => $year,
                'averages' => $sectorAvg ? [
                    'roe' => $sectorAvg->avg_roe,
                    'roa' => $sectorAvg->avg_roa,
                    'net_margin' => $sectorAvg->avg_net_margin,
                    'debt_ratio' => $sectorAvg->avg_debt_ratio,
                    'dividend_yield' => $sectorAvg->avg_dividend_yield,
                    'per' => $sectorAvg->avg_per,
                ] : null,
                'stocks' => $actionsData,
                'stocks_count' => $actions->count(),
            ]
        ]);
    }

    /**
     * Classement des actions par critère
     * GET /api/analysis/ranking?criteria=roe&year=2024&limit=10
     */
    public function getRanking(Request $request): JsonResponse
    {
        $criteria = $request->input('criteria', 'roe');
        $year = $request->input('year', date('Y'));
        $limit = $request->input('limit', 10);
        $order = $request->input('order', 'desc'); // desc ou asc

        $validCriteria = [
            'roe', 'roa', 'net_margin', 'ebitda_margin', 'operating_margin',
            'dividend_yield', 'market_cap', 'pnb_growth', 'rn_growth',
            'debt_ratio', 'per', 'price_to_book'
        ];

        if (!in_array($criteria, $validCriteria)) {
            return response()->json([
                'success' => false,
                'message' => 'Critère invalide',
                'valid_criteria' => $validCriteria
            ], 400);
        }

        $ranking = StockRatio::with('stock')
            ->where('year', $year)
            ->whereNotNull($criteria)
            ->orderBy($criteria, $order)
            ->limit($limit)
            ->get()
            ->map(function($ratio, $index) use ($criteria) {
                return [
                    'rank' => $index + 1,
                    'code' => $ratio->stock->code,
                    'name' => $ratio->stock->name,
                    'sector' => $ratio->stock->sector,
                    'value' => $ratio->$criteria,
                    'additional_metrics' => [
                        'roe' => $ratio->roe,
                        'market_cap' => $ratio->market_cap,
                        'dividend_yield' => $ratio->dividend_yield,
                    ]
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'criteria' => $criteria,
                'year' => $year,
                'order' => $order,
                'ranking' => $ranking,
            ]
        ]);
    }

    /**
     * Analyse comparative de plusieurs actions
     * GET /api/analysis/compare?codes=SAFCA,BOAM,SGCI&year=2024
     */
    public function compareStocks(Request $request): JsonResponse
    {
        $codes = explode(',', $request->input('codes', ''));
        $year = $request->input('year', date('Y'));

        if (count($codes) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Au moins 2 actions requises pour la comparaison'
            ], 400);
        }

        $comparison = Action::whereIn('code', $codes)
            ->with([
                'ratios' => fn($q) => $q->where('year', $year),
                'financials' => fn($q) => $q->where('year', $year)
            ])
            ->get()
            ->map(function($action) use ($year) {
                $ratio = $action->ratios->first();
                $financial = $action->financials->first();

                return [
                    'code' => $action->code,
                    'name' => $action->name,
                    'sector' => $action->sector,

                    'croissance' => [
                        'pnb' => $ratio?->pnb_growth,
                        'rn' => $ratio?->rn_growth,
                        'ebitda' => $ratio?->ebitda_growth,
                        'moyenne_3ans' => $ratio?->avg_growth_3y,
                    ],

                    'rentabilite' => [
                        'roe' => $ratio?->roe,
                        'roa' => $ratio?->roa,
                        'marge_nette' => $ratio?->net_margin,
                        'marge_ebitda' => $ratio?->ebitda_margin,
                    ],

                    'remuneration' => [
                        'dnpa' => $ratio?->dnpa,
                        'rendement' => $ratio?->dividend_yield,
                        'taux_distribution' => $ratio?->payout_ratio,
                    ],

                    'valorisation' => [
                        'capitalisation' => $ratio?->market_cap,
                        'per' => $ratio?->per,
                        'price_to_book' => $ratio?->price_to_book,
                        'cours' => $financial?->cours_31_12,
                    ],

                    'structure' => [
                        'endettement' => $ratio?->debt_ratio,
                        'fonds_propres' => $ratio?->equity_ratio,
                        'cout_risque' => $ratio?->cost_of_risk_ratio,
                    ]
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'stocks_count' => count($codes),
                'comparison' => $comparison,
            ]
        ]);
    }

    /**
     * Dashboard global du marché
     * GET /api/analysis/dashboard?year=2024
     */
    public function getMarketDashboard(Request $request): JsonResponse
    {
        $year = $request->input('year', date('Y'));

        // Top performers
        $topByROE = StockRatio::with('stock')
            ->where('year', $year)
            ->whereNotNull('roe')
            ->orderBy('roe', 'desc')
            ->limit(5)
            ->get();

        $topByGrowth = StockRatio::with('stock')
            ->where('year', $year)
            ->whereNotNull('rn_growth')
            ->orderBy('rn_growth', 'desc')
            ->limit(5)
            ->get();

        $topByDividend = StockRatio::with('stock')
            ->where('year', $year)
            ->whereNotNull('dividend_yield')
            ->orderBy('dividend_yield', 'desc')
            ->limit(5)
            ->get();

        // Statistiques globales
        $allRatios = StockRatio::where('year', $year)->get();

        $marketStats = [
            'total_stocks' => $allRatios->count(),
            'total_market_cap' => $allRatios->sum('market_cap'),
            'average_roe' => $allRatios->avg('roe'),
            'average_per' => $allRatios->avg('per'),
            'average_dividend_yield' => $allRatios->avg('dividend_yield'),
            'median_debt_ratio' => $allRatios->median('debt_ratio'),
        ];

        // Répartition sectorielle
        $sectorDistribution = SectorAverage::where('year', $year)
            ->orderBy('stocks_count', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'market_stats' => $marketStats,
                'top_performers' => [
                    'by_roe' => $this->formatTopList($topByROE, 'roe'),
                    'by_growth' => $this->formatTopList($topByGrowth, 'rn_growth'),
                    'by_dividend' => $this->formatTopList($topByDividend, 'dividend_yield'),
                ],
                'sectors' => $sectorDistribution,
            ]
        ]);
    }

    // === MÉTHODES UTILITAIRES ===

    protected function calculateAverages(array $yearsData): array
    {
        if (empty($yearsData)) return [];

        $metrics = ['pnb_growth', 'rn_growth', 'net_margin', 'roe', 'roa', 'dividend_yield'];
        $averages = [];

        foreach ($metrics as $metric) {
            $values = [];
            foreach ($yearsData as $yearData) {
                $value = data_get($yearData, "croissance.{$metric}")
                    ?? data_get($yearData, "rentabilite.{$metric}")
                    ?? data_get($yearData, "remuneration.{$metric}");

                if ($value !== null) {
                    $values[] = $value;
                }
            }

            $averages[$metric] = !empty($values) ? round(array_sum($values) / count($values), 2) : null;
        }

        return $averages;
    }

    protected function calculateStandardDeviations(array $yearsData): array
    {
        if (empty($yearsData)) return [];

        $metrics = ['roe', 'roa', 'net_margin'];
        $stdDevs = [];

        foreach ($metrics as $metric) {
            $values = [];
            foreach ($yearsData as $yearData) {
                $value = data_get($yearData, "rentabilite.{$metric}");
                if ($value !== null) {
                    $values[] = $value;
                }
            }

            if (count($values) > 1) {
                $mean = array_sum($values) / count($values);
                $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $values)) / count($values);
                $stdDevs[$metric] = round(sqrt($variance), 2);
            } else {
                $stdDevs[$metric] = null;
            }
        }

        return $stdDevs;
    }

    protected function formatTopList($ratios, $criteria): array
    {
        return $ratios->map(function($ratio, $index) use ($criteria) {
            return [
                'rank' => $index + 1,
                'code' => $ratio->stock->code,
                'name' => $ratio->stock->name,
                'value' => $ratio->$criteria,
            ];
        })->toArray();
    }
}

