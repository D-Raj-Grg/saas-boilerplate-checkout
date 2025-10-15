<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait Trackable
{
    /**
     * Boot the trait.
     */
    protected static function bootTrackable(): void
    {
        static::creating(function (Model $model): void {
            if (Auth::check() && $model->hasCreatedByField()) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function (Model $model): void {
            if (Auth::check() && $model->hasUpdatedByField()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    /**
     * Check if model has created_by field.
     */
    protected function hasCreatedByField(): bool
    {
        return in_array('created_by', $this->fillable) ||
               array_key_exists('created_by', $this->attributes);
    }

    /**
     * Check if model has updated_by field.
     */
    protected function hasUpdatedByField(): bool
    {
        return in_array('updated_by', $this->fillable) ||
               array_key_exists('updated_by', $this->attributes);
    }

    /**
     * Get the user who created the model.
     */
    public function creator()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    /**
     * Get the user who last updated the model.
     */
    public function updater()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by');
    }

    /**
     * Scope to filter by creator.
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope to filter by updater.
     */
    public function scopeUpdatedBy($query, $userId)
    {
        return $query->where('updated_by', $userId);
    }
}
