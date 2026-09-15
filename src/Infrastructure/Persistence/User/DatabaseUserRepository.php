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
            'SELECT user_version_user_id, user_version_names, user_version_last_names, '
            . 'user_version_email FROM user_version ORDER BY user_version_user_id',
        );

        if ($rows === false) {
            return [];
        }

        return array_map(fn (array $row): User => $this->mapUser($row), $rows);
    }

    public function findUserById(int $id): User
    {
        $rows = $this->queryBuilder->ownQuery(
            'SELECT user_version_user_id, user_version_names, user_version_last_names, user_version_id, user_version_number,
                user_version_email, user_version_password, user_version_profile_picture, user_version_role_id,
                user_version_role_id, user_version_role_id, user_version_number, role_name
            FROM user_version uv
            INNER JOIN users u ON uv.user_version_user_id = u.user_id AND uv.user_version_number = u.user_current_version_number
            INNER JOIN role r ON uv.user_version_role_id = r.role_id
            WHERE u.user_id = :id LIMIT 1',
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
                user_version_user_id, user_version_names, user_version_last_names, user_version_id, user_version_number,
                user_version_email, user_version_password, user_version_profile_picture, user_version_role_id,
                user_version_role_id, user_version_role_id, user_version_number, role_name
            FROM user_version uv
            INNER JOIN users u ON uv.user_version_user_id = u.user_id AND uv.user_version_number = u.user_current_version_number
            INNER JOIN role r ON uv.user_version_role_id = r.role_id
            WHERE user_version_email = :email 
            LIMIT 1',
            ['email' => $email],
        );

        if ($rows === false || $rows === []) {
            return null;
        }

        return $this->mapUser($rows[0]);
    }

    /** @param array<string, mixed> $row */
    private function mapUser(array $row): User
    {
        return new User(
            (int) $row['user_version_user_id'],
            (int) $row['user_version_id'],
            $row['user_version_names'],
            $row['user_version_last_names'],
            $row['user_version_email'],
            $row['user_version_password'],
            $row['user_version_profile_picture'] ?? null,
            (int) $row['user_version_role_id'],
            (int) $row['user_version_number']
        );
    }
}
