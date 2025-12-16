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
        Schema::create('evaluation_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('nom')->unique(); // ex: 'coeff_k', 'irvm', 'weight_x1'
            $table->decimal('value', 10, 4); // ex: 0.3000
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_parameters');
    }
};
