<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->decimal('total_value', 15, 2)->default(0);
            $table->decimal('total_gain_loss', 15, 2)->default(0);
            $table->decimal('total_invested', 15, 2)->default(0);
            $table->decimal('rendement', 5, 2)->default(0);
            $table->decimal('rentabilite', 5, 2)->default(0);
            $table->decimal('liquidite', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
