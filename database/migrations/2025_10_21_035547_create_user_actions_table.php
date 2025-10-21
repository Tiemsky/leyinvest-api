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
        Schema::create('user_actions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('action_id')->constrained('users')->onDelete('cascade');
            $table->decimal('stop_loss', 10, 2)->nullable();
            $table->decimal('take_profit', 10, 2)->nullable();
            $table->timestamps();

            // Empêcher les doublons (un user ne peut suivre qu'une seule fois une action)
            $table->unique(['user_id', 'action_id']);

            // Index pour optimiser les requêtes
            $table->index('user_id');
            $table->index('action_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_actions');
    }
};
