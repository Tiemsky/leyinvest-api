<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialNews extends Model
{
    use HasFactory, HasKey;

    protected $table = 'financial_news';

    protected $fillable = [
        'key',
        'company',
        'title',
        'pdf_url',
        'published_at',
        'source',
    ];

    protected $casts = [
        'published_at' => 'date',
    ];

    /**
     * Pour le Route Model Binding (ex: /news/{key})
     */
    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /* -----------------------------------------------------------------
     |  Scopes (Filtres pour les requêtes)
     | -----------------------------------------------------------------
     */

    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('published_at', '>=', now()->subDays($days));
    }

    public function scopeByCompany(Builder $query, ?string $company): Builder
    {
        return $company
            ? $query->where('company', 'LIKE', "%{$company}%")
            : $query;
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', "%{$search}%")
              ->orWhere('company', 'LIKE', "%{$search}%");
        });
    }

    /* -----------------------------------------------------------------
     |  Accessors & Helpers
     | -----------------------------------------------------------------
     */

    /**
     * Vérifie si le fichier est stocké localement ou est une URL distante.
     */
    public function isLocalFile(): Attribute
    {
        return Attribute::make(
            get: fn () => !str_starts_with($this->pdf_url, 'http')
        );
    }
}
