<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\User\User;
use App\Domain\User\UserRepository;

class LoginService
{
    public function __construct(private UserRepository $userRepository) {}

    public function authenticate(string $email, string $password): ?User
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null || $user->getPasswordHash() === null) {
            return null;
        }

        return password_verify($password, $user->getPasswordHash()) ? $user : null;
    }
}
