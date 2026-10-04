<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\AccountTransaction;

use App\Domain\AccountTransaction\AccountTransactionRepository;
use App\Infrastructure\Persistence\Database\QueryBuilder;

/**
 * Loads account transactions and their operation labels from the database.
 */
class DatabaseAccountTransactionRepository implements AccountTransactionRepository
{
    /**
     * @param QueryBuilder $queryBuilder Prepared database query service.
     */
    public function __construct(private QueryBuilder $queryBuilder)
    {
    }

    /**
     * Retrieve transaction rows belonging to an account, newest first.
     *
     * Each row contains id, title, amount in minor units, creation date, operation
     * description, and operation symbol.
     *
     * @param int $accountId Account whose transactions are requested.
     * @return array<int, array<string, mixed>>|false Transaction data, or false when retrieval fails.
     */
    public function findByAccountId(int $accountId): array|false
    {
        $rows = $this->queryBuilder->ownQuery(
            'SELECT
                t.account_transaction_id,
                t.account_transaction_title,
                t.account_transaction_reference,
                t.account_transaction_amount,
                t.account_transaction_created_at,
                o.transaction_operation_description,
                o.transaction_operation_symbol
            FROM account_transactions t
            INNER JOIN transaction_operations o
                ON o.transaction_operation_id = t.account_transaction_transaction_operation_id
            WHERE t.account_transaction_account_id = :account_id
            ORDER BY t.account_transaction_created_at DESC, t.account_transaction_id DESC',
            ['account_id' => $accountId],
        );

        return is_array($rows) ? $rows : false;
    }

    /**
     * Insert a transaction record; the caller owns the surrounding database transaction.
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
    ): bool {
        return $this->queryBuilder->ownQuery(
            'INSERT INTO account_transactions (
                account_transaction_account_id,
                account_transaction_title,
                account_transaction_reference,
                account_transaction_amount,
                account_transaction_transaction_operation_id
            ) VALUES (
                :account_id,
                :title,
                :reference,
                :amount,
                :operation_id
            )',
            [
                'account_id' => $accountId,
                'title' => $title,
                'reference' => $reference,
                'amount' => $amountMinor,
                'operation_id' => $operationId,
            ],
            true
        ) === true;
    }
}
