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
     * Find a user by user ID.
     * @param int $id
     * @return User
     * @throws UserNotFoundException
     */
    public function findUserById(int $id): ?User;

    /**
     * Find a user by email address.
     * @param string $email
     * @return User|null
     * @throws UserNotFoundException
     */
    public function findByEmail(string $email): ?User;

    /**
     * Update the editable fields of a user.
     *
     * @param User $user Authenticated user whose profile is updated.
     * @param string $firstName Sanitized, non-empty first name.
     * @param string $lastName Sanitized, non-empty last name.
     * @param string $email Sanitized, valid and unique email address.
     * @param string|null $profilePicture Relative profile-picture path, or null when it is removed.
     * @param bool $updateProfilePicture Whether the profile-picture column must be changed.
     * @return User|null The refreshed user when the update succeeds, or null when it cannot be saved.
     */
    public function updateProfile(
        User $user,
        string $firstName,
        string $lastName,
        string $email,
        ?string $profilePicture,
        bool $updateProfilePicture
    ): ?User;
}
