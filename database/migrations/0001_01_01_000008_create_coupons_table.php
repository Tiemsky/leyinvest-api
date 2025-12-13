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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Code du coupon');
            $table->string('type')->default('percentage')->comment('percentage ou fixed');
            $table->decimal('value', 10, 2)->comment('Valeur de la réduction');
            $table->decimal('max_discount', 10, 2)->nullable()->comment('Réduction maximale (pour percentage)');
            $table->integer('max_uses')->nullable()->comment('Nombre max d\'utilisations (null = illimité)');
            $table->integer('times_used')->default(0)->comment('Nombre de fois utilisé');
            $table->timestamp('starts_at')->nullable()->comment('Date de début de validité');
            $table->timestamp('expires_at')->nullable()->comment('Date d\'expiration');
            $table->boolean('is_active')->default(true);
            $table->json('applicable_plans')->nullable()->comment('IDs des plans applicables (null = tous)');
            $table->json('metadata')->nullable()->comment('Métadonnées additionnelles');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
