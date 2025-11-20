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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('nom'); // Gratuit, Pro, Premium
            $table->string('slug')->unique();
            $table->decimal('prix', 8, 2)->default(0);
            $table->string('billing_cycle')->default('monthly'); // monthly, yearly
            $table->json('features'); // Fonctionnalités du plan
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
