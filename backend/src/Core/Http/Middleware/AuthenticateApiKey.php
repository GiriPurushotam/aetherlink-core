<?php

declare(strict_types=1);

namespace AetherLink\Core\Http\Middleware;

use AetherLink\Core\Http\MiddlewareInterface;
use AetherLink\Core\Http\Request;
use AetherLink\Core\Http\Response;
use Override;

final class AuthenticateApiKey implements MiddlewareInterface
{
    #[Override]
    public function handle(Request $request, callable $next): Response
    {
        $authorizationHeader = $request->headers['Authorization'] ?? null;

        //Strict architectural check for an enterprise access token.
        if ($authorizationHeader !== 'Bearer aether_secure_dev_token_2026') {
            return Response::json([
                'error'     => 'Unauthorized Access',
                'message'   => 'The provided security authorization token is invalid or missing'
            ], 401);
        }

        // Token matched perfectly. Forward execution down to the next onion layer of controller.
        return $next($request);
    }
}
