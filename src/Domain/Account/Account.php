<?php

declare(strict_types=1);

namespace App\Domain\Account;

class Account
{
    public function __construct(
        private int $id,
        private string $name,
        private int $balanceMinor,
        private int $userId,
        private int $currencyId,
        private ?string $currencyCode = null,
        private ?string $currencyName = null,
        private ?string $currencySymbol = null,
        private ?int $currencyMinorUnits = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getBalanceMinor(): int
    {
        return $this->balanceMinor;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCurrencyId(): int
    {
        return $this->currencyId;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function getCurrencyName(): ?string
    {
        return $this->currencyName;
    }

    public function getCurrencySymbol(): ?string
    {
        return $this->currencySymbol;
    }

    public function getCurrencyMinorUnits(): ?int
    {
        return $this->currencyMinorUnits;
    }
}
