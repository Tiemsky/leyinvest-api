<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
       /**
     * Les actions que cet utilisateur suit
     */
    public function followedActions(): HasMany{
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
    public function isOtpExpired(): bool{
        return $this->otp_expires_at === null || $this->otp_expires_at->isPast();
    }

    /**
     * Compléter l'inscription
     */
    public function completeRegistration(array $data): bool{
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
    public function hasCompletedRegistration(): bool{
        return $this->registration_completed && $this->password !== null;
    }

       // Relations
       public function subscriptions(){
           return $this->hasMany(Subscription::class);
       }

       public function activeSubscription(){
           return $this->hasOne(Subscription::class)
               ->active()
               ->with('plan')
               ->latest();
       }

       public function currentPlan(): BelongsTo{
           return $this->belongsTo(Plan::class, 'plan_id');
       }

       // Méthodes de vérification
       public function hasActiveSubscription(){
           return $this->activeSubscription()->exists();
       }

       public function onPlan($planSlug): bool
       {
           $subscription = $this->activeSubscription;
           return $subscription && $subscription->plan->slug === $planSlug;
       }

       public function hasFeature($feature)
       {
           $subscription = $this->activeSubscription;

           if (!$subscription) {
               // Plan gratuit par défaut
               $freePlan = Plan::free()->first();
               return $freePlan ? $freePlan->hasFeature($feature) : false;
           }

           return $subscription->plan->hasFeature($feature);
       }

       // Méthodes de gestion d'abonnement
       public function subscribeTo(Plan $plan, array $options = [])
       {
           // Annuler l'abonnement actif
           if ($current = $this->activeSubscription) {
               $current->cancel();
           }

           // Créer nouvelle souscription
           $subscription = $this->subscriptions()->create([
               'plan_id' => $plan->id,
               'status' => $options['trial'] ?? false ? 'trialing' : 'active',
               'trial_ends_at' => $options['trial_ends_at'] ?? null,
               'starts_at' => $options['starts_at'] ?? now(),
               'ends_at' => $options['ends_at'] ?? null,
           ]);

           // Mettre à jour le plan actuel
           $this->update(['current_plan_id' => $plan->id]);

           return $subscription;
       }

       public function cancelSubscription()
       {
           if ($subscription = $this->activeSubscription) {
               $subscription->cancel();
               return true;
           }
           return false;
       }


}
