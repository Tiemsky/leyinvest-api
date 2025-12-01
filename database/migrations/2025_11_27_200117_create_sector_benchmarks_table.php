<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table sector_benchmarks
     *
     * 2 Types de benchmarks:
     * - secteur_brvm: Moyenne par secteur BRVM (brvm_sector_id NOT NULL)
     * - secteur_reclasse: Moyenne par secteur reclassé (classified_sector_id NOT NULL)
     */
    public function up(): void
    {
        Schema::create('sector_benchmarks', function (Blueprint $table) {
            $table->id();

            // Relations sectorielles (mutuellement exclusives)
            $table->unsignedBigInteger('brvm_sector_id')->nullable();
            $table->unsignedBigInteger('classified_sector_id')->nullable();

            // Foreign keys
            $table->foreign('brvm_sector_id')
                ->references('id')
                ->on('brvm_sectors')
                ->onDelete('cascade');

            $table->foreign('classified_sector_id')
                ->references('id')
                ->on('classified_sectors')
                ->onDelete('cascade');

            // Période
            $table->year('year');

            // Horizon d'investissement
            $table->enum('horizon', [
                'court_terme',
                'moyen_terme',
                'long_terme'
            ]);

            // Type de benchmark
            $table->enum('type', [
                'secteur_brvm',      // Utilise brvm_sector_id
                'secteur_reclasse'   // Utilise classified_sector_id
            ]);

            // Indicateurs - Croissance
            $table->json('croissance_avg')->nullable()
                ->comment('Moyennes des indicateurs de croissance');
            $table->json('croissance_std')->nullable()
                ->comment('Écarts-types des indicateurs de croissance');

            // Indicateurs - Rentabilité
            $table->json('rentabilite_avg')->nullable()
                ->comment('Moyennes des indicateurs de rentabilité');
            $table->json('rentabilite_std')->nullable()
                ->comment('Écarts-types des indicateurs de rentabilité');

            // Indicateurs - Rémunération
            $table->json('remuneration_avg')->nullable()
                ->comment('Moyennes des indicateurs de rémunération');
            $table->json('remuneration_std')->nullable()
                ->comment('Écarts-types des indicateurs de rémunération');

            // Indicateurs - Valorisation
            $table->json('valorisation_avg')->nullable()
                ->comment('Moyennes des indicateurs de valorisation');
            $table->json('valorisation_std')->nullable()
                ->comment('Écarts-types des indicateurs de valorisation');

            // Indicateurs - Solidité Financière
            $table->json('solidite_avg')->nullable()
                ->comment('Moyennes des indicateurs de solidité financière');
            $table->json('solidite_std')->nullable()
                ->comment('Écarts-types des indicateurs de solidité financière');

            // Métadonnées
            $table->timestamp('calculated_at')
                ->comment('Date du dernier calcul');
            $table->timestamps();

            // Index composites pour performances
            $table->index(['brvm_sector_id', 'year', 'horizon'], 'idx_brvm_year_horizon');
            $table->index(['classified_sector_id', 'year', 'horizon'], 'idx_classified_year_horizon');
            $table->index(['type', 'year'], 'idx_type_year');
        });

        // Ajouter contrainte CHECK via raw SQL
        DB::statement('
            ALTER TABLE sector_benchmarks ADD CONSTRAINT chk_sector_exclusivity CHECK (
                (type = "secteur_brvm" AND brvm_sector_id IS NOT NULL AND classified_sector_id IS NULL)
                OR
                (type = "secteur_reclasse" AND classified_sector_id IS NOT NULL AND brvm_sector_id IS NULL)
            )
        ');
    }

    /**
     * Annule les modifications
     */
    public function down(): void
    {
        Schema::dropIfExists('sector_benchmarks');
    }
};
