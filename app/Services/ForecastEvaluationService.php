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
        // On récupère le passé financier (bilans annuels) du plus récent au plus ancien
        $historiqueAnnuel = $action->stockFinancials()->orderBy('year', 'desc')->get();

        // 1. On devine le futur Bénéfice Net (Résultat Net Prévisionnel)
        $beneficeNetEsperé = $this->estimerProchainBeneficeNet($action, $historiqueAnnuel);

        // 2. On devine le futur Dividende que l'action va payer (DNPA Prévisionnel)
        $dividendeNetEsperé = $this->estimerProchainDividendeNet($action, $historiqueAnnuel, $beneficeNetEsperé);

        // 3. On enregistre ces prédictions dans la base de données
        $action->forecast()->updateOrCreate(
            ['action_id' => $action->id],
            [
                'rn_previsionnel'   => $beneficeNetEsperé,
                'dnpa_previsionnel' => $dividendeNetEsperé
            ]
        );

        return $action->forecast;
    }

/**
     * LOGIQUE A : Estimer le bénéfice de l'année en cours.
     */
    private function estimerProchainBeneficeNet(Action $action, Collection $historiqueAnnuel)
    {
        $anneeEnCours = now()->year;

        // PRIORITÉ 1 : Si le vrai résultat est déjà publié, on l'utilise directement
        $bilanActuel = $historiqueAnnuel->firstWhere('year', $anneeEnCours);
        if ($bilanActuel && $bilanActuel->resultat_net) {
            return $bilanActuel->resultat_net;
        }

        // PRIORITÉ 2 : Calculer une tendance basée sur le passé et les trimestres

        // A. Calcul de la tendance passée (Moyenne pondérée des 4 dernières années)
        $quatreDernieresAnnees = $historiqueAnnuel->where('year', '<', $anneeEnCours)
                                                ->whereNotNull('resultat_net')
                                                ->take(4);

        $beneficeMoyenDuPasse = 0;
        if ($quatreDernieresAnnees->isNotEmpty()) {
            $totalBeneficesPonderes = 0;
            $totalDesPoids = 0;
            $index = 1;
            foreach ($quatreDernieresAnnees as $bilan) {
                $poids = $this->reglages["weight_x{$index}"] ?? 1;
                $totalBeneficesPonderes += $bilan->resultat_net * $poids;
                $totalDesPoids += $poids;
                $index++;
            }
            $beneficeMoyenDuPasse = $totalDesPoids > 0 ? $totalBeneficesPonderes / $totalDesPoids : 0;
        }

        // B. Calcul de la forme actuelle (Moyenne des performances des derniers trimestres)
        $resultatsTrimestriels = $action->quarterlyResults()
                                       ->where('year', $anneeEnCours)
                                       ->whereIn('trimestre', [1, 2, 3])
                                       ->get();

        $performanceT1 = $resultatsTrimestriels->firstWhere('trimestre', 1)->evolution_rn ?? 0;
        $performanceT2 = $resultatsTrimestriels->firstWhere('trimestre', 2)->evolution_rn ?? 0;
        $performanceT3 = $resultatsTrimestriels->firstWhere('trimestre', 3)->evolution_rn ?? 0;

        $performanceMoyenneTrimestres = ($performanceT1 + $performanceT2 + $performanceT3) / 3;

        // C. Mélange des deux : On mixe le passé (Z) et la forme actuelle (K)
        $poidsFormeActuelle = $this->reglages['coeff_k'] ?? 0.3;
        $poidsPasseHistorique = $this->reglages['coeff_z'] ?? 0.7;

        return ($performanceMoyenneTrimestres * $poidsFormeActuelle) + ($beneficeMoyenDuPasse * $poidsPasseHistorique);
    }

    /**
     * LOGIQUE B : Estimer le dividende que touchera l'investisseur.
     */
    private function estimerProchainDividendeNet(Action $action, Collection $historiqueAnnuel, $beneficeNetEsperé)
    {
        // On analyse les 5 dernières années pour voir si l'entreprise est régulière
        $historiqueRecent = $historiqueAnnuel->whereNotNull('resultat_net')
                                             ->where('resultat_net', '!=', 0)
                                             ->take(5);

        if ($historiqueRecent->count() < 3) return 0;

        // On calcule deux choses :
        // 1. La part du bénéfice reversée (Taux de Distribution - TD)
        // 2. Le montant cash par action (DNPA)
        $analysesSaisies = $historiqueRecent->map(function ($bilan) {
            return [
                'taux_reversé' => $bilan->dividendes_bruts / $bilan->resultat_net,
                'montant_cash' => $bilan->dnpa
            ];
        });

        // On regarde laquelle de ces deux habitudes est la plus stable (Écart-type)
        $stabiliteTaux = $this->calculerEcartType($analysesSaisies->pluck('taux_reversé')->toArray());
        $stabiliteMontant = $this->calculerEcartType($analysesSaisies->pluck('montant_cash')->toArray());

        $moyenneTaux5ans = $analysesSaisies->avg('taux_reversé');
        $moyenneMontant5ans = $analysesSaisies->avg('montant_cash');

        // Coefficient de Variation (plus c'est petit, plus c'est stable)
        $scoreInstabiliteTaux = ($moyenneTaux5ans != 0) ? $stabiliteTaux / $moyenneTaux5ans : 999;
        $scoreInstabiliteMontant = ($moyenneMontant5ans != 0) ? $stabiliteMontant / $moyenneMontant5ans : 999;

        $enveloppeDividendesBruts = 0;
        $dernierBeneficeReel = $historiqueRecent->first()->resultat_net ?? 0;
        $nombreTotalTitres = $historiqueRecent->first()->nombre_titre;

        // On choisit la méthode la plus fiable (celle qui varie le moins)
        if ($scoreInstabiliteTaux < $scoreInstabiliteMontant) {
            // L'entreprise reverse toujours le même POURCENTAGE de son bénéfice
            $enveloppeDividendesBruts = $moyenneTaux5ans * $dernierBeneficeReel;
        } else {
            // L'entreprise reverse toujours environ le même MONTANT par action (+ une petite marge de 10%)
            $enveloppeDividendesBruts = $moyenneMontant5ans * 1.1 * $nombreTotalTitres;
        }

        // On retire l'impôt (IRVM) pour avoir le Net qui va dans la poche de l'investisseur
        $taxeEtat = $this->reglages['irvm'] ?? 0.15;

        if ($nombreTotalTitres == 0) return 0;

        $dividendeGlobalNet = $enveloppeDividendesBruts * (1 - $taxeEtat);
        return $dividendeGlobalNet / $nombreTotalTitres;
    }


    /**
 * Calcule la volatilité (l'écart-type) d'une liste de nombres.
 * En clair : mesure à quel point les chiffres s'éloignent de la moyenne.
 */
private function calculerEcartType(array $listeDeNombres)
{
    $nombreElements = count($listeDeNombres);
    // Si la liste est vide, on ne peut rien calculer
    if ($nombreElements === 0) return 0;
    // 1. Calculer la moyenne de tous les nombres
    $somme = array_sum($listeDeNombres);
    $moyenne = $somme / $nombreElements;
    // 2. Calculer l'écart de chaque nombre par rapport à cette moyenne
    $sommeDesEcartsAuCarre = 0.0;
    foreach ($listeDeNombres as $nombre) {
        // On regarde la distance entre le nombre et la moyenne
        $distanceDeLaMoyenne = (double)$nombre - $moyenne;
        // On multiplie cette distance par elle-même (pour que ce soit toujours positif)
        $sommeDesEcartsAuCarre += $distanceDeLaMoyenne * $distanceDeLaMoyenne;
    }
    // 3. Faire la moyenne de ces écarts et prendre la racine carrée
    $variance = $sommeDesEcartsAuCarre / $nombreElements;
    return sqrt($variance);
}
}
