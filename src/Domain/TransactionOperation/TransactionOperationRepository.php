<?php

declare(strict_types=1);

namespace App\Domain\TransactionOperation;

/**
 * Provides transaction operations available to the application.
 */
interface TransactionOperationRepository
{
    /**
     * Retrieve every transaction operation in database order.
     *
     * @return TransactionOperation[] Available operations, or an empty array when none are found.
     */
    public function findAll(): array;

    /**
     * Retrieve one operation by its database identifier.
     *
     * @param int $id Operation identifier to look up.
     * @return TransactionOperation|null|false The operation, null when absent, or false on retrieval failure.
     */
    public function findById(int $id): TransactionOperation|false|null;
}