<?php

namespace App\Services;

use App\Jobs\ProcessForecastsJob;
use App\Models\Action;
use App\Models\BocIndicator;
use App\Models\BrvmSector;
use App\Models\ClassifiedSector;
use App\Support\BrvmMapping;
use Illuminate\Support\Facades\DB;

class BrvmSyncService
{
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
        $brvmSectors = BrvmSector::pluck('id', 'nom')->toArray();
        $classifiedSectors = ClassifiedSector::pluck('id', 'nom')->toArray();
        $mapping = BrvmMapping::actionSectorMap();
        $now = now();

        $records = [];
        foreach ($actions as $item) {
            $sectors = $mapping[$item['symbole']] ?? null;
            if (! $sectors) {
                continue;
            }

            $records[] = [
                'symbole' => $item['symbole'],
                'key' => 'act_'.strtolower($item['symbole']),
                'nom' => $item['nom'] ?? 'Inconnu',
                'brvm_sector_id' => $brvmSectors[$sectors[0]] ?? 1,
                'classified_sector_id' => $classifiedSectors[$sectors[1]] ?? 1,
                'volume' => (string) ($item['volume'] ?? '0'),
                'cours_cloture' => $item['cours_cloture'] ?? 0,
                'variation' => $item['variation'] ?? 0,
                'updated_at' => $now,
            ];
        }

        Action::upsert($records, ['symbole'], ['volume', 'cours_cloture', 'variation', 'updated_at']);

        ProcessForecastsJob::dispatch();

        return ['count' => count($records)];
    }

    private function processIndicators(array $data): array
    {
        BocIndicator::updateOrCreate(
            ['date_rapport' => $data['date_rapport']],
            [
                // Ici HasKey fonctionne car updateOrCreate utilise Eloquent
                'taux_rendement_moyen' => $data['taux_rendement_moyen'] ?? null,
                'per_moyen' => $data['per_moyen'] ?? null,
                'taux_rentabilite_moyen' => $data['taux_rentabilite_moyen'] ?? null,
                'prime_risque_marche' => $data['prime_risque_marche'] ?? null,
                'source_pdf' => $data['source_pdf'] ?? null,
                'updated_at' => now(),
            ]
        );

        return ['message' => 'Indicateurs mis à jour'];
    }
}
