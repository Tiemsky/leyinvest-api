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

        $table->unsignedBigInteger('action_id');
        $table->foreign('action_id')
            ->references('id')
            ->on('actions')
            ->onDelete('cascade');

        $table->year('year');
        $table->tinyInteger('quarter')
            ->comment('Trimestre: 1, 2, 3, 4');

        $table->decimal('produit_net_bancaire', 15, 2)->nullable();
        $table->decimal('chiffre_affaires', 15, 2)->nullable();
        $table->decimal('resultat_net', 15, 2)->nullable();
        $table->decimal('ebit', 15, 2)->nullable();
        $table->decimal('ebitda', 15, 2)->nullable();

        $table->decimal('evolution_pnb', 10, 2)->nullable();
        $table->decimal('evolution_ca', 10, 2)->nullable();
        $table->decimal('evolution_rn', 10, 2)->nullable();

        $table->decimal('evolution_yoy_pnb', 10, 2)->nullable();
        $table->decimal('evolution_yoy_ca', 10, 2)->nullable();
        $table->decimal('evolution_yoy_rn', 10, 2)->nullable();

        $table->timestamps();

        $table->unique(['action_id', 'year', 'quarter'], 'uniq_action_year_quarter');
    });

    // Safe index creation
    DB::statement('CREATE INDEX IF NOT EXISTS idx_action_year ON quarterly_results (action_id, year)');
    DB::statement('CREATE INDEX IF NOT EXISTS idx_year_quarter ON quarterly_results (year, quarter)');
}

    /**
     * Annule les modifications
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_action_year');
        DB::statement('DROP INDEX IF EXISTS idx_year_quarter');
        Schema::dropIfExists('quarterly_results');
    }
};
