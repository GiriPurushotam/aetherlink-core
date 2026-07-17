<?php

declare(strict_types=1);

namespace AetherLink\Core\Routing;

use Attribute;

/**
 * Modern PHP Attribute Engine
 * Allows declaring routing maps directly above Controller actions natively.
 */
#[Attribute(Attribute::TARGET_METHOD)]
readonly final class Route
/**
 * @param array<class-string> $middleware Array of middleware class strings to execute.
 */
{
    public function __construct(
        public string $path,
        public string $method = 'GET',
        public array $middleware = []
    ) {}
}
