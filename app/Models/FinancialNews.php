<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinancialNews extends Model
{
    use HasFactory, HasKey;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'financial_news';

    /**
     * Les attributs assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'company',
        'title',
        'pdf_url',
        'published_at',
        'source',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published_at' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Les attributs qui doivent être cachés pour les tableaux.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Les attributs ajoutés au modèle.
     *
     * @var array<int, string>
     */
    protected $appends = [];

    public function getRouteKeyName(): string{
        return 'key';
    }

    /**
     * Scope pour filtrer par source
     *
     * @param Builder $query
     * @param string $source
     * @return Builder
     */
    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    /**
     * Scope pour filtrer par période (X derniers jours)
     *
     * @param Builder $query
     * @param int $days
     * @return Builder
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('published_at', '>=', now()->subDays($days));
    }

    /**
     * Scope pour filtrer par société (recherche partielle)
     *
     * @param Builder $query
     * @param string|null $company
     * @return Builder
     */
    public function scopeByCompany(Builder $query, ?string $company): Builder
    {
        if ($company) {
            return $query->where('company', 'LIKE', "%{$company}%");
        }
        return $query;
    }

    /**
     * Scope pour filtrer par période personnalisée
     *
     * @param Builder $query
     * @param Carbon|string $from
     * @param Carbon|string|null $to
     * @return Builder
     */
    public function scopeBetweenDates(Builder $query, $from, $to = null): Builder
    {
        $query->whereDate('published_at', '>=', $from);

        if ($to) {
            $query->whereDate('published_at', '<=', $to);
        }

        return $query;
    }

    /**
     * Scope pour rechercher dans le titre et la société
     *
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', "%{$search}%")
              ->orWhere('company', 'LIKE', "%{$search}%")
              ->orWhere('key', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Scope pour les actualités publiées
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    /**
     * Vérifie si l'actualité a un PDF
     *
     * @return bool
     */
    public function hasPdf(): bool
    {
        return !empty($this->pdf_url);
    }

    /**
     * Retourne l'âge de l'actualité en jours
     *
     * @return int|null
     */
    public function getAgeInDays(): ?int
    {
        return $this->published_at?->diffInDays(now());
    }

    /**
     * Vérifie si l'actualité est récente (moins de 7 jours)
     *
     * @return bool
     */
    public function isRecent(): bool
    {
        if (!$this->published_at) {
            return false;
        }

        return $this->published_at->isAfter(now()->subDays(7));
    }

    /**
     * Boot du modèle pour les événements
     */
    protected static function boot()
    {
        parent::boot();

        // Nettoyer le cache lors de la création/mise à jour
        static::saved(function () {
            \Cache::tags(['financial_news'])->flush();
        });

        static::deleted(function () {
            \Cache::tags(['financial_news'])->flush();
        });
    }
}
