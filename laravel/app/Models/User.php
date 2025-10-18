<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\OrganizationRole;
use App\Enums\WorkspaceRole;
use App\Traits\HasPermissions;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string $email
 * @property \Carbon\Carbon|null $email_verified_at
 * @property int|null $current_organization_id
 * @property int|null $current_workspace_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \App\Models\Organization|null $currentOrganization
 * @property \App\Models\Workspace|null $currentWorkspace
 */
class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use HasPermissions;
    use HasUuid;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'google_id',
        'avatar',
        'current_organization_id',
        'current_workspace_id',
        'metadata',
        'email_verified_at',
        'locale',
        'currency_preference',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'current_organization_uuid',
        'current_workspace_uuid',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the verifications for the user.
     *
     * @return HasMany<UserVerification, $this>
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(UserVerification::class);
    }

    /**
     * Get the user's current organization.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function currentOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'current_organization_id');
    }

    /**
     * Get the user's current workspace.
     *
     * @return BelongsTo<Workspace, $this>
     */
    public function currentWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'current_workspace_id');
    }

    /**
     * Get the organization users (memberships).
     *
     * @return HasMany<OrganizationUser, $this>
     */
    public function organizationUsers(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }

    /**
     * Get the organizations the user belongs to.
     *
     * @return BelongsToMany<Organization, $this>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
            ->withPivot(['role', 'capabilities', 'joined_at', 'invited_by'])
            ->withTimestamps();
    }

    /**
     * Get the organizations owned by the user.
     *
     * @return BelongsToMany<Organization, $this>
     */
    public function ownedOrganizations(): BelongsToMany
    {
        return $this->organizations()->wherePivot('role', OrganizationRole::OWNER->value);
    }

    /**
     * Get the workspaces the user belongs to.
     *
     * @return BelongsToMany<Workspace, $this>
     */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_users')
            ->withPivot(['role', 'capabilities', 'joined_at', 'invited_by'])
            ->withTimestamps();
    }

    /**
     * Get all workspaces accessible to the user with their effective roles.
     *
     * @return \Illuminate\Support\Collection<int, array{workspace: \App\Models\Workspace, role: string}>
     */
    public function accessibleWorkspaces(): \Illuminate\Support\Collection
    {
        $workspaceData = collect();

        // Get user's direct workspace memberships once (avoid N+1 queries)
        $directWorkspaceRoles = $this->workspaces()
            ->select('workspaces.id', 'workspace_users.role')
            ->get()
            ->keyBy('id')
            ->map(function ($workspace) {
                // @phpstan-ignore-next-line
                return $workspace->pivot->role;
            });

        // Get organizations where user is admin/owner to access ALL workspaces
        $adminOrganizations = $this->accessibleOrganizations()
            ->with(['workspaces.organization'])
            ->get();

        foreach ($adminOrganizations as $org) {
            $orgRole = $this->getOrganizationRole($org);

            foreach ($org->workspaces as $workspace) {
                // Check if user is direct member of this workspace (no database query)
                $directRole = $directWorkspaceRoles->get($workspace->id);

                // Determine effective role
                $effectiveRole = null;
                if ($directRole) {
                    $effectiveRole = $directRole;
                } elseif ($orgRole && in_array($orgRole, [OrganizationRole::OWNER, OrganizationRole::ADMIN])) {
                    $effectiveRole = WorkspaceRole::MANAGER->value;
                }

                // Only include if user has some access
                if ($effectiveRole) {
                    $workspaceData->push([
                        'workspace' => $workspace,
                        'role' => $effectiveRole,
                    ]);
                }
            }
        }

        // Remove duplicates by workspace ID (keep first occurrence)
        $uniqueWorkspaces = $workspaceData->unique(function ($item) {
            return $item['workspace']->id;
        });

        return $uniqueWorkspaces;
    }

    /**
     * Get workspaces accessible to the user in their current organization.
     * Merges direct workspace memberships with admin access.
     * Returns Eloquent collection with pivot data for compatibility.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Workspace>
     */
    public function currentOrganizationWorkspaces(): \Illuminate\Database\Eloquent\Collection
    {
        // If no current organization, return empty collection
        if (! $this->current_organization_id) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        $organization = Organization::find($this->current_organization_id);
        if (! $organization) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        // Get user's direct workspace memberships in this organization
        $directWorkspaces = $this->workspaces()
            ->where('organization_id', $organization->id)
            ->with(['organization', 'users'])
            ->withPivot('role', 'joined_at')
            ->get();

        // If user is organization admin/owner, also get workspaces they don't have direct access to
        if ($this->isOrganizationAdmin($organization)) {
            // Get all workspace IDs user already has direct access to
            $directWorkspaceIds = $directWorkspaces->pluck('id');

            // Get remaining workspaces in the organization (only if there are direct workspaces to exclude)
            $query = $organization->workspaces()
                ->with(['organization', 'users']);

            if ($directWorkspaceIds->isNotEmpty()) {
                $query->whereNotIn('id', $directWorkspaceIds);
            }

            $adminAccessWorkspaces = $query->get()
                ->each(function ($workspace) use ($organization) {
                    // Add pivot data for consistency (org admins get manager role)
                    $workspace->setRelation('pivot', (object) [
                        'role' => WorkspaceRole::MANAGER->value,
                        'joined_at' => $this->organizations()
                            ->where('organizations.id', $organization->id)
                            ->first()
                            ?->pivot
                            ->joined_at ?? now(),
                    ]);
                });

            // Concat both collections (following getMembers pattern)
            return $directWorkspaces->concat($adminAccessWorkspaces);
        }

        // Regular members only see workspaces they're directly assigned to
        return $directWorkspaces;
    }

    /**
     * Get the invitations sent by the user.
     *
     * @return HasMany<Invitation, $this>
     */
    public function sentInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }

    /**
     * Get the invitations received by the user.
     *
     * @return HasMany<Invitation, $this>
     */
    public function receivedInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'email', 'email');
    }

    /**
     * Get all organizations the user has access to.
     */
    public function accessibleOrganizations()
    {
        return $this->organizations();
    }

    /**
     * Get the user's default organization.
     */
    public function defaultOrganization(): ?Organization
    {
        return $this->ownedOrganizations()->first() ??
               $this->organizations()->first();
    }

    /**
     * Get the user's default workspace.
     */
    public function defaultWorkspace(): ?Workspace
    {
        $defaultOrg = $this->defaultOrganization();

        return $defaultOrg?->defaultWorkspace() ??
               $this->workspaces()->first();
    }

    /**
     * Get workspaces where the user is a manager.
     *
     * @return BelongsToMany<Workspace, $this>
     */
    public function managedWorkspaces(): BelongsToMany
    {
        return $this->workspaces()->wherePivot('role', WorkspaceRole::MANAGER->value);
    }

    /**
     * Get the current organization UUID.
     */
    public function getCurrentOrganizationUuidAttribute(): ?string
    {
        return $this->currentOrganization?->uuid;
    }

    /**
     * Get the current workspace UUID.
     */
    public function getCurrentWorkspaceUuidAttribute(): ?string
    {
        return $this->currentWorkspace?->uuid;
    }

    /**
     * Get the user's organization role.
     */
    public function getOrganizationRole(Organization $organization): ?OrganizationRole
    {
        $orgUser = $this->organizationUsers()
            ->where('organization_id', $organization->id)
            ->first();

        return $orgUser?->role;
    }

    /**
     * Check if user is an organization owner.
     */
    public function isOrganizationOwner(Organization $organization): bool
    {
        return $this->getOrganizationRole($organization) === OrganizationRole::OWNER;
    }

    /**
     * Check if user is an organization admin.
     */
    public function isOrganizationAdmin(Organization $organization): bool
    {
        $role = $this->getOrganizationRole($organization);

        return $role && in_array($role, [OrganizationRole::OWNER, OrganizationRole::ADMIN]);
    }

    /**
     * Check if user can access all workspaces in an organization.
     */
    public function canAccessAllWorkspacesIn(Organization $organization): bool
    {
        $role = $this->getOrganizationRole($organization);

        return $role && $role->hasImplicitWorkspaceAccess();
    }

    /**
     * Get the user's workspace count.
     */
    public function getWorkspaceCountAttribute(): int
    {
        return $this->workspaces()->count();
    }

    /**
     * Get the user's organization count.
     */
    public function getOrganizationCountAttribute(): int
    {
        return $this->organizations()->count();
    }

    /**
     * Get the user's owned organization count.
     */
    public function getOwnedOrganizationCountAttribute(): int
    {
        return $this->ownedOrganizations()->count();
    }

    /**
     * Check if user belongs to an organization.
     */
    public function belongsToOrganization(Organization $organization): bool
    {
        return $this->organizationUsers()
            ->where('organization_id', $organization->id)
            ->exists();
    }

    /**
     * Check if the user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        $adminEmails = config('app.superadmin_emails', []);

        return in_array($this->email, $adminEmails);
    }

    /**
     * Get the SureCart customer ID for the current environment.
     */
    public function getSureCartCustomerId(): ?string
    {
        /** @var array<string, mixed> $metadata */
        $metadata = $this->metadata ?? [];

        /** @var array<string, string> $scCustomerIds */
        $scCustomerIds = isset($metadata['sc_customer_ids']) && is_array($metadata['sc_customer_ids'])
            ? $metadata['sc_customer_ids']
            : [];

        $environment = config('app.env') === 'production' ? 'live' : 'test';

        return $scCustomerIds[$environment] ?? null;
    }
}
