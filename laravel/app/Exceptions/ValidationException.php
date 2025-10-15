<?php

namespace App\Exceptions;

use Exception;

class ValidationException extends Exception
{
    public static function invalidUuid(string $field = 'id'): self
    {
        return new self("Invalid UUID format for {$field}");
    }

    public static function invalidEmail(): self
    {
        return new self('Invalid email format');
    }

    public static function invalidRole(): self
    {
        return new self('Invalid role specified');
    }

    public static function invalidPlan(): self
    {
        return new self('Invalid plan specified');
    }

    public static function fieldRequired(string $field): self
    {
        return new self("The {$field} field is required");
    }

    public static function fieldTooLong(string $field, int $maxLength): self
    {
        return new self("The {$field} field must not exceed {$maxLength} characters");
    }

    public static function fieldTooShort(string $field, int $minLength): self
    {
        return new self("The {$field} field must be at least {$minLength} characters");
    }
}
