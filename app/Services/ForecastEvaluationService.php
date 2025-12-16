<?php

namespace App\Services;

use App\Models\Action;
use App\Models\EvaluationParameter;
use Illuminate\Support\Collection;

class ForecastEvaluationService
{
    private $params;

    public function __construct()
    {
        // Charger les paramètres en cache pour éviter N requêtes SQL
        $this->params = cache()->remember('valuation_params', 3600, function () {
            return EvaluationParameter::pluck('value', 'nom');
        });
    }

    /**
     * Orchestre le calcul complet pour une action
     */
    public function calculateForAction(Action $action)
    {
        $financials = $action->stockFinancials()->orderBy('year', 'desc')->get();

        // Calcul du RNp avec la logique trimestrielle
        $rnPrevisionnel = $this->calculateRNPrevisionnel($action, $financials);

        // Calcul du DNPAp
        $dnpaPrevisionnel = $this->calculateDNPAPrevisionnel($action, $financials, $rnPrevisionnel);

        // Mise à jour de la table de prévisions
        $action->forecast()->updateOrCreate(
            ['action_id' => $action->id],
            [
                'rn_previsionnel' => $rnPrevisionnel,
                'dnpa_previsionnel' => $dnpaPrevisionnel
                // Rappel : Le rendement n'est pas stocké ici car il dépend du cours de cloture en temps réel
            ]
        );

        return $action->forecast;
    }

    // --- LOGIQUE A : RN PREVISIONNEL  ---
    private function calculateRNPrevisionnel(Action $action, Collection $financials)
    {
        $currentYear = now()->year;

        // 1. Priorité aux données réelles (Si RN de l'année N existe déjà)
        $realRN = $financials->firstWhere('year', $currentYear);
        if ($realRN && $realRN->resultat_net) {
            return $realRN->resultat_net;
        }

        // 2. Calcul Moyenne Historique (RN Moyen)
        $historicalYears = $financials->where('year', '<', $currentYear)
                                      ->whereNotNull('resultat_net')
                                      ->take(4); // Max 4 ans (N-1 à N-4)

        $rnMoyen = 0;
        if ($historicalYears->isNotEmpty()) {
            $numerator = 0;
            $denominator = 0;
            $i = 1;
            foreach ($historicalYears as $fin) {
                // Récupère X1, X2... (défaut 1)
                $weight = $this->params["weight_x{$i}"] ?? 1;
                $numerator += $fin->resultat_net * $weight;
                $denominator += $weight;
                $i++;
            }
            $rnMoyen = $denominator > 0 ? $numerator / $denominator : 0;
        }

        // 3. Calcul Moyenne Evolution Trimestrielle (Ev_moyenne)
        // Calculons maintenant Evolution Net Prévisionnelle
        // On récupère les trimestres de l'année en cours via la relation
        $quarterlyData = $action->quarterlyResults()
                                ->where('year', $currentYear)
                                ->whereIn('trimestre', [1, 2, 3]) // On cherche T1, T2, T3
                                ->get();

        // Extraction des valeurs (0 si non existant)
        // EvT1 = trimestre 1, EvT2 = trimestre 2, etc.
        $evT1 = $quarterlyData->firstWhere('trimestre', 1)->evolution_rn ?? 0;
        $evT2 = $quarterlyData->firstWhere('trimestre', 2)->evolution_rn ?? 0;
        $evT3 = $quarterlyData->firstWhere('trimestre', 3)->evolution_rn ?? 0;

        // Formule: (EvT1 + EvT2 + EvT3) / 3
        $evMoyenne = ($evT1 + $evT2 + $evT3) / 3;

        // Calcul Final RNp [cite: 22]
        $K = $this->params['coeff_k'] ?? 0.3;
        $Z = $this->params['coeff_z'] ?? 0.7;

        return ($evMoyenne * $K) + ($rnMoyen * $Z);
    }

    // --- LOGIQUE B : DNPA PREVISIONNEL  ---
    private function calculateDNPAPrevisionnel(Action $action, Collection $financials, $rnPrevisionnel)
    {
        // On a besoin des 5 dernières années historiques pour les moyennes [cite: 50]
        // Mais CV calculé sur n années disponibles
        $history = $financials->whereNotNull('resultat_net')
                              ->where('resultat_net', '!=', 0)
                              ->take(5);

        if ($history->count() < 3) return 0; // Sécurité si pas assez de data

        // Calcul des TD historiques et DNPA
        $dataPoints = $history->map(function ($item) {
            return [
                'td' => $item->dividendes_bruts / $item->resultat_net,
                'dnpa' => $item->dnpa
            ];
        });

        // Moyennes sur 5 ans (ou disponibles)
        $avgTD_5 = $dataPoints->avg('td');
        $avgDNPA_5 = $dataPoints->avg('dnpa');

        // Ecart-types et Moyennes pour CV (sur n années)
        $stdTD = $this->statsStandardDeviation($dataPoints->pluck('td')->toArray());
        $stdDNPA = $this->statsStandardDeviation($dataPoints->pluck('dnpa')->toArray());

        // CV = Ecart-type / Moyenne 5 ans
        $CVa = ($avgTD_5 != 0) ? $stdTD / $avgTD_5 : 999;
        $CVb = ($avgDNPA_5 != 0) ? $stdDNPA / $avgDNPA_5 : 999;

        // Choix de la méthode [cite: 40-43]
        $dividendeTotalPrev = 0;
        $dernierRN = $history->first()->resultat_net ?? 0; // N-1
        $nbTitres = $history->first()->nombre_titre; // N-1 (ou actuel selon besoin)

        if ($CVa < $CVb) {
            // Cas 1: TD plus stable
            $dividendeTotalPrev = $avgTD_5 * $dernierRN;
        } else {
            // Cas 2: DNPA plus stable ou égal
            $dividendeTotalPrev = $avgDNPA_5 * 1.1 * $nbTitres;
        }

        // Application IRVM
        $irvm = $this->params['irvm'];
        if ($nbTitres == 0) return 0;
        return ((1 - $irvm) * $dividendeTotalPrev) / $nbTitres;
    }

    // Helper statistique pour Ecart-Type
    private function statsStandardDeviation(array $a) {
        $n = count($a);
        if ($n === 0) return 0;
        $mean = array_sum($a) / $n;
        $carry = 0.0;
        foreach ($a as $val) {
            $d = ((double) $val) - $mean;
            $carry += $d * $d;
        }
        return sqrt($carry / $n);
    }
}
