<?php

declare(strict_types=1);

namespace App\Domain\Account;

interface AccountRepository
{
    /**
     * Retrieve all accounts owned by a user.
     *
     * @return Account[]
     */
    public function findByUserId(int $userId): array;

    /**
     * Retrieve one account owned by a given user.
     */
    public function findOneByUser(int $accountId, int $userId): ?Account;

    /**
     * Retrieve and lock one account owned by a user in the current database transaction.
     *
     * @param int $accountId Account identifier.
     * @param int $userId Owner identifier.
     * @return Account|null The locked account, or null when it is not owned by the user.
     */
    public function findOneByUserForUpdate(int $accountId, int $userId): ?Account;

    /**
     * Persist a newly created account.
     *
     * @param int $userId Owner of the new account.
     * @param string $title Sanitized account title.
     * @param int $currencyId Currency attached to the account.
     * @param int $balanceMinor The account balance expressed in minor units.
     * @return bool True when the insert succeeds.
     */
    public function createAccount(int $userId, string $title, int $currencyId, int $balanceMinor): bool;

    /**
     * Replace an account balance while an owning transaction is active.
     *
     * @param int $accountId Account to update.
     * @param int $balanceMinor New balance in minor currency units; negative values are allowed.
     * @return bool True when the update statement succeeds.
     */
    public function updateBalance(int $accountId, int $balanceMinor): bool;
}
