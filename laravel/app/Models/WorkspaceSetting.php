<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $workspace_id
 * @property array<string, mixed> $settings
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class WorkspaceSetting extends Model
{
    /** @use HasFactory<\Database\Factories\WorkspaceSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    protected $hidden = [
        'id',
        'workspace_id',
    ];

    /**
     * Get the workspace that owns the settings.
     *
     * @return BelongsTo<Workspace, WorkspaceSetting>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
