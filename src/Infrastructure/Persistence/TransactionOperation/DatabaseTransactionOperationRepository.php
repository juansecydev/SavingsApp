<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\TransactionOperation;

use App\Domain\TransactionOperation\TransactionOperation;
use App\Domain\TransactionOperation\TransactionOperationRepository;
use App\Infrastructure\Persistence\Database\QueryBuilder;

/**
 * Retrieves transaction operations from the relational database.
 */
class DatabaseTransactionOperationRepository implements TransactionOperationRepository
{
    /**
     * @param QueryBuilder $queryBuilder Database query service used to read operation rows.
     */
    public function __construct(private QueryBuilder $queryBuilder)
    {
    }

    /**
     * Retrieve transaction operations for transaction forms.
     *
     * @return TransactionOperation[] Operations ordered by their database identifier.
     */
    public function findAll(): array
    {
        $rows = $this->queryBuilder->ownQuery(
            'SELECT transaction_operation_id, transaction_operation_description, transaction_operation_symbol
            FROM transaction_operations
            ORDER BY transaction_operation_id',
        );

        if ($rows === false || !is_array($rows)) {
            return [];
        }

        return array_map(
            fn (array $row): TransactionOperation => $this->mapTransactionOperation($row),
            $rows,
        );
    }

    /**
     * Retrieve one transaction operation by its database identifier.
     *
     * @param int $id Operation identifier to look up.
     * @return TransactionOperation|null|false The operation, null when absent, or false on retrieval failure.
     */
    public function findById(int $id): TransactionOperation|false|null
    {
        $rows = $this->queryBuilder->ownQuery(
            'SELECT transaction_operation_id, transaction_operation_description, transaction_operation_symbol
            FROM transaction_operations
            WHERE transaction_operation_id = :id
            LIMIT 1',
            ['id' => $id],
        );

        if ($rows === false) {
            return false;
        }
        if ($rows === []) {
            return null;
        }

        return $this->mapTransactionOperation($rows[0]);
    }

    /**
     * Map a database row to its transaction-operation domain representation.
     *
     * @param array<string, mixed> $row Transaction-operation row returned by the database.
     * @return TransactionOperation Operation represented by the row.
     */
    private function mapTransactionOperation(array $row): TransactionOperation
    {
        return new TransactionOperation(
            (int) $row['transaction_operation_id'],
            (string) $row['transaction_operation_description'],
            (string) $row['transaction_operation_symbol'],
        );
    }
}