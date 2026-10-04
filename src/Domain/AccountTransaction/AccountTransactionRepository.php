<?php

declare(strict_types=1);

namespace App\Domain\AccountTransaction;

interface AccountTransactionRepository
{
    /**
     * Retrieve transaction rows belonging to an account, newest first.
     *
     * @param int $accountId Account whose transactions are requested.
     * @return array<int, array<string, mixed>>|false Transaction data, or false when retrieval fails.
     */
    public function findByAccountId(int $accountId): array|false;
}
