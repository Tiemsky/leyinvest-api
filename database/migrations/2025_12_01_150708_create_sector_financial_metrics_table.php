<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sector_financial_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('sector_type'); // 'brvm' ou 'classified'
            $table->foreignId('sector_id'); // ID du secteur (brvm_sector_id ou classified_sector_id)
            $table->year('year');
            $table->boolean('is_financial_sector')->default(false);

            // ========== CROISSANCE - MOYENNES ==========
            $table->decimal('croissance_pnb_moy', 10, 2)->nullable();
            $table->decimal('croissance_ca_moy', 10, 2)->nullable();
            $table->decimal('croissance_ebit_moy', 10, 2)->nullable();
            $table->decimal('croissance_ebitda_moy', 10, 2)->nullable();
            $table->decimal('croissance_rn_moy', 10, 2)->nullable();
            $table->decimal('croissance_capex_moy', 10, 2)->nullable();
            $table->decimal('moy_croissance_moy', 10, 2)->nullable();

            // ========== CROISSANCE - ÉCART-TYPES ==========
            $table->decimal('croissance_pnb_ecart_type', 10, 2)->nullable();
            $table->decimal('croissance_ca_ecart_type', 10, 2)->nullable();
            $table->decimal('croissance_ebit_ecart_type', 10, 2)->nullable();
            $table->decimal('croissance_ebitda_ecart_type', 10, 2)->nullable();
            $table->decimal('croissance_rn_ecart_type', 10, 2)->nullable();
            $table->decimal('croissance_capex_ecart_type', 10, 2)->nullable();
            $table->decimal('moy_croissance_ecart_type', 10, 2)->nullable();

            // ========== RENTABILITÉ - MOYENNES ==========
            $table->decimal('marge_nette_moy', 10, 2)->nullable();
            $table->decimal('marge_ebitda_moy', 10, 2)->nullable();
            $table->decimal('marge_operationnelle_moy', 10, 2)->nullable();
            $table->decimal('roe_moy', 10, 2)->nullable();
            $table->decimal('roa_moy', 10, 2)->nullable();
            $table->decimal('moy_rentabilite_moy', 10, 2)->nullable();

            // ========== RENTABILITÉ - ÉCART-TYPES ==========
            $table->decimal('marge_nette_ecart_type', 10, 2)->nullable();
            $table->decimal('marge_ebitda_ecart_type', 10, 2)->nullable();
            $table->decimal('marge_operationnelle_ecart_type', 10, 2)->nullable();
            $table->decimal('roe_ecart_type', 10, 2)->nullable();
            $table->decimal('roa_ecart_type', 10, 2)->nullable();
            $table->decimal('moy_rentabilite_ecart_type', 10, 2)->nullable();

            // ========== RÉMUNÉRATION - MOYENNES ==========
            $table->decimal('dnpa_moy', 10, 2)->nullable();
            $table->decimal('rendement_dividendes_moy', 10, 2)->nullable();
            $table->decimal('taux_distribution_moy', 10, 2)->nullable();
            $table->decimal('moy_remuneration_moy', 10, 2)->nullable();

            // ========== RÉMUNÉRATION - ÉCART-TYPES ==========
            $table->decimal('dnpa_ecart_type', 10, 2)->nullable();
            $table->decimal('rendement_dividendes_ecart_type', 10, 2)->nullable();
            $table->decimal('taux_distribution_ecart_type', 10, 2)->nullable();
            $table->decimal('moy_remuneration_ecart_type', 10, 2)->nullable();

            // ========== VALORISATION - MOYENNES ==========
            $table->decimal('per_moy', 10, 2)->nullable();
            $table->decimal('pbr_moy', 10, 2)->nullable();
            $table->decimal('ratio_ps_moy', 10, 2)->nullable();
            $table->decimal('ev_ebitda_moy', 10, 2)->nullable();
            $table->decimal('moy_valorisation_moy', 10, 2)->nullable();

            // ========== VALORISATION - ÉCART-TYPES ==========
            $table->decimal('per_ecart_type', 10, 2)->nullable();
            $table->decimal('pbr_ecart_type', 10, 2)->nullable();
            $table->decimal('ratio_ps_ecart_type', 10, 2)->nullable();
            $table->decimal('ev_ebitda_ecart_type', 10, 2)->nullable();
            $table->decimal('moy_valorisation_ecart_type', 10, 2)->nullable();

            // ========== SOLIDITÉ - MOYENNES ==========
            $table->decimal('autonomie_financiere_moy', 10, 2)->nullable();
            $table->decimal('ratio_prets_depots_moy', 10, 2)->nullable();
            $table->decimal('loan_to_deposit_moy', 10, 2)->nullable();
            $table->decimal('dette_capitalisation_moy', 10, 2)->nullable();
            $table->decimal('endettement_actif_moy', 10, 2)->nullable();
            $table->decimal('endettement_general_moy', 10, 2)->nullable();
            $table->decimal('cout_du_risque_moy', 15, 2)->nullable();
            $table->decimal('moy_solidite_moy', 10, 2)->nullable();

            // ========== SOLIDITÉ - ÉCART-TYPES ==========
            $table->decimal('autonomie_financiere_ecart_type', 10, 2)->nullable();
            $table->decimal('ratio_prets_depots_ecart_type', 10, 2)->nullable();
            $table->decimal('loan_to_deposit_ecart_type', 10, 2)->nullable();
            $table->decimal('dette_capitalisation_ecart_type', 10, 2)->nullable();
            $table->decimal('endettement_actif_ecart_type', 10, 2)->nullable();
            $table->decimal('endettement_general_ecart_type', 10, 2)->nullable();
            $table->decimal('cout_du_risque_ecart_type', 15, 2)->nullable();
            $table->decimal('moy_solidite_ecart_type', 10, 2)->nullable();

            // ========== METADATA ==========
            $table->integer('companies_count')->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            // ========== INDEX ==========
            $table->unique(['sector_type', 'sector_id', 'year'], 'idx_sector_type_id_year');
            $table->index('year', 'idx_year_sector');
            $table->index('sector_type', 'idx_sector_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sector_financial_metrics');
    }
};
