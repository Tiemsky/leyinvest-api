<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinancialNewsRequest;
use App\Http\Collections\FinancialNewsCollection;
use App\Http\Resources\FinancialNewsResource;
use App\Models\FinancialNews;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @tags Actualités Financières
 */
class FinancialNewsController extends Controller
{
    /**
     * Liste paginée des actualités avec filtres et recherche.
     */
    public function index(FinancialNewsRequest $request): FinancialNewsCollection
    {
        $cacheKey = 'financial_news:index:' . md5($request->fullUrl());

        $news = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($request) {
            $query = FinancialNews::query();

            // Filtres via Scopes
            if ($request->filled('source')) $query->bySource($request->input('source'));
            if ($request->filled('company')) $query->byCompany($request->input('company'));

            // Filtre de période
            if ($request->filled('from')) $query->whereDate('published_at', '>=', $request->date('from'));
            if ($request->filled('to')) $query->whereDate('published_at', '<=', $request->date('to'));

            // Recherche
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(fn($q) => $q->where('title', 'LIKE', "%{$search}%")->orWhere('company', 'LIKE', "%{$search}%"));
            }

            return $query->orderBy($request->input('sort_by', 'published_at'), $request->input('sort_order', 'desc'))
                         ->paginate($request->input('per_page', 20));
        });

        return new FinancialNewsCollection($news);
    }

    /**
     * Détails d'une actualité spécifique.
     */
    public function show(FinancialNews $financialNews): FinancialNewsResource
    {
        $news = Cache::remember("financial_news:show:{$financialNews->id}", now()->addMinutes(30), fn() => $financialNews);
        return new FinancialNewsResource($news);
    }

    /**
     * Actualités récentes (X derniers jours).
     */
    public function recent(Request $request): FinancialNewsCollection
    {
        $days = (int) $request->input('days', 7);
        $perPage = (int) $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $cacheKey = "financial_news:recent:{$days}:{$perPage}:{$page}";

        $news = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($days, $perPage) {
            return FinancialNews::recent($days)
                ->orderBy('published_at', 'desc')
                ->paginate($perPage);
        });

        return new FinancialNewsCollection($news);
    }

    /**
     * Liste des sources disponibles.
     */
    public function sources(): JsonResponse
    {
        return $this->getCachedList('source', 'financial_news:unique_sources');
    }

    /**
     * Liste des entreprises disponibles.
     */
    public function companies(): JsonResponse
    {
        return $this->getCachedList('company', 'financial_news:unique_companies', true);
    }

    /**
     * Filtre spécifique par type de source (RichBourse vs Autres).
     */
    public function getFinancialNewBySource(string $source): FinancialNewsCollection
    {
        $page = request('page', 1);
        $perPage = (int) request('per_page', 20);
        $cacheKey = "financial_news:by_source:{$source}:p{$page}:sp{$perPage}";

        $news = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($source, $perPage) {
            $query = FinancialNews::query();

            if ($source === 'richbourse_etats_financiers') {
                $query->where('source', $source);
            } else {
                $query->where('source', '!=', 'richbourse_etats_financiers');
            }

            return $query->orderBy('published_at', 'desc')->paginate($perPage);
        });

        return new FinancialNewsCollection($news);
    }

    /**
     * Helper pour les listes distinctes avec Cache.
     */
    private function getCachedList(string $column, string $cacheKey, bool $sort = false): JsonResponse
    {
        $list = Cache::remember($cacheKey, now()->addHours(24), function () use ($column, $sort) {
            $data = FinancialNews::distinct()->pluck($column)->filter()->values();
            return $sort ? $data->sort()->values() : $data;
        });

        return response()->json([
            'success' => true,
            'data' => $list,
            'count' => $list->count(),
        ]);
    }
}
