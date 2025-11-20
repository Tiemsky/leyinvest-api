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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable(); // null = illimité (ex: Gratuit)
            $table->string('status')->default('active'); // active, expired, canceled
            $table->string('payment_method')->nullable(); // ex: "manual", "bank_transfer"
            $table->string('transaction_id')->nullable(); // tracker les paiements
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
