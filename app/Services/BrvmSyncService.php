<?php

namespace App\Services;

use App\Models\Action;
use App\Models\BocIndicator;
use App\Models\BrvmSector;
use App\Models\IndiceSectoriel;
use App\Models\IndicateurMarche;
use Illuminate\Support\Facades\DB;

class BrvmSyncService
{
    /**
     * Point d'entrée principal pour traiter les données du Webhook
     */
    public function handlePayload(string $dataType, array $payload): array
    {
        return DB::transaction(function () use ($dataType, $payload) {
            return match ($dataType) {
                'actions' => $this->processActions($payload['actions'] ?? []),
                'indices_sectoriels' => $this->processIndices($payload['indices'] ?? []),
                'indicateurs_marche' => $this->processIndicators($payload),
                default => throw new \Exception("Type de donnée '$dataType' non supporté"),
            };
        });
    }

    private function processActions(array $actions): array
    {
        foreach ($actions as $item) {
            Action::updateOrCreate(
                ['symbole' => $item['symbole']],
                [
                    'nom' => $item['nom'],
                    'volume' => $item['volume'],
                    'cours_veille' => $item['cours_veille'],
                    'cours_ouverture' => $item['cours_ouverture'],
                    'cours_cloture' => $item['cours_cloture'],
                    'variation' => $item['variation'],
                    'updated_at' => now()
                ]
            );
        }
        return ['message' => count($actions) . ' actions synchronisées'];
    }

    private function processIndices(array $indices): array
    {
        foreach ($indices as $item) {
            BrvmSector::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'nom' => $item['nom'],
                    'variation' => $item['variation'],
                    'updated_at' => $item['updated_at'] ?? now()
                ]
            );
        }
        return ['message' => count($indices) . ' indices synchronisés'];
    }

    private function processIndicators(array $data): array
    {
        BocIndicator::updateOrCreate(
            [
              'date_rapport' => $data['date_rapport']
            ],
            [
                'taux_rendement_moyen' => $data['taux_rendement_moyen'],
                'per_moyen' => $data['per_moyen'],
                'taux_rentabilite_moyen' => $data['taux_rentabilite_moyen'],
                'prime_risque_marche' => $data['prime_risque_marche'],
                'source_pdf'  =>  'boc_' . now()->format('Ymd') . '_2.pdf',
                'updated_at' => now()
            ]
        );
        return ['message' => 'Indicateurs mis à jour'];
    }
}
