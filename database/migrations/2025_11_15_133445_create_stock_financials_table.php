<?php

use App\Models\Action;
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
        Schema::create('stock_financials', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Action::class)->constrained()->onDelete('cascade'); // stock_id = référence vers l'action
            $table->year('year'); // year = année fiscale

            // Nombre de titres (varie chaque année)
            $table->bigInteger('nombre_titre')->nullable(); // total_shares = nombre de titres émis

            // BILAN (en millions)
            $table->decimal('total_immobilisation', 15, 2)->nullable(); // total_immobilisation = immobilisations totales
            $table->decimal('credits_clientele', 15, 2)->nullable(); // credits_clientele = crédits à la clientèle
            $table->decimal('depots_clientele', 15, 2)->nullable(); // depots_clientele = dépôts de la clientèle
            $table->decimal('capitaux_propres', 15, 2)->nullable(); // capitaux_propres = fonds propres/equity
            $table->decimal('dette_totale', 15, 2)->nullable(); // dette_totale = total des dettes
            $table->decimal('total_actif', 15, 2)->nullable(); // total_actif = total de l'actif/assets

            // COMPTE DE RÉSULTAT (en millions)
            $table->decimal('produit_net_bancaire', 15, 2)->nullable(); // produit_net_bancaire = PNB/net banking income
            $table->decimal('ebit', 15, 2)->nullable(); // ebit = résultat d'exploitation/operating income
            $table->decimal('ebitda', 15, 2)->nullable(); // ebitda = résultat brut d'exploitation
            $table->decimal('resultat_avant_impot', 15, 2)->nullable(); // resultat_avant_impot = bénéfice avant impôt
            $table->decimal('resultat_net', 15, 2)->nullable(); // resultat_net = bénéfice net/net income

            // INDICATEURS
            $table->decimal('cout_du_risque', 15, 2)->nullable(); // cout_du_risque = coût du risque/provisions
            $table->decimal('per', 10, 2)->nullable(); // per = Price Earning Ratio (cours/bénéfice)
            $table->decimal('dnpa', 10, 2)->nullable(); // dnpa = Dividende Net Par Action
            $table->decimal('capex', 15, 2)->nullable(); // capex = dépenses d'investissement
            $table->decimal('dividendes_bruts', 15, 2)->nullable(); // dividendes_bruts = dividendes totaux versés

            // COURS DE L'ACTION
            $table->decimal('cours_31_12', 10, 2)->nullable(); // cours_31_12 = cours au 31 décembre/closing price

            $table->timestamps(); // created_at, updated_at = dates de création/modification

            // Contrainte d'unicité: une seule entrée par action par année
            $table->unique(['action_id', 'year']);
            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_financials');
    }
};
