<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Models\FinancialNews;

class FinancialNewsController extends Controller
{
    public function index(Request $request)
{
    $cacheKey = 'financial_news:' . md5($request->fullUrl());

    // $news = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($request) {
    //     $query = FinancialNews::query();

    //     if ($request->filled('source')) {
    //         $query->where('source', $request->string('source'));
    //     }
    //     if ($request->filled('company')) {
    //         $query->where('company', 'LIKE', "%{$request->string('company')}%");
    //     }
    //     if ($request->filled('from')) {
    //         $query->whereDate('published_at', '>=', $request->date('from'));
    //     }
    //     if ($request->filled('to')) {
    //         $query->whereDate('published_at', '<=', $request->date('to'));
    //     }

    //     return $query->latest('published_at')->paginate(20);
    // });


   $news = FinancialNews::all();
    return response()->json($news);
    }
}
