<?php

namespace App\Enums;

enum WorkspaceRole: string
{
    case MANAGER = 'manager';
    case EDITOR = 'editor';
    case VIEWER = 'viewer';

    /**
     * Get all role values.
     */
    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the display name for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::MANAGER => 'Manager',
            self::EDITOR => 'Editor',
            self::VIEWER => 'Viewer',
        };
    }

    /**
     * @deprecated Use label() instead
     */
    public function displayName(): string
    {
        return $this->label();
    }

    /**
     * Get the role description.
     */
    public function description(): string
    {
        return match ($this) {
            self::MANAGER => 'Can manage workspace settings, content, and members',
            self::EDITOR => 'Can create and modify content within the workspace',
            self::VIEWER => 'Read-only access to workspace content',
        };
    }

    /**
     * Get the badge color for UI display.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::MANAGER => 'purple',
            self::EDITOR => 'blue',
            self::VIEWER => 'gray',
        };
    }

    /**
     * Get the icon for UI display.
     */
    public function icon(): string
    {
        return match ($this) {
            self::MANAGER => 'briefcase',
            self::EDITOR => 'edit',
            self::VIEWER => 'eye',
        };
    }

    /**
     * Get the role hierarchy level (higher number = more permissions).
     */
    public function level(): int
    {
        return match ($this) {
            self::MANAGER => 30,
            self::EDITOR => 20,
            self::VIEWER => 10,
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
     * Check if this role can manage workspace settings.
     */
    public function canManageWorkspace(): bool
    {
        return $this === self::MANAGER;
    }

    /**
     * Check if this role can invite users to the workspace.
     */
    public function canInviteUsers(): bool
    {
        return $this === self::MANAGER;
    }

    /**
     * Check if this role can edit content.
     */
    public function canEditContent(): bool
    {
        return in_array($this, [self::MANAGER, self::EDITOR]);
    }

    /**
     * Check if this role can view content.
     */
    public function canViewContent(): bool
    {
        return true; // All roles can view content
    }

    /**
     * Get roles that can be assigned by a given role.
     *
     * @return array<self>
     */
    public function assignableRoles(): array
    {
        return match ($this) {
            self::MANAGER => [self::EDITOR, self::VIEWER],
            self::EDITOR => [],
            self::VIEWER => [],
        };
    }
}
