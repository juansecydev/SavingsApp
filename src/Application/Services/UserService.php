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

    /**
     * Find the current user associated with an email address.
     *
     * @param string $email Sanitized email address to look up.
     * @return User|null The matching current user, or null when the email is available.
     */
    public function getUserByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }

    /**
     * Persist the editable profile fields for the authenticated user.
     *
     * @param User $user Authenticated user whose profile is being changed.
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
    ): ?User {
        return $this->userRepository->updateProfile(
            $user,
            $firstName,
            $lastName,
            $email,
            $profilePicture,
            $updateProfilePicture
        );
    }
}
