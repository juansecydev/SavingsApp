<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\TransactionOperation\TransactionOperation;
use App\Domain\TransactionOperation\TransactionOperationRepository;

/**
 * Exposes transaction operations used by application features.
 */
class TransactionOperationService
{
    /**
     * @param TransactionOperationRepository $transactionOperationRepository Repository used to retrieve operations.
     */
    public function __construct(private TransactionOperationRepository $transactionOperationRepository)
    {
    }

    /**
     * Get transaction operations available for account transactions.
     *
     * @return TransactionOperation[] Operations ordered by database identifier.
     */
    public function getTransactionOperations(): array
    {
        return $this->transactionOperationRepository->findAll();
    }

    /**
     * Find a transaction operation by identifier.
     *
     * @param int $id Operation identifier submitted by the transaction form.
     * @return TransactionOperation|null|false The operation, null when absent, or false on retrieval failure.
     */
    public function getTransactionOperationById(int $id): TransactionOperation|false|null
    {
        return $this->transactionOperationRepository->findById($id);
    }
}