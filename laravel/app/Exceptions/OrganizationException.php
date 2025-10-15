<?php

namespace App\Exceptions;

use Exception;

class OrganizationException extends Exception
{
    public static function memberLimitReached(): self
    {
        return new self('Organization has reached member limit');
    }

    public static function workspaceLimitReached(): self
    {
        return new self('Organization has reached workspace limit');
    }

    public static function notFound(): self
    {
        return new self('Organization not found');
    }

    public static function cannotDelete(): self
    {
        return new self('Organization cannot be deleted while it has workspaces');
    }

    public static function transferRequiresAccess(): self
    {
        return new self('New owner must have access to the organization');
    }

    public static function cannotTransferToSelf(): self
    {
        return new self('Cannot transfer ownership to yourself');
    }

    public static function invalidPlan(): self
    {
        return new self('Invalid plan selected');
    }

    public static function noCurrentOwner(): self
    {
        return new self('Organization has no current owner');
    }

    public static function cannotRemoveOwner(): self
    {
        return new self('Cannot remove the organization owner');
    }

    public static function cannotRemoveYourself(): self
    {
        return new self('Cannot remove yourself from the organization');
    }

    public static function userNotMember(): self
    {
        return new self('User is not a member of this organization');
    }

    public static function cannotChangeOwnerRole(): self
    {
        return new self('Cannot change the organization owner\'s role');
    }

    public static function cannotChangeOwnRole(): self
    {
        return new self('Cannot change your own role');
    }

    public static function cannotPromoteToOwner(): self
    {
        return new self('Cannot promote to owner. Use transfer ownership instead');
    }

    public static function freeOrganizationLimitReached(): self
    {
        return new self('You already have a free organization. Please upgrade your existing organization or choose a paid plan.');
    }
}
