<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model ClassifiedSector - Secteur reclassé personnalisé
 *
 * @property int $id
 * @property string $nom
 * @property string $slug
 * @property string $key
 * @property float|null $variation
 */
class ClassifiedSector extends Model
{
    use HasFactory, HasKey;
    protected $fillable = [
        'nom',
        'slug',
        'key',
        'variation',
    ];

    protected $casts = [
        'variation' => 'decimal:2',
    ];

    /**
     * Relation avec actions
     */
    public function actions(): HasMany{
        return $this->hasMany(Action::class, 'classified_sector_id');
    }

    /**
     * Relation avec benchmarks
     */
    public function benchmarks(): HasMany{
        return $this->hasMany(SectorBenchmark::class, 'classified_sector_id');
    }

    /**
     * Scope pour résolution par slug
     */
    public function scopeBySlug($query, string $slug){
        return $query->where('slug', $slug);
    }

    /**
     * Route key name pour Route Model Binding
     */
    public function getRouteKeyName(): string{
        return 'key';
    }
}
