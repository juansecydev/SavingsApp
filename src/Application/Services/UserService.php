<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\User\User;
use App\Domain\User\UserRepository;

class UserService
{
    public function __construct(private UserRepository $userRepository) {}

    public function getUser(int $id): ?User
    {
        return $this->userRepository->findUserById($id);
    }
}
