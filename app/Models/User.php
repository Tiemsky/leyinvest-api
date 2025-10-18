<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\HasKey;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasKey, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
        'otp_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'otp_expires_at' => 'datetime',
            'email_verified' => 'boolean',
            'registration_completed' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function getRouteKeyName(): string{
        return 'key';
    }

    public function wallet(): HasOne{
        return $this->hasOne(Wallet::class);
    }

    public function sales(): HasMany{
        return $this->hasMany(Sale::class);
    }

    public function purchases(): HasMany{
        return $this->hasMany(Purchase::class);
    }

    /**
     * Générer un code OTP
     */
    public function generateOtpCode(): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        return $otp;
    }

    /**
     * Vérifier le code OTP
     */
    public function verifyOtpCode(string $code): bool
    {
        if (
            $this->otp_code === $code &&
            $this->otp_expires_at &&
            $this->otp_expires_at->isFuture()
        ) {
            $this->update([
                'email_verified' => true,
                'otp_code' => null,
                'otp_expires_at' => null,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Vérifier si l'OTP a expiré
     */
    public function isOtpExpired(): bool
    {
        return $this->otp_expires_at === null || $this->otp_expires_at->isPast();
    }

    /**
     * Compléter l'inscription
     */
    public function completeRegistration(array $data): bool
    {
        return $this->update([
            'password' => $data['password'],
            'country' => $data['country'] ?? null,
            'phone' => $data['phone'] ?? null,
            'registration_completed' => true,
        ]);
    }

    /**
     * Vérifier si l'inscription est complète
     */
    public function hasCompletedRegistration(): bool
    {
        return $this->registration_completed && $this->password !== null;
    }


}
