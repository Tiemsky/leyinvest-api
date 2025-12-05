<?php

namespace App\Http\Controllers;

use App\Http\Resources\SectorStatsResource;
use App\Models\BrvmSector;
use App\Models\ClassifiedSector;
use App\Models\SectorFinancialMetric;
use Illuminate\Http\JsonResponse;

class SectorStatsController extends Controller
{
    /**
     * GET /api/sectors/brvm/{sectorId}/stats
     *
     * Retourne les statistiques d'un secteur BRVM
     */
    public function brvmStats(int $sectorId): JsonResponse
    {
        $sector = BrvmSector::findOrFail($sectorId);
        $year = now()->year - 1;

        $metrics = SectorFinancialMetric::where('sector_type', 'brvm')
            ->where('sector_id', $sectorId)
            ->where('year', $year)
            ->first();

        if (!$metrics) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune métrique disponible pour ce secteur et cette année',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new SectorStatsResource($sector, $metrics, 'brvm'),
        ]);
    }

    /**
     * GET /api/sectors/classified/{sectorId}/stats
     *
     * Retourne les statistiques d'un secteur classifié
     */
    public function classifiedStats(int $sectorId): JsonResponse
    {
        $sector = ClassifiedSector::findOrFail($sectorId);
        $year = now()->year - 1;

        $metrics = SectorFinancialMetric::where('sector_type', 'classified')
            ->where('sector_id', $sectorId)
            ->where('year', $year)
            ->first();

        if (!$metrics) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune métrique disponible pour ce secteur et cette année',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new SectorStatsResource($sector, $metrics, 'classified'),
        ]);
    }

    /**
     * GET /api/sectors/brvm/{sectorId}/history
     *
     * Retourne l'historique d'un secteur BRVM sur 5 ans
     */
    public function brvmHistory(int $sectorId): JsonResponse
    {
        $sector = BrvmSector::findOrFail($sectorId);
        $currentYear = now()->year - 1;
        $years = range($currentYear, $currentYear - 4);

        $metrics = SectorFinancialMetric::where('sector_type', 'brvm')
            ->where('sector_id', $sectorId)
            ->whereIn('year', $years)
            ->orderBy('year', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'sector' => [
                    'id' => $sector->id,
                    'nom' => $sector->nom,
                    'slug' => $sector->slug,
                    'type' => 'brvm'
                ],
                'history' => $metrics->map(fn($m) => new SectorStatsResource($sector, $m, 'brvm'))
            ]
        ]);
    }

    /**
     * GET /api/sectors/classified/{sectorId}/history
     *
     * Retourne l'historique d'un secteur classifié sur 5 ans
     */
    public function classifiedHistory(int $sectorId): JsonResponse
    {
        $sector = ClassifiedSector::findOrFail($sectorId);
        $currentYear = now()->year - 1;
        $years = range($currentYear, $currentYear - 4);

        $metrics = SectorFinancialMetric::where('sector_type', 'classified')
            ->where('sector_id', $sectorId)
            ->whereIn('year', $years)
            ->orderBy('year', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'sector' => [
                    'id' => $sector->id,
                    'nom' => $sector->nom,
                    'slug' => $sector->slug,
                    'type' => 'classified'
                ],
                'history' => $metrics->map(fn($m) => new SectorStatsResource($sector, $m, 'classified'))
            ]
        ]);
    }
}
