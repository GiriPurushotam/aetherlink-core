<?php

declare(strict_types=1);

namespace AetherLink\Core\Controllers;

use AetherLink\Core\Http\Middleware\AuthenticateApiKey;
use AetherLink\Core\Http\Request;
use AetherLink\Core\Http\Response;
use AetherLink\Core\Routing\Route;
use AetherLink\Core\Services\UserRepository;

final class UserController
{
    //The Container will auto-wire the UserRepository right into our Router dispatch map.

    public function __construct(
        private UserRepository $userRepository
    ) {}

    #[Route(path: '/', method: 'GET')]
    public function index(Request $request): Response
    {
        return Response::json([
            'status' => 'active',
            'data' => 'Welcome to AetherLink Endpoint Matrix',
            'postgres_telemetry' => $this->userRepository->getSystemHealth()
        ]);
    }

    #[Route(path: '/user/profile', method: 'GET', middleware: [AuthenticateApiKey::class])]
    public function profile(): Response
    {
        return Response::json([
            'status' => 'success',
        ]);
    }
}
