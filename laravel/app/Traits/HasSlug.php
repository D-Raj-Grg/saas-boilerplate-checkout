<?php

namespace App\Traits;

use Illuminate\Support\Str;

/**
 * @property string $slug
 * @property string $name
 * @property int|null $workspace_id
 * @property int|null $organization_id
 *
 * @method bool isDirty($attribute = null)
 * @method bool exists()
 */
trait HasSlug
{
    /**
     * Boot the trait.
     */
    protected static function bootHasSlug(): void
    {
        static::creating(function ($model): void {
            /** @var \Illuminate\Database\Eloquent\Model&\App\Traits\HasSlug $model */
            if (empty($model->slug) && ! empty($model->name)) {
                $model->slug = $model->generateUniqueSlug($model->name);
            }
        });

        static::updating(function ($model): void {
            /** @var \Illuminate\Database\Eloquent\Model&\App\Traits\HasSlug $model */
            // Regenerate slug if name changed and slug is auto-generated
            if ($model->isDirty('name') && ! $model->isDirty('slug')) {
                $model->slug = $model->generateUniqueSlug($model->name);
            }
        });
    }

    /**
     * Generate a unique slug.
     */
    public function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);

        // Generate time-based random suffix (8 characters)
        // Using timestamp + random ensures uniqueness and sortability
        do {
            $timestamp = Str::substr((string) time(), -4); // Last 4 digits of timestamp
            $random = strtolower(Str::random(4)); // 4 random characters
            $slug = "{$baseSlug}-{$timestamp}{$random}";
        } while ($this->slugExists($slug));

        return $slug;
    }

    /**
     * Check if slug exists.
     */
    protected function slugExists(string $slug): bool
    {
        $query = static::where('slug', $slug);

        // Exclude current model if updating
        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        return $query->exists();
    }

    /**
     * Find by slug.
     *
     * @return static|null
     */
    public static function findBySlug(string $slug)
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Find by slug or fail.
     *
     * @return static
     */
    public static function findBySlugOrFail(string $slug)
    {
        return static::where('slug', $slug)->firstOrFail();
    }
}
