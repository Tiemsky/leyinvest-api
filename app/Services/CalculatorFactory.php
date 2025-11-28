<?php

namespace App\Services;

use App\Contracts\FinancialCalculatorInterface;
use App\Models\Action;
use App\Services\Calculators\FinancialServicesCalculator;
use App\Services\Calculators\StandardSectorCalculator;

/**
 * Factory pour créer le calculateur approprié selon le secteur
 *
 * Règle: Se base sur brvm_sector_id uniquement
 * - Si brvm_sector.slug = 'services-financiers' → FinancialServicesCalculator
 * - Sinon → StandardSectorCalculator
 */
class CalculatorFactory
{
    /**
     * Crée le calculateur approprié pour une action
     */
    public static function make(Action $action): FinancialCalculatorInterface{
        // Vérifier si l'action appartient au secteur services financiers
        if ($action->brvmSector && $action->brvmSector->slug === 'services-financiers') {
            return new FinancialServicesCalculator();
        }

        return new StandardSectorCalculator();
    }

    /**
     * Détermine le type de secteur
     */
    public static function getSectorType(Action $action): string{
        if ($action->brvmSector && $action->brvmSector->slug === 'services-financiers') {
            return 'services_financiers';
        }

        return 'autres_secteurs';
    }
}
