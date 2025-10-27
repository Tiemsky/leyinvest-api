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
        Schema::create('boc_indicators', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('date_rapport');
            $table->decimal('taux_rendement_moyen');
            $table->decimal('per_moyen');
            $table->decimal('taux_rentabilite_moyen');
            $table->decimal('prime_risque_marche');
            $table->string('source_pdf');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boc_indicators');
    }
};
