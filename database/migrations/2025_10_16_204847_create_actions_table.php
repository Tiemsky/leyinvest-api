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
        $table->string('nom', 100);
            $table->string('volume', 100);
            $table->decimal('cours_veille', 15, 2)->default(0);
            $table->decimal('cours_ouverture', 15, 2)->default(0);
            $table->decimal('cours_cloture', 15, 2)->default(0);
            $table->decimal('variation', 5, 2)->default(0);
            $table->string('categorie', 50)->nullable();
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
