<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\User;

use App\Domain\User\User;
use App\Domain\User\UserNotFoundException;
use App\Domain\User\UserRepository;
use App\Infrastructure\Persistence\Database\QueryBuilder;

class DatabaseUserRepository implements UserRepository
{
    public function __construct(private QueryBuilder $queryBuilder) {}

    public function findAll(): array
    {
        $rows = $this->queryBuilder->ownQuery(
            'SELECT user_id, user_names, user_last_names, user_email FROM users ORDER BY user_id;',
        );

        if ($rows === false) {
            return [];
        }

        return array_map(fn (array $row): User => $this->mapUser($row), $rows);
    }

    public function findUserById(int $id): User
    {
        $rows = $this->queryBuilder->ownQuery(
            'SELECT
                user_id, user_names, user_last_names, user_id, user_email, user_password, user_profile_picture, user_role_id, role_name
            FROM users u
            INNER JOIN roles r ON u.user_role_id = r.role_id
            WHERE u.user_id = :id LIMIT 1;',
            ['id' => $id],
        );

        if ($rows === false || $rows === []) {
            throw new UserNotFoundException();
        }

        return $this->mapUser($rows[0]);
    }

    public function findByEmail(string $email): ?User
    {
        $rows = $this->queryBuilder->ownQuery(
            'SELECT 
                user_id, user_names, user_last_names, user_id, user_email, user_password, user_profile_picture, user_role_id, role_name
            FROM users u
            INNER JOIN roles r ON u.user_role_id = r.role_id
            WHERE user_email = :email
            LIMIT 1;',
            ['email' => $email],
        );

        if ($rows === false || $rows === []) {
            return null;
        }

        return $this->mapUser($rows[0]);
    }

    /**
     * Update the editable fields in a user record and mark the user as updated.
     *
     * When requested, the supplied relative profile-picture path is also saved; otherwise
     * the existing picture column is left unchanged. The database's unique email constraint
     * is enforced by the prepared update query.
     *
     * @param User $user Authenticated user whose profile is updated.
     * @param string $firstName Sanitized, non-empty first name.
     * @param string $lastName Sanitized, non-empty last name.
     * @param string $email Sanitized, valid and unique email address.
     * @param string|null $profilePicture Relative profile-picture path, or null when it is removed.
     * @param bool $updateProfilePicture Whether the profile-picture column must be changed.
     * @return User|null The refreshed user when both writes succeed, or null when they fail.
     */
    public function updateProfile(
        User $user,
        string $firstName,
        string $lastName,
        string $email,
        ?string $profilePicture,
        bool $updateProfilePicture
    ): ?User {
        $assignments = [
            'user_names = :first_name',
            'user_last_names = :last_name',
            'user_email = :email',
        ];
        $parameters = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'user_id' => $user->getId(),
        ];

        if ($updateProfilePicture) {
            $assignments[] = 'user_profile_picture = :profile_picture';
            $parameters['profile_picture'] = $profilePicture;
        }

        $assignments[] = 'user_updated_at = CURRENT_TIMESTAMP';
        $profileUpdated = $this->queryBuilder->ownQuery(
            'UPDATE users SET ' . implode(', ', $assignments) . ' WHERE user_id = :user_id',
            $parameters, true
        );

        if ($profileUpdated === false || $user->getId() === null) {
            return null;
        }

        return $this->findUserById($user->getId());
    }

    /** @param array<string, mixed> $row */
    private function mapUser(array $row): User
    {
        return new User(
            (int) $row['user_id'],
            $row['user_names'],
            $row['user_last_names'],
            $row['user_email'],
            $row['user_password'],
            $row['user_profile_picture'] ?? null,
            (int) $row['user_role_id'],
            $row['role_name'] ?? null
        );
    }
}
