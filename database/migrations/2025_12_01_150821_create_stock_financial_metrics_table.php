<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_financial_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_id')->constrained('actions')->onDelete('cascade');
            $table->year('year');

            // ========== CROISSANCE SECTEUR FINANCIER ==========
            $table->decimal('croissance_pnb', 10, 2)->nullable();
            $table->decimal('croissance_ebit_sf', 10, 2)->nullable();
            $table->decimal('croissance_ebitda_sf', 10, 2)->nullable();
            $table->decimal('croissance_rn_sf', 10, 2)->nullable();
            $table->decimal('croissance_capex_sf', 10, 2)->nullable();
            $table->decimal('moy_croissance_sf', 10, 2)->nullable();

            // ========== CROISSANCE AUTRE SECTEUR ==========
            $table->decimal('croissance_ca', 10, 2)->nullable();
            $table->decimal('croissance_ebit_as', 10, 2)->nullable();
            $table->decimal('croissance_ebitda_as', 10, 2)->nullable();
            $table->decimal('croissance_rn_as', 10, 2)->nullable();
            $table->decimal('croissance_capex_as', 10, 2)->nullable();
            $table->decimal('moy_croissance_as', 10, 2)->nullable();

            // ========== RENTABILITÉ (TOUS SECTEURS) ==========
            $table->decimal('marge_nette', 10, 2)->nullable();
            $table->decimal('marge_ebitda', 10, 2)->nullable();
            $table->decimal('marge_operationnelle', 10, 2)->nullable();
            $table->decimal('roe', 10, 2)->nullable();
            $table->decimal('roa', 10, 2)->nullable();
            $table->decimal('moy_rentabilite', 10, 2)->nullable();

            // ========== RÉMUNÉRATION (TOUS SECTEURS) ==========
            $table->decimal('dnpa_calculated', 10, 2)->nullable();
            $table->decimal('rendement_dividendes', 10, 2)->nullable();
            $table->decimal('taux_distribution', 10, 2)->nullable();
            $table->decimal('moy_remuneration', 10, 2)->nullable();

            // ========== VALORISATION (TOUS SECTEURS) ==========
            $table->decimal('per', 10, 2)->nullable();
            $table->decimal('pbr', 10, 2)->nullable();
            $table->decimal('ratio_ps', 10, 2)->nullable();
            $table->decimal('ev_ebitda', 10, 2)->nullable();
            $table->decimal('cours_cible', 10, 2)->nullable();
            $table->decimal('potentiel_hausse', 10, 2)->nullable();
            $table->decimal('moy_valorisation', 10, 2)->nullable();

            // ========== SOLIDITÉ FINANCIÈRE SECTEUR FINANCIER ==========
            $table->decimal('autonomie_financiere', 10, 2)->nullable();
            $table->decimal('ratio_prets_depots', 10, 2)->nullable();
            $table->decimal('loan_to_deposit', 10, 2)->nullable();
            $table->decimal('endettement_general_sf', 10, 2)->nullable();
            $table->decimal('cout_du_risque_value', 15, 2)->nullable();
            $table->decimal('moy_solidite_sf', 10, 2)->nullable();

            // ========== SOLIDITÉ FINANCIÈRE AUTRE SECTEUR ==========
            $table->decimal('dette_capitalisation', 10, 2)->nullable();
            $table->decimal('endettement_actif', 10, 2)->nullable();
            $table->decimal('endettement_general_as', 10, 2)->nullable();
            $table->decimal('moy_solidite_as', 10, 2)->nullable();

            // ========== METADATA ==========
            $table->boolean('is_financial_sector')->default(false);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            // ========== INDEX ==========
            $table->unique(['action_id', 'year'], 'idx_action_year_metrics');
            $table->index('year', 'idx_year_metrics');
            $table->index('is_financial_sector', 'idx_financial_sector');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_financial_metrics');
    }
};
