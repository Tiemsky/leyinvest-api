<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Service de gestion de l'année fiscale de référence
 *
 * Gère la logique métier de bascule d'année selon la date de consolidation comptable
 * Date de bascule : 1er mars
 * - Avant le 01/03 inclus : année N-2
 * - À partir du 02/03 : année N-1
 */
class FiscalYearService
{
    /**
     * Date de bascule annuelle (1er mars)
     */
    private const CUTOFF_MONTH = 3;

    private const CUTOFF_DAY = 1;

    /**
     * Durée de cache en secondes (24h)
     * Le cache est invalidé quotidiennement pour détecter le changement de période
     */
    private const CACHE_TTL = 86400;

    /**
     * Détermine l'année de référence selon la date de bascule fiscale
     *
     * Logique :
     * - Avant le 01/03 inclus : année N-2 (en 2026, on affiche 2024)
     * - À partir du 02/03 : année N-1 (en 2026, on affiche 2025)
     *
     * @param  Carbon|null  $date  Date de référence (défaut: maintenant)
     */
    public function getReferenceYear(?Carbon $date = null): int
    {
        $date = $date ?? now();
        $currentYear = $date->year;

        // Utilisation du cache pour optimiser les appels répétés
        $cacheKey = "fiscal_year.reference.{$date->format('Y-m-d')}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($date, $currentYear) {
            $cutoffDate = $this->getCutoffDate($currentYear);

            // Si on est avant ou égal au 01 mars, on utilise l'année N-2
            if ($date->lessThanOrEqualTo($cutoffDate)) {
                return $currentYear - 2;
            }

            // À partir du 02 mars, on utilise l'année N-1
            return $currentYear - 1;
        });
    }

    /**
     * Retourne la date de bascule pour une année donnée (01/03 à 23:59:59)
     */
    public function getCutoffDate(int $year): Carbon
    {
        return Carbon::create(
            $year,
            self::CUTOFF_MONTH,
            self::CUTOFF_DAY,
            23,
            59,
            59
        );
    }

    /**
     * Retourne la date de début de la nouvelle période (02/03 à 00:00:00)
     */
    public function getNewPeriodStartDate(int $year): Carbon
    {
        return Carbon::create(
            $year,
            self::CUTOFF_MONTH,
            self::CUTOFF_DAY + 1,
            0,
            0,
            0
        );
    }

    /**
     * Vérifie si nous sommes en période de consolidation
     * (du 01/01 au 01/03 inclus)
     */
    public function isConsolidationPeriod(?Carbon $date = null): bool
    {
        $date = $date ?? now();
        $cutoffDate = $this->getCutoffDate($date->year);

        return $date->lessThanOrEqualTo($cutoffDate);
    }

    /**
     * Retourne un label explicatif de la période fiscale active
     */
    public function getFiscalPeriodLabel(?Carbon $date = null): string
    {
        $date = $date ?? now();
        $referenceYear = $this->getReferenceYear($date);
        $currentYear = $date->year;

        if ($this->isConsolidationPeriod($date)) {
            return sprintf(
                'Période de consolidation (données %d disponibles jusqu\'au 01/03/%d)',
                $referenceYear,
                $currentYear
            );
        }

        return sprintf(
            'Données financières %d (mises à jour depuis le 02/03/%d)',
            $referenceYear,
            $currentYear
        );
    }

    /**
     * Calcule la prochaine date de mise à jour des données (02/03)
     */
    public function getNextUpdateDate(?Carbon $date = null): Carbon
    {
        $date = $date ?? now();
        $currentYear = $date->year;

        $nextCutoff = $this->getNewPeriodStartDate($currentYear);

        // Si on est déjà passé le 01 mars, la prochaine date est le 02 mars de l'année suivante
        if ($date->greaterThan($this->getCutoffDate($currentYear))) {
            $nextCutoff = $this->getNewPeriodStartDate($currentYear + 1);
        }

        return $nextCutoff;
    }

    /**
     * Génère un tableau d'années pour l'historique
     * À partir de l'année de référence, remonte sur N années
     *
     * @param  int  $count  Nombre d'années à retourner
     * @param  Carbon|null  $date  Date de référence
     */
    public function getHistoricalYears(int $count = 5, ?Carbon $date = null): array
    {
        $referenceYear = $this->getReferenceYear($date);

        return range($referenceYear, $referenceYear - ($count - 1));
    }

    /**
     * Retourne les métadonnées complètes de la période fiscale
     * Utile pour enrichir les réponses API
     */
    public function getMetadata(?Carbon $date = null): array
    {
        $date = $date ?? now();
        $referenceYear = $this->getReferenceYear($date);
        $nextUpdate = $this->getNextUpdateDate($date);

        return [
            'reference_year' => $referenceYear,
            'current_date' => $date->toDateString(),
            'fiscal_period' => $this->getFiscalPeriodLabel($date),
            'is_consolidation_period' => $this->isConsolidationPeriod($date),
            'next_update_date' => $nextUpdate->toDateString(),
            'cutoff_date' => $this->getCutoffDate($date->year)->toDateString(),
        ];
    }

    /**
     * Invalide le cache de l'année fiscale
     * Utile lors de tests ou de migrations de données
     */
    public function clearCache(): void
    {
        Cache::forget('fiscal_year.reference.*');
    }
}
