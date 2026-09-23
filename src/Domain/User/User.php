<?php

declare(strict_types=1);

namespace App\Domain\User;

use JsonSerializable;

class User implements JsonSerializable
{
    private int $id;

    private string $username;

    private string $firstName;

    private string $lastName;

    private ?string $email;

    private ?string $passwordHash;

    private ?string $profilePicture;

    private int $roleId;

    private ?string $roleName;

    public function __construct(
        int $id,
        string $firstName,
        string $lastName,
        string $email,
        string $passwordHash,
        ?string $profilePicture,
        int $roleId,
        ?string $roleName = null
    ) {
        $this->id = $id;
        $this->firstName = ucfirst($firstName);
        $this->lastName = ucfirst($lastName);
        $this->email = strtolower($email);
        $this->passwordHash = $passwordHash;
        $this->profilePicture = $profilePicture;
        $this->roleId = $roleId;
        $this->roleName = $roleName;
    }

    public function isAdmin(): bool
    {
        return $this->roleId === 1;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function getRoleId(): int
    {
        return $this->roleId;
    }

    public function getRoleName(): ?string
    {
        return $this->roleName;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setUsername(string $username): void
    {
        $this->username = strtolower($username);
    }

    public function setprofilePicture(string $profilePicture): void
    {
        $this->profilePicture = $profilePicture;
    }

    public function setRoleId(int $roleId): void
    {
        $this->roleId = $roleId;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'profilePicture' => $this->profilePicture,
            'roleId' => $this->roleId,
        ];
    }
}
