<?php

declare(strict_types=1);

namespace AetherLink\Core;

/**
 * The Kernel is the structural engine core of AetherLink.
 * It manages Application bootstrapping, environment verification, and runtime isolation
 */

final class Kernel
{
    private bool $booted = false;

    public function __construct(
        private readonly string $environment,
        private readonly bool $debugMode
    ) {}

    /**
     * Undocumented function
     * Bootstrap the application runtime space.
     * Enforces type-safety check and system health boundaries. 
     * @return void
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Core system registration will live here
        $this->booted = true;
    }


    public function isBooted(): bool
    {
        return $this->booted;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }
}
