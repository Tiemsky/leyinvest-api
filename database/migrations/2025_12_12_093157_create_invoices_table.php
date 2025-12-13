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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('invoice_number')->unique()->comment('Numéro de facture');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('coupon_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('subtotal', 10, 2)->comment('Montant avant réduction');
            $table->decimal('discount', 10, 2)->default(0)->comment('Montant de la réduction');
            $table->decimal('tax', 10, 2)->default(0)->comment('Montant des taxes');
            $table->decimal('total', 10, 2)->comment('Montant total');
            $table->string('currency', 3)->default('XOF');
            $table->enum('status', ['draft', 'pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->timestamp('issued_at')->comment('Date d\'émission');
            $table->timestamp('due_at')->nullable()->comment('Date d\'échéance');
            $table->timestamp('paid_at')->nullable()->comment('Date de paiement');
            $table->json('metadata')->nullable()->comment('Données de paiement, etc.');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
