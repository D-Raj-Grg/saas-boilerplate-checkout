<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use App\Traits\HasSlug;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $organization_id
 * @property string $name
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Workspace extends Model
{
    use HasFactory;
    use HasSlug;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'description',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    protected $hidden = [
        'id',
        'organization_id',
    ];

    /**
     * Get the organization that owns the workspace.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the users that belong to the workspace.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_users')
            ->withPivot(['role', 'capabilities', 'joined_at', 'invited_by'])
            ->withTimestamps();
    }

    /**
     * Get the invitations for the workspace.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * Get pending invitations for the workspace.
     */
    public function pendingInvitations(): HasMany
    {
        return $this->invitations()->whereNull('accepted_at')->where('expires_at', '>', now());
    }

    /**
     * Check if the user has access to this workspace.
     */
    public function hasUser(User $user): bool
    {
        // Check if user has direct workspace access
        if ($this->users()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Check if user is org admin/owner (implicit access)
        /** @var Organization|null $organization */
        $organization = $this->organization;

        return $organization?->isUserAdmin($user) ?? false;
    }

    /**
     * Get the user's role in this workspace.
     */
    public function getUserRole(User $user): ?WorkspaceRole
    {
        // If user is org admin/owner, they have manager access
        /** @var Organization|null $organization */
        $organization = $this->organization;
        if ($organization?->isUserAdmin($user)) {
            return WorkspaceRole::MANAGER;
        }

        $pivot = $this->users()->where('user_id', $user->id)->first()?->pivot;

        return $pivot ? WorkspaceRole::from($pivot->role) : null;
    }

    /**
     * Check if the user has a specific role in this workspace.
     */
    public function userHasRole(User $user, WorkspaceRole $role): bool
    {
        return $this->getUserRole($user) === $role;
    }

    /**
     * Add a user to the workspace with a specific role.
     */
    public function addUser(User $user, WorkspaceRole|string $role = WorkspaceRole::VIEWER, ?User $invitedBy = null): void
    {
        $roleValue = $role instanceof WorkspaceRole ? $role->value : $role;

        $this->users()->attach($user->id, [
            'role' => $roleValue,
            'joined_at' => now(),
            'invited_by' => $invitedBy?->id,
        ]);
    }

    /**
     * Remove a user from the workspace.
     */
    public function removeUser(User $user): void
    {
        $this->users()->detach($user->id);
    }

    /**
     * Update a user's role in the workspace.
     */
    public function updateUserRole(User $user, WorkspaceRole|string $role): void
    {
        $roleValue = $role instanceof WorkspaceRole ? $role->value : $role;
        $this->users()->updateExistingPivot($user->id, ['role' => $roleValue]);
    }

    /**
     * Get workspace managers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<User, $this>
     */
    public function managers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->users()->wherePivot('role', WorkspaceRole::MANAGER->value);
    }

    /**
     * Get workspace editors.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<User, $this>
     */
    public function editors(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->users()->wherePivot('role', WorkspaceRole::EDITOR->value);
    }

    /**
     * Get workspace viewers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<User, $this>
     */
    public function viewers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->users()->wherePivot('role', WorkspaceRole::VIEWER->value);
    }

    /**
     * Check if workspace can be deleted.
     * Prevents deletion of the last workspace in an organization.
     */
    public function canBeDeleted(): bool
    {
        /** @var Organization|null $organization */
        $organization = $this->organization;

        // If no organization, cannot determine if it's safe to delete
        if (! $organization) {
            return false;
        }

        // Prevent deletion if this is the last workspace in the organization
        $workspaceCount = $organization->workspaces()->count();

        return $workspaceCount > 1;
    }

    /**
     * Get the settings for the workspace.
     *
     * @return HasOne<WorkspaceSetting, Workspace>
     */
    public function setting(): HasOne
    {
        return $this->hasOne(WorkspaceSetting::class);
    }

    /**
     * Get the connections for the workspace.
     *
     * @return HasMany<Connection, $this>
     */
    public function connections(): HasMany
    {
        return $this->hasMany(Connection::class);
    }

    /**
     * Get the feature limits for the workspace.
     *
     * @return HasMany<WorkspaceFeatureLimit, $this>
     */
    public function limits(): HasMany
    {
        return $this->hasMany(WorkspaceFeatureLimit::class);
    }
}
