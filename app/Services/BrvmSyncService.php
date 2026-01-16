<?php

namespace App\Services;

use App\Models\Action;
use App\Models\BocIndicator;
use App\Models\BrvmSector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        if (empty($actions)) return ['message' => 'Aucune action à synchroniser'];

        $now = now();
        $records = [];

        // 1. Préparation du batch de données
        foreach ($actions as $item) {
            $symbole = $item['symbole'];
            $records[] = [
                'symbole'              => $symbole,
                'key'                  => 'act_' . strtolower($symbole), // Fix pour contrainte NOT NULL
                'nom'                  => $item['nom'],
                'volume'               => (string) ($item['volume'] ?? '0'),
                'cours_veille'         => $item['cours_veille'] ?? 0,
                'cours_ouverture'      => $item['cours_ouverture'] ?? 0,
                'cours_cloture'        => $item['cours_cloture'] ?? 0,
                'variation'            => $item['variation'] ?? 0,
                'updated_at'           => $now,
                'created_at'           => $now,
            ];
        }

        // 2. Exécution d'une seule requête SQL (Upsert)
        // On ne met à jour que les prix et le volume en cas de doublon
        Action::upsert($records, ['symbole'], [
            'volume', 'cours_veille', 'cours_ouverture', 'cours_cloture', 'variation', 'updated_at'
        ]);

        return ['message' => count($actions) . ' actions synchronisées via Upsert'];
    }

    private function processIndices(array $indices): array
    {
        if (empty($indices)) return ['message' => 'Aucun indice à synchroniser'];

        $now = now();
        $records = array_map(fn($item) => [
            'slug'       => $item['slug'],
            'nom'        => $item['nom'],
            'variation'  => $item['variation'] ?? 0,
            'updated_at' => $item['updated_at'] ?? $now,
            'created_at' => $now,
        ], $indices);

        // Batch update sur les secteurs
        BrvmSector::upsert($records, ['slug'], ['nom', 'variation', 'updated_at']);

        return ['message' => count($indices) . ' indices synchronisés via Upsert'];
    }

    private function processIndicators(array $data): array
    {
        $dateRapport = $data['date_rapport'] ?? null;
        if (!$dateRapport) {
            throw new \Exception("Champ 'date_rapport' manquant dans les indicateurs");
        }

        // Pour un enregistrement unique, updateOrCreate reste correct et lisible
        BocIndicator::updateOrCreate(
            ['date_rapport' => $dateRapport],
            [
                'taux_rendement_moyen'   => $data['taux_rendement_moyen'] ?? null,
                'per_moyen'              => $data['per_moyen'] ?? null,
                'taux_rentabilite_moyen' => $data['taux_rentabilite_moyen'] ?? null,
                'prime_risque_marche'    => $data['prime_risque_marche'] ?? null,
                'source_pdf'             => $data['source_pdf'] ?? null,
                'updated_at'             => now(),
            ]
        );

        return ['message' => "Indicateurs mis à jour pour le $dateRapport"];
    }
}
