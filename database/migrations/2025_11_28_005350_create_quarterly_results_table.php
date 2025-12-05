<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
        $table->tinyInteger('trimestre')->comment('Trimestre: 1, 2, 3, 4');
        $table->decimal('evolution', 10, 2)->nullable();
        $table->timestamps();

        $table->unique(['action_id', 'year', 'trimestre']);
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
