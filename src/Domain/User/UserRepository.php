<?php

declare(strict_types=1);

namespace App\Domain\User;

interface UserRepository
{
    /**
     * @return User[]
     */
    public function findAll(): array;

    /**
     * Find the current user version by user ID.
     * @param int $id
     * @return User
     * @throws UserNotFoundException
     */
    public function findUserById(int $id): ?User;

    /**
     * Find the current user version by email address.
     * @param string $email
     * @return User|null
     * @throws UserNotFoundException
     */
    public function findByEmail(string $email): ?User;
}
