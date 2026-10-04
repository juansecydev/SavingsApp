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

    /**
     * Insert a transaction record for an account.
     *
     * @param int $accountId Account receiving the transaction.
     * @param int $operationId Transaction operation identifier.
     * @param int $amountMinor Positive amount in minor currency units.
     * @param string $title User-provided transaction description.
     * @param string|null $reference Optional transaction reference.
     * @return bool True when the insert statement succeeds.
     */
    public function create(
        int $accountId,
        int $operationId,
        int $amountMinor,
        string $title,
        ?string $reference
    ): bool;
}
