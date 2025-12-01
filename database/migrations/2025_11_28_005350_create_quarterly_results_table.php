<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table quarterly_results pour résultats trimestriels
     */
    public function up(): void
    {
        Schema::create('quarterly_results', function (Blueprint $table) {
            $table->id();

            // Relation avec action
            $table->unsignedBigInteger('action_id');
            $table->foreign('action_id')
                ->references('id')
                ->on('actions')
                ->onDelete('cascade');

            // Période
            $table->year('year');
            $table->tinyInteger('quarter')
                ->comment('Trimestre: 1, 2, 3, 4');

            // Données financières trimestrielles
            $table->decimal('produit_net_bancaire', 15, 2)->nullable()
                ->comment('PNB trimestriel (services financiers)');
            $table->decimal('chiffre_affaires', 15, 2)->nullable()
                ->comment('CA trimestriel (autres secteurs)');
            $table->decimal('resultat_net', 15, 2)->nullable()
                ->comment('Résultat net trimestriel');
            $table->decimal('ebit', 15, 2)->nullable()
                ->comment('EBIT trimestriel');
            $table->decimal('ebitda', 15, 2)->nullable()
                ->comment('EBITDA trimestriel');

            // Évolutions vs trimestre précédent
            $table->decimal('evolution_pnb', 10, 2)->nullable()
                ->comment('Évolution PNB vs T-1 (%)');
            $table->decimal('evolution_ca', 10, 2)->nullable()
                ->comment('Évolution CA vs T-1 (%)');
            $table->decimal('evolution_rn', 10, 2)->nullable()
                ->comment('Évolution RN vs T-1 (%)');

            // Évolutions vs année précédente (YoY)
            $table->decimal('evolution_yoy_pnb', 10, 2)->nullable()
                ->comment('Évolution PNB vs même trimestre N-1 (%)');
            $table->decimal('evolution_yoy_ca', 10, 2)->nullable()
                ->comment('Évolution CA vs même trimestre N-1 (%)');
            $table->decimal('evolution_yoy_rn', 10, 2)->nullable()
                ->comment('Évolution RN vs même trimestre N-1 (%)');

            $table->timestamps();

            // Contrainte d'unicité
            $table->unique(['action_id', 'year', 'quarter'], 'uniq_action_year_quarter');

            // Index pour recherches
            $table->index(['action_id', 'year'], 'idx_action_year');
            $table->index(['year', 'quarter'], 'idx_year_quarter');
        });
    }

    /**
     * Annule les modifications
     */
    public function down(): void
    {
        Schema::dropIfExists('quarterly_results');
    }
};
