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
        Schema::create('action_daily_snapshots', function (Blueprint $table) {
            $table->id(); // id = identifiant unique
            $table->unsignedBigInteger('action_id'); // action_id = référence vers l'action
            $table->foreign('action_id')->references('id')->on('actions')->onDelete('cascade');

            $table->date('snapshot_date'); // snapshot_date = date de la capture (clé de rotation)
            $table->string('symbole', 10)->index(); // symbole = code boursier (dénormalisé pour perf)
            $table->string('nom'); // nom = nom de l'action

            // Données de marché (market data)
            $table->bigInteger('volume')->default(0); // volume = nombre de titres échangés
            $table->decimal('cours_veille', 10, 2)->nullable(); // cours_veille = cours de la veille
            $table->decimal('cours_ouverture', 10, 2)->nullable(); // cours_ouverture = cours d'ouverture
            $table->decimal('cours_cloture', 10, 2); // cours_cloture = cours de clôture
            $table->decimal('variation', 10, 2)->nullable(); // variation = variation en %

            // Métadonnées
            $table->timestamp('created_at')->useCurrent(); // created_at = date de création

            // Contrainte: une seule snapshot par action par jour
            $table->unique(['action_id', 'snapshot_date'], 'unique_action_snapshot_date');

            // Index pour rotation et requêtes
            $table->index('snapshot_date'); // Pour filtrer par date
            $table->index(['symbole', 'snapshot_date']); // Pour requêtes par action

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_daily_snapshots');
    }
};
