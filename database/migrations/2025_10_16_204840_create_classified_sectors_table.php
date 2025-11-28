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
        Schema::create('classified_sectors', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('nom');
            $table->string('slug')->unique();
            $table->decimal('variation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classified_sectors');
    }
};
