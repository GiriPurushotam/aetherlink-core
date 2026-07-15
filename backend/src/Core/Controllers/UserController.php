<?php

declare(strict_types=1);

namespace AetherLink\Core\Controllers;

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
    public function index(): Response
    {
        return Response::json([
            'status' => 'active',
            'data' => 'Welcome to AetherLink Endpoint Matrix'
        ]);
    }

    #[Route(path: '/user/profile', method: 'GET')]
    public function profile(): Response
    {
        return Response::json([
            'status' => 'success',
            'user_payload' => $this->userRepository->getUserData(99)
        ]);
    }
}
