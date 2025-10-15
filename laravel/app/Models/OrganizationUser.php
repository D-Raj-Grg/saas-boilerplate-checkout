<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $user_id
 * @property \App\Enums\OrganizationRole $role
 * @property array<string, mixed>|null $capabilities
 * @property int|null $invited_by
 * @property \Carbon\Carbon|null $joined_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read User $user
 * @property-read User|null $inviter
 */
class OrganizationUser extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
        'capabilities',
        'invited_by',
        'joined_at',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'joined_at' => 'datetime',
        'role' => OrganizationRole::class,
    ];

    /**
     * Get the organization.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who invited this user.
     *
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Check if the user is an owner.
     */
    public function isOwner(): bool
    {
        return $this->role === OrganizationRole::OWNER;
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === OrganizationRole::ADMIN;
    }

    /**
     * Check if the user is a member.
     */
    public function isMember(): bool
    {
        return $this->role === OrganizationRole::MEMBER;
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(OrganizationRole $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has at least a specific role level.
     */
    public function hasRoleAtLeast(OrganizationRole $role): bool
    {
        return $this->role->isAtLeast($role);
    }

    /**
     * Check if user can manage organization.
     */
    public function canManageOrganization(): bool
    {
        return $this->role->canManageOrganization();
    }

    /**
     * Check if user can invite other users.
     */
    public function canInviteUsers(): bool
    {
        return $this->role->canInviteUsers();
    }

    /**
     * Check if user can manage billing.
     */
    public function canManageBilling(): bool
    {
        return $this->role->canManageBilling();
    }

    /**
     * Check if user has implicit access to all workspaces.
     */
    public function hasImplicitWorkspaceAccess(): bool
    {
        return $this->role->hasImplicitWorkspaceAccess();
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

    /**
     * Get the role display name.
     */
    public function getRoleDisplayName(): string
    {
        return $this->role->label();
    }

    /**
     * Check if the user can change another user's role.
     */
    public function canChangeUserRole(OrganizationUser $targetUser): bool
    {
        // Can't change your own role
        if ($this->user_id === $targetUser->user_id) {
            return false;
        }

        // Only owners can change roles
        if (! $this->isOwner()) {
            return false;
        }

        // Owners can change any role except other owners
        return ! $targetUser->isOwner();
    }

    /**
     * Check if the user can remove another user.
     */
    public function canRemoveUser(OrganizationUser $targetUser): bool
    {
        // Can't remove yourself
        if ($this->user_id === $targetUser->user_id) {
            return false;
        }

        // Owners can remove anyone except other owners
        if ($this->isOwner()) {
            return ! $targetUser->isOwner();
        }

        // Admins can remove members only
        if ($this->isAdmin()) {
            return $targetUser->isMember();
        }

        return false;
    }

    /**
     * Scope to get users with specific role.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<OrganizationUser>  $query
     * @return \Illuminate\Database\Eloquent\Builder<OrganizationUser>
     */
    public function scopeWithRole($query, OrganizationRole $role)
    {
        return $query->where('role', $role->value);
    }

    /**
     * Scope to get owner users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<OrganizationUser>  $query
     * @return \Illuminate\Database\Eloquent\Builder<OrganizationUser>
     */
    public function scopeOwners($query)
    {
        return $query->where('role', OrganizationRole::OWNER->value);
    }

    /**
     * Scope to get admin users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<OrganizationUser>  $query
     * @return \Illuminate\Database\Eloquent\Builder<OrganizationUser>
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', OrganizationRole::ADMIN->value);
    }

    /**
     * Scope to get member users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<OrganizationUser>  $query
     * @return \Illuminate\Database\Eloquent\Builder<OrganizationUser>
     */
    public function scopeMembers($query)
    {
        return $query->where('role', OrganizationRole::MEMBER->value);
    }

    /**
     * Scope to get users who can manage the organization.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<OrganizationUser>  $query
     * @return \Illuminate\Database\Eloquent\Builder<OrganizationUser>
     */
    public function scopeManagers($query)
    {
        return $query->whereIn('role', [
            OrganizationRole::OWNER->value,
            OrganizationRole::ADMIN->value,
        ]);
    }
}
