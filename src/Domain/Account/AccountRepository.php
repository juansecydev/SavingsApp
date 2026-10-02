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
     * Persist a newly created account.
     *
     * @param int $userId Owner of the new account.
     * @param string $title Sanitized account title.
     * @param int $currencyId Currency attached to the account.
     * @param int $balanceMinor The account balance expressed in minor units.
     * @return bool True when the insert succeeds.
     */
    public function createAccount(int $userId, string $title, int $currencyId, int $balanceMinor): bool;
}
