<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinancialNewsRequest; // Pour la méthode index
use App\Http\Collections\FinancialNewsCollection;
use App\Http\Resources\FinancialNewsResource;
use App\Models\FinancialNews;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request; // Pour les actions de collection
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


/**
 * @OA\Tag(
 *     name="Financial News",
 *     description="Endpoints pour la gestion et la consultation des actualités financières"
 * )
 */
class FinancialNewsController extends Controller
{
    /**
     * Structure de réponse d'erreur standard (pour les erreurs 500 inattendues).
     */
    protected function internalErrorResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Une erreur interne est survenue lors du traitement de la requête.',
        ], 500);
    }

      /**
     * @OA\Get(
     *     path="/api/v1/financial-news",
     *     tags={"Financial News"},
     *     summary="Lister les actualités financières",
     *     description="Récupère une liste paginée d'actualités financières avec filtres, tri et recherche globale.",
     *     @OA\Parameter(
     *         name="source",
     *         in="query",
     *         description="Filtrer par source (ex: BRVM, AMF, ECOBANK)",
     *         required=false,
     *         @OA\Schema(type="string", example="BRVM")
     *     ),
     *     @OA\Parameter(
     *         name="company",
     *         in="query",
     *         description="Filtrer par nom de société (recherche partielle)",
     *         required=false,
     *         @OA\Schema(type="string", example="SONATEL")
     *     ),
     *     @OA\Parameter(
     *         name="from",
     *         in="query",
     *         description="Date minimale de publication (format: YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         description="Date maximale de publication (format: YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-12-31")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche globale dans le titre ou le nom de société",
     *         required=false,
     *         @OA\Schema(type="string", example="dividende")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Champ utilisé pour le tri des résultats",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"published_at", "created_at", "title", "company"},
     *             example="published_at"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Ordre du tri",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"asc", "desc"},
     *             example="desc"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page (défaut: 20, max: 100)",
     *         required=false,
     *         @OA\Schema(type="integer", example=20, minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/FinancialNews")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=10),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="total", type="integer", example=200)
     *             ),
     *             @OA\Property(property="links", type="object",
     *                 @OA\Property(property="first", type="string", example="https://api.leyinvest.ci/api/v1/financial-news?page=1"),
     *                 @OA\Property(property="last", type="string", example="https://api.leyinvest.ci/api/v1/financial-news?page=10"),
     *                 @OA\Property(property="prev", type="string", nullable=true, example=null),
     *                 @OA\Property(property="next", type="string", nullable=true, example="https://api.leyinvest.ci/api/v1/financial-news?page=2")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Paramètres de requête invalides"),
     *     @OA\Response(response=500, description="Erreur interne du serveur")
     * )
     */


    public function index(FinancialNewsRequest $request): JsonResponse
    {
        try {
            // Clé de cache unique basée sur l'URL complète
            $cacheKey = 'financial_news:' . md5($request->fullUrl());
            $cacheDuration = now()->addMinutes(10);

            $news = Cache::remember($cacheKey, $cacheDuration, function () use ($request) {
                $query = FinancialNews::query();

                // 1. FILTRES (Utilisation de Scopes Locaux si définis dans le Modèle)
                if ($request->filled('source')) {
                    // Supposons que bySource est un scope dans FinancialNews
                    $query->bySource($request->input('source'));
                }
                if ($request->filled('company')) {
                    $query->byCompany($request->input('company'));
                }

                // 2. FILTRE DE PÉRIODE (Format YYYY-MM-DD)
                if ($request->filled('from')) {
                    $query->whereDate('published_at', '>=', $request->date('from'));
                }
                if ($request->filled('to')) {
                    $query->whereDate('published_at', '<=', $request->date('to'));
                }

                // 3. RECHERCHE GLOBALE
                if ($request->filled('search')) {
                    $search = $request->input('search');
                    $query->where(function ($q) use ($search) {
                        $q->where('title', 'LIKE', "%{$search}%")
                          ->orWhere('company', 'LIKE', "%{$search}%");
                    });
                }

                // 4. TRI DYNAMIQUE (Validé dans FinancialNewsRequest)
                $sortBy = $request->input('sort_by', 'published_at');
                $sortOrder = $request->input('sort_order', 'desc');
                $query->orderBy($sortBy, $sortOrder);

                // 5. PAGINATION (per_page validé dans FinancialNewsRequest)
                $perPage = $request->input('per_page', 20);
                return $query->paginate($perPage);
            });
            return (new FinancialNewsCollection($news))->response()->setStatusCode(200);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des actualités financières: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->internalErrorResponse();
        }
    }

    // --- ENDPOINT DE DÉTAIL ---

    /**
     * @OA\Get(
     * path="/api/v1/financial-news/{financialNews}",
     * tags={"Financial News"},
     * summary="Détails d'une actualité financière via key",
     * description="Récupère les détails d'une actualité spécifique via la key",
     * @OA\Parameter(
     * name="financialNews",
     * in="path",
     * description="key de l'actualité",
     * required=true,
     * @OA\Schema(type="string")
     * ),
     * @OA\Response(response=200, description="Actualité récupérée avec succès"),
     * @OA\Response(response=404, description="Actualité non trouvée")
     * )
     */
    public function show(FinancialNews $financialNews): JsonResponse{

        try {
            $cacheKey = "financial_news:single:{$financialNews->getKey()}";

            $news = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($financialNews) {
                return $financialNews;
            });

            return (new FinancialNewsResource($news))->response()->setStatusCode(200);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'actualité financière (Show): ' . $e->getMessage(), ['key' => $financialNews->getKey()]);
            return $this->internalErrorResponse();
        }
    }

    // --- ACTIONS DE COLLECTION / UTILITAIRES ---

    /**
     * @OA\Get(
     * path="/api/v1/financial-news/recent",
     * tags={"Financial News"},
     * summary="Actualités récentes",
     * description="Récupère les actualités des X derniers jours. Paramètres de requête : days (int, défaut 7), per_page.",
     * @OA\Response(response=200, description="Actualités récentes récupérées avec succès")
     * )
     */
    public function recent(Request $request): JsonResponse
    {
        try {
            // Utilisation des paramètres de requête pour plus de flexibilité
            $days = (int) $request->input('days', 7);
            $perPage = $request->input('per_page', 20);

            // Inclus les paramètres de pagination dans la clé de cache
            $cacheKey = "financial_news:recent:{$days}:{$perPage}:" . $request->page;
            $cacheDuration = now()->addMinutes(5);

            $news = Cache::remember($cacheKey, $cacheDuration, function () use ($days, $perPage) {
                return FinancialNews::recent($days) // Scope requis dans le modèle: where('published_at', '>=', now()->subDays($days))
                    ->orderBy('published_at', 'desc')
                    ->paginate($perPage);
            });

            return (new FinancialNewsCollection($news))->response()->setStatusCode(200);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des actualités récentes: ' . $e->getMessage());
            return $this->internalErrorResponse();
        }
    }

    /**
     * Méthode privée pour factoriser la logique de récupération des listes uniques (Sources, Companies).
     */
    private function getCachedList(string $column, string $cacheKey, bool $sort = false): JsonResponse
    {
        try {
            $list = Cache::remember($cacheKey, now()->addHours(24), function () use ($column, $sort) {
                $query = FinancialNews::distinct()->pluck($column)->filter()->values();
                return $sort ? $query->sort() : $query;
            });

            // Réponse cohérente et structurée
            return response()->json([
                'data' => $list,
                'count' => $list->count(),
            ], 200);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération de la liste '{$column}': " . $e->getMessage());
            return $this->internalErrorResponse();
        }
    }

    /**
     * @OA\Get(
     * path="/api/v1/financial-news/sources",
     * tags={"Financial News"},
     * summary="Liste des sources",
     * description="Récupère la liste de toutes les sources d'actualités uniques",
     * @OA\Response(response=200, description="Sources récupérées avec succès")
     * )
     */
    public function sources(): JsonResponse
    {
        return $this->getCachedList('source', 'financial_news:sources', false);
    }




    /**
     * @OA\Get(
     * path="/api/v1/financial-news/companies",
     * tags={"Financial News"},
     * summary="Liste des sociétés (actions)",
     * description="Récupère la liste de toutes les sociétés ayant des actualités (pour autocomplétion)",
     * @OA\Response(response=200, description="Sociétés récupérées avec succès")
     * )
     */
    public function companies(): JsonResponse
    {
        return $this->getCachedList('company', 'financial_news:companies', true);
    }

    /**
     * @OA\Get(
     * path="/api/v1/financial-news/statistics",
     * tags={"Financial News"},
     * summary="Statistiques du flux",
     * description="Récupère des statistiques agrégées (totaux, par source, récentes) pour les dashboards.",
     * @OA\Response(response=200, description="Statistiques récupérées avec succès")
     * )
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = Cache::remember('financial_news:statistics', now()->addHours(1), function () {
                return [
                    'total_news' => FinancialNews::count(),
                    'total_companies' => FinancialNews::distinct('company')->count(),
                    'total_sources' => FinancialNews::distinct('source')->count(),
                    'news_last_7_days' => FinancialNews::recent(7)->count(),
                    'news_last_30_days' => FinancialNews::recent(30)->count(),
                    'latest_publication' => FinancialNews::latest('published_at')->first()?->published_at,
                    'count_by_source' => FinancialNews::selectRaw('source, COUNT(*) as count')
                        ->groupBy('source')
                        ->pluck('count', 'source'),
                ];
            });

            return response()->json([
                'data' => $stats,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des statistiques: ' . $e->getMessage());
            return $this->internalErrorResponse();
        }
    }


public function getFinancialNewBySource(string $source): JsonResponse
{
    try {
        $cacheKey = "financial_news:by_source:{$source}"; // Consider adding page if paginated

        // Since we're paginating, include page in cache key
        $page = request('page', 1);
        $perPage = (int) request('per_page', 20);
        $cacheKey = "financial_news:by_source:{$source}:page_{$page}:per_{$perPage}";

        $news = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($source, $perPage) {
            $query = FinancialNews::query();

            if ($source === 'richbourse_etats_financiers') {
                $query->where('source', $source);
            } else {
                $query->where('source', '!=', 'richbourse_etats_financiers');
            }

            return $query->orderBy('published_at', 'desc')->paginate($perPage);
        });

        return (new FinancialNewsCollection($news))->response()->setStatusCode(200);

    } catch (\Exception $e) {
        Log::error('Erreur lors de la récupération des actualités financières par source: ' . $e->getMessage(), ['source' => $source]);
        return $this->internalErrorResponse();
    }
}
}
