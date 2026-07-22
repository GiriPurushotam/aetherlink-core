<?php

declare(strict_types=1);

namespace AetherLink\Core\Exceptions;

use RuntimeException;

final class InvalidEnvironmentException extends RuntimeException
{
    public static function missingVariable(string $key): self
    {
        return new self(sprintf('Environment configuration fault: Require Key [%s] is not defined.', $key));
    }

    public static function invalidType(string $key, string $expectedType, mixed $actualValue): self
    {
        return new self(sprintf(
            'Environment Configuration Fault: key [%s] must be type of [%s], got [%s].',
            $key,
            $expectedType,
            get_debug_type($actualValue)
        ));
    }
}
