<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Subscription;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CouponService
{
    /**
     * Valider un code coupon
     */
    public function validate(string $code, ?int $planId = null, ?int $userId = null): array{
        $coupon = Coupon::where('code', strtoupper($code))->first();

        if (!$coupon) {
            throw new NotFoundHttpException('Code coupon invalide.');
        }
        // 1. Validation Générale (Actif, Dates, Utilisation Globale)
        if (!$coupon->isValid()) {
            return [
                'valid' => false,
                'message' => $this->getInvalidReason($coupon),
                'coupon' => $coupon,
            ];
        }

        // 2. Validation Spécifique au Plan
        if ($planId && !$coupon->isApplicableToPlan($planId)) {
            return [
                'valid' => false,
                'message' => 'Ce coupon n\'est pas applicable à ce plan.',
                'coupon' => $coupon,
            ];
        }

        // 3. Validation Spécifique à l'Utilisateur (Si une limite par utilisateur existe)
        if ($userId && $coupon->hasUsageLimitPerUser() && $this->hasUserUsedCoupon($userId, $coupon->id)) {
            // NOTE: hasUsageLimitPerUser est une méthode que vous devez implémenter sur votre modèle Coupon
            return [
                'valid' => false,
                'message' => 'Vous avez déjà utilisé ce coupon.',
                'coupon' => $coupon,
            ];
        }

        return [
            'valid' => true,
            'coupon' => $coupon,
            'message' => 'Coupon valide!',
        ];
    }


    /**
     * Calculer la réduction pour un montant
     */
    public function calculateDiscount(Coupon $coupon, float $amount): array{
        $discount = $coupon->calculateDiscount($amount);
        $finalAmount = max(0, $amount - $discount);

        return [
            'original_amount' => $amount,
            'discount' => $discount,
            'final_amount' => $finalAmount,
            'discount_percentage' => $amount > 0 ? round(($discount / $amount) * 100, 2) : 0,
        ];
    }

    /**
     * Appliquer un coupon à un montant
     */
    public function apply(string $code, float $amount, ?int $planId = null): array{
        $validation = $this->validate($code, $planId);

        if (!$validation['valid']) {
            return $validation;
        }

        $coupon = $validation['coupon'];
        $calculation = $this->calculateDiscount($coupon, $amount);

        return array_merge($validation, $calculation);
    }

    /**
     * Créer un nouveau coupon
     */
    public function create(array $data): Coupon{
        // Assurer que le code est en majuscules
        $data['code'] = strtoupper($data['code']);

        // Valider que le code n'existe pas déjà
        if (Coupon::where('code', $data['code'])->exists()) {
            throw new \Exception('Ce code coupon existe déjà.');
        }
        return Coupon::create($data);
    }

    /**
     * Désactiver un coupon
     */
    public function deactivate(Coupon $coupon): bool{
        return $coupon->update(['is_active' => false]);
    }

    /**
     * Activer un coupon
     */
    public function activate(Coupon $coupon): bool{
        return $coupon->update(['is_active' => true]);
    }

    /**
     * Obtenir les statistiques d'utilisation d'un coupon
     */
    public function getUsageStats(Coupon $coupon): array{
        $subscriptions = $coupon->subscriptions()->count();
        $totalRevenue = $coupon->subscriptions()->sum('amount_paid');
        $totalDiscount = $coupon->subscriptions()
            ->get()
            ->sum(function ($subscription) {
                return $subscription->metadata['discount'] ?? 0;
            });

        return [
            'times_used' => $coupon->times_used,
            'max_uses' => $coupon->max_uses,
            'usage_percentage' => $coupon->max_uses ?
                round(($coupon->times_used / $coupon->max_uses) * 100, 2) : null,
            'remaining_uses' => $coupon->max_uses ?
                max(0, $coupon->max_uses - $coupon->times_used) : 'Illimité',
            'total_subscriptions' => $subscriptions,
            'total_revenue' => $totalRevenue,
            'total_discount_given' => $totalDiscount,
            'is_valid' => $coupon->isValid(),
        ];
    }

    /**
     * Obtenir la raison pour laquelle un coupon est invalide
     */
    protected function getInvalidReason(Coupon $coupon): string{
        if (!$coupon->is_active) {
            return 'Ce coupon a été désactivé.';
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return 'Ce coupon n\'est pas encore actif.';
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return 'Ce coupon a expiré.';
        }

        if ($coupon->max_uses && $coupon->times_used >= $coupon->max_uses) {
            return 'Ce coupon a atteint sa limite d\'utilisation.';
        }

        return 'Ce coupon n\'est pas valide.';
    }

    /**
     * Générer un code coupon unique
     */
    public function generateUniqueCode(int $length = 8): string{
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length));
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }

    /**
     * Vérifier si un utilisateur a déjà utilisé un coupon
     */
    public function hasUserUsedCoupon(int $userId, int $couponId): bool{
        return Subscription::where('user_id', $userId)
            ->where('coupon_id', $couponId)
            ->exists();
    }
}
