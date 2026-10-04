<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\AccountTransaction\AccountTransactionRepository;

/**
 * Provides account transaction data to application actions.
 */
class AccountTransactionService
{
    /**
     * @param AccountTransactionRepository $accountTransactionRepository Repository used to retrieve account transactions.
     */
    public function __construct(private AccountTransactionRepository $accountTransactionRepository)
    {
    }

    /**
     * Retrieve transactions for one account.
     *
     * @param int $accountId Account whose transactions are requested.
     * @return array<int, array<string, mixed>>|false Transaction rows, or false when retrieval fails.
     */
    public function getTransactionsByAccountId(int $accountId): array|false
    {
        return $this->accountTransactionRepository->findByAccountId($accountId);
    }
}
