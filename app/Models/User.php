<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasKey, Notifiable, SoftDeletes;

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

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /**
     * Les actions que cet utilisateur suit
     */
    public function followedActions(): HasMany
    {
        return $this->hasMany(UserAction::class, 'user_id');
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
            'country_id' => $data['country_id'],
            'numero' => $data['numero'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'age' => $data['age'] ?? null,
            'genre' => $data['genre'] ?? null,
            'situation_professionnelle' => $data['situation_professionnelle'] ?? null,
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

    // Relations
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->orWhere('status', 'trialing');
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->with('plan.features')
            ->latest();
    }

    public function invoices()
    {
        return $this->hasMany(\App\Models\Invoice::class);
    }

    /**
     * Récupérer le plan actuel de l'utilisateur via sa souscription active
     */
    public function currentPlan()
    {
        return $this->activeSubscription?->plan;
    }

    // Méthodes de vérification
    public function hasActiveSubscription()
    {
        return $this->activeSubscription()->exists();
    }

    /**
     * Vérifier si l'utilisateur est sur un plan spécifique
     */
    public function onPlan($planSlug): bool
    {
        $subscription = $this->activeSubscription;

        return $subscription && $subscription->plan->slug === $planSlug;
    }

    /**
     * Vérifier si l'utilisateur a un plan spécifique
     */
    public function hasFeature(string $feature): bool
    {
        $subscription = $this->activeSubscription;

        if (! $subscription || ! $subscription->isValid()) {
            // Plan gratuit par défaut
            $freePlan = Plan::free()->first();

            return $freePlan ? $freePlan->hasFeature($feature) : false;
        }

        return $subscription->plan->hasFeature($feature);
    }

    /**
     * Récupérer la limite des plans pour l'utilisateur
     */
    public function getFeatureLimit(string $featureKey, string $limitKey = 'limit'): ?int
    {
        $subscription = $this->activeSubscription;

        if (! $subscription || ! $subscription->isValid()) {
            $freePlan = Plan::free()->first();

            return $freePlan?->getFeatureLimit($featureKey, $limitKey);
        }

        return $subscription->plan->getFeatureLimit($featureKey, $limitKey);
    }

    /**
     * Souscrire à un plan (utilise maintenant SubscriptionService)
     *
     * @deprecated Utiliser SubscriptionService::subscribe() à la place
     */
    public function subscribeTo(Plan $plan, array $options = [])
    {
        $subscriptionService = app(\App\Services\SubscriptionService::class);

        return $subscriptionService->subscribe($this, $plan, $options);
    }

    /**
     * Annuler l'abonnement actif
     *
     * @deprecated Utiliser SubscriptionService::cancel() à la place
     */
    public function cancelSubscription(?string $reason = null): bool
    {
        if ($subscription = $this->activeSubscription) {
            $subscriptionService = app(\App\Services\SubscriptionService::class);

            return $subscriptionService->cancel($subscription, $reason);
        }

        return false;
    }
}
