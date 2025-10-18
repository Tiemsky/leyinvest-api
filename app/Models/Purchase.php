<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseFactory> */
    use HasFactory,  HasKey;
    protected $guarded = [];
    protected $casts = [
        'quantite' => 'integer',
        'prix_par_action' => 'decimal:2',
        'montant_achat' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function wallet(): BelongsTo{
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo{
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, int $userId){
        return $query->where('user_id', $userId);
    }

    public function scopeForWallet($query, int $walletId){
        return $query->where('wallet_id', $walletId);
    }

    public function scopeRecent($query){
        return $query->orderBy('created_at', 'desc');
    }

    public function getMontantTotalAttribute(): string{
        return number_format($this->montant_achat, 2, ',', ' ');
    }
}
