<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'role',
        'capabilities',
        'joined_at',
        'invited_by',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'capabilities' => 'array',
        'role' => WorkspaceRole::class,
    ];

    /**
     * Get the workspace.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who invited this user.
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Check if the user is a manager.
     */
    public function isManager(): bool
    {
        return $this->role === WorkspaceRole::MANAGER;
    }

    /**
     * Check if the user is an editor.
     */
    public function isEditor(): bool
    {
        return $this->role === WorkspaceRole::EDITOR;
    }

    /**
     * Check if the user is a viewer.
     */
    public function isViewer(): bool
    {
        return $this->role === WorkspaceRole::VIEWER;
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(WorkspaceRole $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has at least a specific role level.
     */
    public function hasRoleAtLeast(WorkspaceRole $role): bool
    {
        return $this->role->isAtLeast($role);
    }

    /**
     * Scope to get users with specific role.
     */
    public function scopeWithRole($query, WorkspaceRole $role)
    {
        return $query->where('role', $role->value);
    }

    /**
     * Scope to get manager users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<WorkspaceUser>  $query
     * @return \Illuminate\Database\Eloquent\Builder<WorkspaceUser>
     */
    public function scopeManagers($query)
    {
        return $query->where('role', WorkspaceRole::MANAGER->value);
    }

    /**
     * Scope to get editor users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<WorkspaceUser>  $query
     * @return \Illuminate\Database\Eloquent\Builder<WorkspaceUser>
     */
    public function scopeEditors($query)
    {
        return $query->where('role', WorkspaceRole::EDITOR->value);
    }

    /**
     * Scope to get viewer users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<WorkspaceUser>  $query
     * @return \Illuminate\Database\Eloquent\Builder<WorkspaceUser>
     */
    public function scopeViewers($query)
    {
        return $query->where('role', WorkspaceRole::VIEWER->value);
    }

    /**
     * Get the role display name.
     */
    public function getRoleDisplayName(): string
    {
        return $this->role->label();
    }

    /**
     * Check if the user can perform admin actions.
     */
    public function canPerformAdminActions(): bool
    {
        return $this->role === WorkspaceRole::MANAGER;
    }

    /**
     * Check if the user can invite others.
     */
    public function canInviteUsers(): bool
    {
        return $this->role->canInviteUsers();
    }

    /**
     * Check if the user can manage workspace settings.
     */
    public function canManageWorkspace(): bool
    {
        return $this->role->canManageWorkspace();
    }

    /**
     * Check if the user can delete the workspace.
     */
    public function canDeleteWorkspace(): bool
    {
        // Only organization owners/admins can delete workspaces now
        // This is checked at the organization level
        return false;
    }

    /**
     * Check if the user can remove other users.
     */
    public function canRemoveUsers(): bool
    {
        return $this->role === WorkspaceRole::MANAGER;
    }

    /**
     * Check if the user can change other users' roles.
     */
    public function canChangeUserRoles(): bool
    {
        return $this->role === WorkspaceRole::MANAGER;
    }

    /**
     * Check if user has a specific capability.
     */
    public function hasCapability(string $capability): bool
    {
        if (! $this->capabilities) {
            return false;
        }

        return in_array($capability, $this->capabilities);
    }

    /**
     * Add a capability to the user.
     */
    public function addCapability(string $capability): void
    {
        $capabilities = $this->capabilities ?? [];

        if (! in_array($capability, $capabilities)) {
            $capabilities[] = $capability;
            $this->update(['capabilities' => $capabilities]);
        }
    }

    /**
     * Remove a capability from the user.
     */
    public function removeCapability(string $capability): void
    {
        if (! $this->capabilities) {
            return;
        }

        $capabilities = array_values(array_diff($this->capabilities, [$capability]));
        $this->update(['capabilities' => $capabilities]);
    }
}
