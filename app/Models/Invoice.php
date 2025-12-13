<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasKey, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'user_id',
        'subscription_id',
        'coupon_id',
        'subtotal',
        'discount',
        'tax',
        'total',
        'currency',
        'status',
        'issued_at',
        'due_at',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relations
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Scopes
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('due_at', '<', now());
    }

    /**
     * Vérifications d'état
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' &&
            $this->due_at &&
            $this->due_at->isPast();
    }

    /**
     * Marquer comme payée
     */
    public function markAsPaid(array $paymentData = []): bool
    {
        return $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], [
                'payment_data' => $paymentData,
            ]),
        ]);
    }

    /**
     * Marquer comme échouée
     */
    public function markAsFailed(string $reason = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'metadata' => array_merge($this->metadata ?? [], [
                'failure_reason' => $reason,
                'failed_at' => now()->toISOString(),
            ]),
        ]);
    }

    /**
     * Générer le numéro de facture
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = now()->year;
        $month = now()->format('m');

        $lastInvoice = self::whereYear('created_at', $year)
            ->whereMonth('created_at', now()->month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, -4)) + 1 : 1;

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $sequence);
    }


    public function scopeApplyFilters(\Illuminate\Database\Eloquent\Builder $query, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['from_date'])) {
            $query->where('issued_at', '>=', $filters['from_date']);
        }
        if (isset($filters['to_date'])) {
            $query->where('issued_at', '<=', $filters['to_date']);
        }
        return $query;
    }
}
