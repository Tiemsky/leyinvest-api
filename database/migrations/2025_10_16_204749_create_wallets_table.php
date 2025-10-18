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
            $table->string('key')->unique();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->decimal('capital')->default(0);
            $table->decimal('total_value')->default(0);
            $table->decimal('total_gain_loss')->default(0);
            $table->decimal('total_invested')->default(0);
            $table->decimal('rendement')->default(0);
            $table->decimal('rentabilite')->default(0);
            $table->decimal('liquidite')->default(0);
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
