<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Account\Account;
use App\Domain\Account\AccountRepository;
use Brick\Money\Money;
use InvalidArgumentException;

class AccountService
{
    public function __construct(private AccountRepository $accountRepository) {}

    /**
     * @return Account[]
     */
    public function getAccountsByUserId(int $userId): array
    {
        return $this->accountRepository->findByUserId($userId);
    }

    public function getAccountByIdForUser(int $accountId, int $userId): ?Account
    {
        return $this->accountRepository->findOneByUser($accountId, $userId);
    }

    /**
     * Retrieve an owned account while locking its row for a balance transaction.
     *
     * @param int $accountId Account identifier.
     * @param int $userId Owner identifier.
     * @return Account|null The locked account, or null when it is not owned by the user.
     */
    public function getAccountByIdForUserForUpdate(int $accountId, int $userId): ?Account
    {
        return $this->accountRepository->findOneByUserForUpdate($accountId, $userId);
    }

    /**
     * Convert a user-facing amount string into minor units compatible with the database.
     *
     * Supports zero, positive amounts, and negative values.
     */
    public function convertAmountToMinorUnits(string $amount, string $currencyCode): int
    {
        $normalizedAmount = $this->normalizeAmount($amount);

        if ($normalizedAmount === null) {
            throw new InvalidArgumentException('The account amount must be a valid numeric value.');
        }

        return Money::of($normalizedAmount, $currencyCode)->getMinorAmount()->toInt();
    }

    /**
     * Persist a newly created account balance in minor units.
     */
    public function createAccount(int $userId, string $title, int $currencyId, int $balanceMinor): bool
    {
        return $this->accountRepository->createAccount($userId, $title, $currencyId, $balanceMinor);
    }

    /**
     * Update an account's balance in minor units.
     *
     * @param int $accountId Account to update.
     * @param int $balanceMinor New balance; negative balances are allowed.
     * @return bool True when the update statement succeeds.
     */
    public function updateBalance(int $accountId, int $balanceMinor): bool
    {
        return $this->accountRepository->updateBalance($accountId, $balanceMinor);
    }

    private function normalizeAmount(string $amount): ?string
    {
        $normalized = trim((string) $amount);

        if ($normalized === '') {
            return '0';
        }

        $normalized = str_replace(',', '.', $normalized);

        if (!preg_match('/^-?\d+(?:\.\d+)?$/', $normalized)) {
            return null;
        }

        return $normalized;
    }
}
