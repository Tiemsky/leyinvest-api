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
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->nullable();
            $table->boolean('is_visible')->default(true)->comment('Visible pour les clients');
            $table->integer('trial_days')->default(0);
            $table->timestamps();
            $table->softDeletes();
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
