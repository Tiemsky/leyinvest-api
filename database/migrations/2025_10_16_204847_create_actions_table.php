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
        Schema::create('actions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('symbole', 10)->unique();
            $table->string('nom');
            $table->string('volume');
            $table->decimal('cours_veille')->default(0);
            $table->decimal('cours_ouverture')->default(0);
            $table->decimal('cours_cloture')->default(0);
            $table->decimal('variation')->default(0);
            $table->string('categorie')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actions');
    }
};
