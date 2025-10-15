<?php

namespace App\Exceptions;

use Exception;

class InvitationException extends Exception
{
    public static function alreadyMember(): self
    {
        return new self('User is already a member of this workspace');
    }

    public static function alreadyPending(): self
    {
        return new self('Invitation already sent to this email');
    }

    public static function notFound(): self
    {
        return new self('Invitation not found');
    }

    public static function expired(): self
    {
        return new self('Invalid or expired invitation');
    }

    public static function emailMismatch(): self
    {
        return new self('Invitation email does not match user email');
    }

    public static function cannotCancel(): self
    {
        return new self('Cannot cancel a non-pending invitation');
    }

    public static function cannotResend(): self
    {
        return new self('Cannot resend a non-pending invitation');
    }

    public static function cannotAccept(): self
    {
        return new self('Cannot accept this invitation');
    }

    public static function cannotDecline(): self
    {
        return new self('Cannot decline this invitation');
    }

    public static function cannotUpdateRole(): self
    {
        return new self('Cannot update role of non-pending invitation');
    }
}
