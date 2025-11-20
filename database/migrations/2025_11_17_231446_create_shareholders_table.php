<?php

use App\Models\Action;
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
        Schema::create('shareholders', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Action::class)->nullable()->constrained()->nullOnDelete();
            $table->string('nom'); // name = nom de l'actionnaire
            $table->decimal('pourcentage', 5, 2)->default(0);
            $table->integer('rang')->default(0); // rank = rang/ordre d'affichage (du plus gros au plus petit)
            $table->timestamps(); // created_at, updated_at = dates de création/modification

            $table->index(['action_id', 'rang']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shareholders');
    }
};
