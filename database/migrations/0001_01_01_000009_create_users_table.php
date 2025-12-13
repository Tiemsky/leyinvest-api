<?php

use App\Enums\PlanEnum;
use App\Enums\RoleEnum;
use App\Models\Country;
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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('google_id')->nullable()->unique();
            $table->foreignIdFor(Country::class)->nullable();
            $table->string('role')->default(RoleEnum::USER->value);
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();
            $table->string('genre')->nullable();
            $table->string('age')->nullable();
            $table->string('situation_professionnelle')->nullable();
            $table->string('numero')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('password')->default(Hash::make('password'));
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->boolean('email_verified')->default(false);
            $table->boolean('registration_completed')->default(false);
            $table->string('avatar')->nullable();
            $table->string('auth_provider')->default('email');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
