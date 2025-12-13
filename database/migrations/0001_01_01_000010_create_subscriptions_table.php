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
            $table->string('key')->unique()->comment('Identifiant unique de la souscription');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('coupon_id')->nullable()->constrained()->onDelete('set null');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable(); // null = illimité (ex: Gratuit)
            $table->enum('status', ['pending', 'trialing', 'active', 'paused', 'expired', 'canceled'])->default('pending');
            $table->string('payment_method')->nullable(); // ex: "manual", "bank_transfer"
            $table->string('transaction_id')->nullable(); // tracker les paiements

            $table->timestamp('trial_ends_at')->nullable()->comment('Fin de la période d\'essai');
            $table->timestamp('canceled_at')->nullable()->comment('Date d\'annulation');
            $table->timestamp('paused_at')->nullable()->comment('Date de pause');

            // Champs de paiement
            $table->string('payment_status')->nullable()->after('payment_method')->comment('pending, paid, failed');
            $table->decimal('amount_paid', 10, 2)->nullable()->comment('Montant payé');
            $table->string('currency', 3)->default('XOF');

            $table->json('metadata')->nullable()->comment('Données supplémentaires');

            $table->text('cancellation_reason')->nullable()->comment('Raison d\'annulation');
            $table->index(['user_id', 'status']);
            $table->index('ends_at');
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
