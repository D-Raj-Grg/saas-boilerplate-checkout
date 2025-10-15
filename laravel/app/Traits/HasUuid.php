<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property string $uuid
 */
trait HasUuid
{
    /**
     * Boot the trait.
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function (Model $model): void {
            /** @var Model&HasUuid $model */
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Scope a query to only include models with the given UUID.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeByUuid(Builder $query, string $uuid): Builder
    {
        return $query->where('uuid', $uuid);
    }

    /**
     * Find a model by its UUID.
     *
     * @return static|null
     */
    public static function findByUuid(string $uuid): ?self
    {
        /** @var static|null */
        return static::where('uuid', $uuid)->first();
    }

    /**
     * Find a model by its UUID or fail.
     *
     * @return static
     */
    public static function findByUuidOrFail(string $uuid): self
    {
        /** @var static */
        return static::where('uuid', $uuid)->firstOrFail();
    }
}
