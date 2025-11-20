<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('action_ratios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_id')->constrained()->onDelete('cascade');
            $table->year('year');

            // === SECTION CROISSANCE (Growth) ===
            $table->decimal('produit_net_bancaire', 10, 2)->nullable(); // croissance_pnb = Croissance du PNB (%)
            $table->decimal('resultat_net', 10, 2)->nullable(); // croissance_rn = Croissance du Résultat Net (%)
            $table->decimal('ebit', 10, 2)->nullable(); // croissance_ebit = Croissance de l'EBIT (%)
            $table->decimal('ebitda', 10, 2)->nullable(); // croissance_ebitda = Croissance de l'EBITDA (%)
            $table->decimal('capex', 10, 2)->nullable(); // croissance_capex = Croissance des investissements (%)
            $table->decimal('avg_croissance', 10, 2)->nullable(); // croissance_moy_3ans = Croissance moyenne 3 ans (%)

            // === SECTION RENTABILITÉ (Profitability) ===
            $table->decimal('marge_nette', 10, 2)->nullable(); // marge_nette = Marge Nette (RN/PNB) %
            $table->decimal('marge_ebitda', 10, 2)->nullable(); // marge_ebitda = Marge EBITDA %
            $table->decimal('marge_operationnelle', 10, 2)->nullable(); // marge_operationnelle = Marge Opérationnelle (EBIT/PNB) %
            $table->decimal('roe', 10, 2)->nullable(); // roe = Return on Equity (RN/Capitaux Propres) %
            $table->decimal('roa', 10, 2)->nullable(); // roa = Return on Assets (RN/Total Actif) %
            $table->decimal('avg_rentabilite', 10, 2)->nullable(); // rentabilite_moyenne = Moyenne des marges %

            // === SECTION RÉMUNÉRATION (Shareholder Return) ===
            $table->decimal('dnpa', 10, 2)->nullable(); // dnpa = Dividende Net Par Action
            $table->decimal('rendement_dividende', 10, 2)->nullable(); // rendement_dividende = Rendement du dividende %
            $table->decimal('taux_distribution', 10, 2)->nullable(); // taux_distribution = Taux de distribution (Div/RN) %
            $table->decimal('avg_remuneration', 10, 2)->nullable(); // rendement_moy = Rendement moyen %

            // === SOLIDITÉ FINANCIÈRE ===
            $table->decimal('autonomie_financiere', 10, 2)->nullable(); // taux_endettement = Dette/Actif %
            $table->decimal('endettement_sur_actif', 10, 2)->nullable(); // ratio_fonds_propres = Capitaux Propres/Actif %
            $table->decimal('pret_sur_depot_et_capitaux_propre', 10, 2)->nullable(); // ratio_cout_risque = Coût du Risque/PNB %
            $table->decimal('loan_to_deposit', 10, 2)->nullable(); // per = Price Earning Ratio
            $table->decimal('endettement_general', 10, 2)->nullable(); // per = Price Earning Ratio
            $table->decimal('cout_risque', 10, 2)->nullable(); // per = Price Earning Ratio
            $table->decimal('avg_solidite_financiere', 10, 2)->nullable(); // per = Price Earning Ratio

            // === VALORISATION ===
            $table->decimal('per', 15, 2)->nullable(); // capitalisation = Capitalisation boursière (millions)
            $table->decimal('pbr', 10, 2)->nullable(); // valeur_comptable_action = Capitaux propres / Nombre titres
            $table->decimal('ratio_ps', 10, 2)->nullable(); // price_to_book = Cours / Valeur comptable
            $table->decimal('cours_cible', 10, 2)->nullable(); // price_to_book = Cours / Valeur comptable
            $table->decimal('avg_valorisation', 10, 2)->nullable(); // price_to_book = Cours / Valeur comptable

            // Métadonnées
            $table->timestamp('calculated_at')->nullable(); // calculated_at = date de calcul
            $table->timestamps();

            $table->unique(['action_id', 'year']);
            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_ratios');
    }
};
