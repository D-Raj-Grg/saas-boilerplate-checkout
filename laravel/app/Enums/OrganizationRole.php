<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MEMBER = 'member';

    /**
     * Get the display name for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Owner',
            self::ADMIN => 'Administrator',
            self::MEMBER => 'Member',
        };
    }

    /**
     * Get the role hierarchy level (higher number = more permissions).
     */
    public function level(): int
    {
        return match ($this) {
            self::OWNER => 100,
            self::ADMIN => 50,
            self::MEMBER => 10,
        };
    }

    /**
     * Check if this role is higher than another role.
     */
    public function isHigherThan(self $other): bool
    {
        return $this->level() > $other->level();
    }

    /**
     * Check if this role is at least as high as another role.
     */
    public function isAtLeast(self $other): bool
    {
        return $this->level() >= $other->level();
    }

    /**
     * Check if this role can manage organization settings.
     */
    public function canManageOrganization(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN]);
    }

    /**
     * Check if this role can invite users to the organization.
     */
    public function canInviteUsers(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN]);
    }

    /**
     * Check if this role can manage billing.
     */
    public function canManageBilling(): bool
    {
        return $this === self::OWNER;
    }

    /**
     * Check if this role can transfer ownership.
     */
    public function canTransferOwnership(): bool
    {
        return $this === self::OWNER;
    }

    /**
     * Check if this role has implicit access to all workspaces.
     */
    public function hasImplicitWorkspaceAccess(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN]);
    }

    /**
     * Get all available roles.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get roles that can be assigned by a given role.
     *
     * @return array<self>
     */
    public function assignableRoles(): array
    {
        return match ($this) {
            self::OWNER => [self::ADMIN, self::MEMBER],
            self::ADMIN => [self::MEMBER],
            self::MEMBER => [],
        };
    }
}
