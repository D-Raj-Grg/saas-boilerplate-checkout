<?php

namespace App\Exceptions;

use Exception;

class WorkspaceException extends Exception
{
    public static function notFound(): self
    {
        return new self('Workspace not found');
    }

    public static function cannotDelete(): self
    {
        return new self('Cannot delete workspace');
    }

    public static function memberNotFound(): self
    {
        return new self('Member not found in workspace');
    }

    public static function cannotRemoveLastOwner(): self
    {
        return new self('Cannot remove the last owner from workspace');
    }

    public static function cannotRemoveSelf(): self
    {
        return new self('Cannot remove yourself from workspace');
    }

    public static function transferRequiresAccess(): self
    {
        return new self('New owner must have access to the workspace');
    }

    public static function cannotTransferToSelf(): self
    {
        return new self('Cannot transfer ownership to yourself');
    }

    public static function duplicateNameInOrganization(): self
    {
        return new self('Workspace name already exists in organization');
    }
}
