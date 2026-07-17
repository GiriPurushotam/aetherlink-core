<?php

declare(strict_types=1);

namespace AetherLink\Core\Http;

interface MiddlewareInterface
{
    /**
     * Intercept and process an incoming servcer request
     *
     * @param Request $request The incoming server state.
     * @param callable(Request): Response $next The next execution layer closure block.
     */
    public function handle(Request $request, callable $next): Response;
}
