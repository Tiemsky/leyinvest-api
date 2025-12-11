<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Action;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quarterly_results', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Action::class)->constrained()->onDelete('cascade');
            $table->year('year')->comment('Année fiscale');
            $table->tinyInteger('trimestre')->comment('Trimestre: 1, 2, 3, 4');

            // Chiffre d'Affaires
            $table->decimal('chiffre_affaires', 15, 2)->nullable()->comment('CA du trimestre en millions');
            $table->decimal('evolution_ca', 10, 2)->nullable()->comment('Evolution du CA en %');

            // Résultat Net
            $table->decimal('resultat_net', 15, 2)->nullable()->comment('RN du trimestre en millions');
            $table->decimal('evolution_rn', 10, 2)->nullable()->comment('Evolution du RN en %');

            $table->timestamps();


            // Contraintes et index
            $table->index(['action_id', 'year'], 'quarterly_results_action_year_index');
            $table->index('trimestre', 'quarterly_results_trimestre_index');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quarterly_results');
    }
};
