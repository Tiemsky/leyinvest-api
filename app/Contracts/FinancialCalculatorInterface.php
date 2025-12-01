<?php

namespace App\Contracts;

use App\Models\StockFinancial;

/**
 * Interface pour les calculateurs d'indicateurs financiers
 *
 * Version 3.0 - Utilise les données RÉELLES en base de données
 * Calcule uniquement les RATIOS, pas les données de base
 *
 * Implémentations:
 * - FinancialServicesCalculator (services-financiers)
 * - StandardSectorCalculator (autres secteurs)
 */
interface FinancialCalculatorInterface
{
    /**
     * Calcule tous les indicateurs pour une année donnée
     *
     * @param StockFinancial $financial Données financières de l'année N
     * @param StockFinancial|null $previousYearFinancial Données de l'année N-1 (pour croissance)
     * @return array Structure complète des indicateurs
     *
     * Retourne:
     * [
     *   'croissance' => [...],
     *   'rentabilite' => [...],
     *   'remuneration' => [...],
     *   'valorisation' => [...],
     *   'solidite_financiere' => [...]
     * ]
     */
    public function calculate(StockFinancial $financial, ?StockFinancial $previousYearFinancial = null): array;

    /**
     * Calcule les indicateurs de croissance
     * Nécessite l'année N-1 pour calculer les taux de croissance
     *
     * @param StockFinancial $financial Année N
     * @param StockFinancial|null $previousYearFinancial Année N-1
     * @return array Indicateurs de croissance
     *
     * Retourne:
     * [
     *   'croissance_ca' ou 'croissance_pnb' => ['valeur' => float, 'formatted' => string],
     *   'croissance_rn' => [...],
     *   'croissance_ebit' => [...],
     *   'croissance_ebitda' => [...],
     *   'croissance_capex' => [...]
     * ]
     */
    public function calculateCroissance(StockFinancial $financial, ?StockFinancial $previousYearFinancial = null): array;

    /**
     * Calcule les indicateurs de rentabilité (marges, ROE, ROA)
     *
     * @param StockFinancial $financial
     * @return array Indicateurs de rentabilité
     *
     * Retourne:
     * [
     *   'marge_nette' => ['valeur' => float, 'formatted' => string],
     *   'marge_ebitda' => [...],
     *   'marge_operationnelle' => [...],
     *   'roe' => [...],
     *   'roa' => [...]
     * ]
     */
    public function calculateRentabilite(StockFinancial $financial): array;

    /**
     * Calcule les indicateurs de rémunération (DNPA, rendement, taux distribution)
     *
     * @param StockFinancial $financial
     * @return array Indicateurs de rémunération
     *
     * Retourne:
     * [
     *   'dnpa' => ['valeur' => float, 'formatted' => string],
     *   'rendement_dividende' => [...],
     *   'taux_distribution' => [...]
     * ]
     */
    public function calculateRemuneration(StockFinancial $financial): array;

    /**
     * Calcule les indicateurs de valorisation (PER, PBR, P/S, EV/EBITDA)
     *
     * @param StockFinancial $financial
     * @return array Indicateurs de valorisation
     *
     * Retourne:
     * [
     *   'per' => ['valeur' => float, 'formatted' => string],
     *   'pbr' => [...],
     *   'ratio_ps' => [...],
     *   'ev_ebitda' => [...],
     *   'cours_cible' => [...]
     * ]
     */
    public function calculateValorisation(StockFinancial $financial): array;

    /**
     * Calcule les indicateurs de solidité financière
     * Les formules diffèrent selon le secteur:
     * - Services financiers: 5 indicateurs spécifiques
     * - Autres secteurs: 3 indicateurs standards
     *
     * @param StockFinancial $financial
     * @return array Indicateurs de solidité financière
     *
     * Services Financiers:
     * [
     *   'ratio_autonomie_financiere' => [...],
     *   'ratio_prets_depots_capitaux' => [...],
     *   'loan_to_deposit' => [...],
     *   'ratio_endettement_general' => [...],
     *   'cout_du_risque' => [...]
     * ]
     *
     * Autres Secteurs:
     * [
     *   'dette_sur_capitalisation' => [...],
     *   'endettement_sur_actif' => [...],
     *   'endettement_general' => [...]
     * ]
     */
    public function calculateSoliditeFinanciere(StockFinancial $financial): array;
}
