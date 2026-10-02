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
}