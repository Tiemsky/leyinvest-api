<?php

use App\Models\Action;
use App\Models\Position;
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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Action::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Position::class)->nullable()->constrained()->nullOnDelete();
            $table->string('nom'); // name = nom de l'employé
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
