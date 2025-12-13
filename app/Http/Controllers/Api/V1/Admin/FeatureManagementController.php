<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FeatureManagementController extends Controller
{
    /**
     * Liste toutes les features
     * GET /api/v1/admin/features
     */
    public function index()
    {
        $features = Feature::withCount('plans')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $features->map(function($feature) {
                return [
                    'id' => $feature->id,
                    'key' => $feature->key,
                    'name' => $feature->name,
                    'slug' => $feature->slug,
                    'is_active' => $feature->is_active,
                    'plans_count' => $feature->plans_count,
                    'created_at' => $feature->created_at?->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Créer une nouvelle feature
     * POST /api/v1/admin/features
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:features,key|regex:/^[a-z_]+$/',
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $feature = Feature::create([
            'key' => $validated['key'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['key']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feature créée avec succès.',
            'data' => $feature,
        ], 201);
    }

    /**
     * Afficher une feature avec les plans qui l'utilisent
     * GET /api/v1/admin/features/{feature}
     */
    public function show(Feature $feature)
    {
        $feature->load('plans');

        return response()->json([
            'success' => true,
            'data' => [
                'feature' => $feature,
                'plans' => $feature->plans->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->nom,
                    'slug' => $p->slug,
                    'is_enabled' => $p->pivot->is_enabled,
                ]),
            ],
        ]);
    }

    /**
     * Mettre à jour une feature
     * PUT/PATCH /api/v1/admin/features/{feature}
     */
    public function update(Request $request, Feature $feature)
    {
        $validated = $request->validate([
            'key' => 'sometimes|string|unique:features,key,' . $feature->id . '|regex:/^[a-z_]+$/',
            'name' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['key'])) {
            $validated['slug'] = Str::slug($validated['key']);
        }

        $feature->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Feature mise à jour avec succès.',
            'data' => $feature->fresh(),
        ]);
    }

    /**
     * Supprimer une feature
     * DELETE /api/v1/admin/features/{feature}
     */
    public function destroy(Feature $feature)
    {
        // Vérifier si la feature est utilisée par des plans
        $plansCount = $feature->plans()->count();

        if ($plansCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Impossible de supprimer cette feature. Elle est utilisée par {$plansCount} plan(s).",
                'hint' => 'Détachez-la d\'abord de tous les plans.',
            ], 422);
        }

        $feature->delete();

        return response()->json([
            'success' => true,
            'message' => 'Feature supprimée avec succès.',
        ]);
    }
}
