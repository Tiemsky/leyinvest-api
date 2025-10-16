<?php
declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;

trait HasKey
{
    /**
     * Boot the trait and register the creating event.
     */
    protected static function bootHasKey(): void
    {
        static::creating(function (Model $model): void {
            if (empty($model->key)) {
                $model->key = static::generateUniqueKey($model);
            }
        });

        // Handle duplicate key exceptions on save
        static::saving(function (Model $model): void {
            if ($model->isDirty('key') && !$model->exists) {
                static::ensureKeyIsUnique($model);
            }
        });
    }

    /**
     * Generate a unique key for the model.
     */
    protected static function generateUniqueKey(Model $model, int $attempt = 0): string
    {
        $prefix = static::getKeyPrefix($model);
        $keyLength = config('key.length', 16); // Default to 16 for better uniqueness
        $maxAttempts = config('key.max_attempts', 10);

        // Generate random key
        $key = "{$prefix}_" . Str::random($keyLength);

        // Check if key exists
        $exists = $model->newQueryWithoutScopes()->where('key', $key)->exists();
        if (!$exists) {
            return $key;
        }

        // Retry if collision detected
        if ($attempt < $maxAttempts) {
            return static::generateUniqueKey($model, $attempt + 1);
        }

        // Ultimate fallback: add microtime for guaranteed uniqueness
        return "{$prefix}_" . Str::random($keyLength) . '_' . str_replace('.', '', microtime(true));
    }

    /**
     * Get the key prefix from the model class name.
     */
    protected static function getKeyPrefix(Model $model): string
    {
        // Check if model has custom prefix method
        if (method_exists($model, 'getKeyPrefixAttribute')) {
            return $model->getKeyPrefixAttribute();
        }

        // Default: first 3 letters of class name
        return strtolower(substr(class_basename($model), 0, 3));
    }

    /**
     * Ensure the key is unique by regenerating if necessary.
     */
    protected static function ensureKeyIsUnique(Model $model): void
    {
        $originalKey = $model->key;
        $attempts = 0;
        $maxAttempts = config('key.max_attempts', 10);

        while ($attempts < $maxAttempts) {
            try {
                // Check uniqueness in database
                if (!$model->newQueryWithoutScopes()->where('key', $model->key)->exists()) {
                    return;
                }

                // Regenerate if exists
                $model->key = static::generateUniqueKey($model);
                $attempts++;
            } catch (QueryException $e) {
                // If it's a duplicate key error, retry
                if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                    $model->key = static::generateUniqueKey($model);
                    $attempts++;
                } else {
                    throw $e;
                }
            }
        }

        // If we've exhausted attempts, throw exception
        throw new \RuntimeException(
            "Unable to generate unique key for " . get_class($model) . " after {$maxAttempts} attempts."
        );
    }

    /**
     * Get the route key for the model.
     * This allows you to use the key in routes instead of ID.
     */
    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /**
     * Scope a query to find by key.
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }
}
