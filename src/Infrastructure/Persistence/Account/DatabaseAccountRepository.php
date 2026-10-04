<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Account;

use App\Domain\Account\Account;
use App\Domain\Account\AccountRepository;
use App\Infrastructure\Persistence\Database\QueryBuilder;

class DatabaseAccountRepository implements AccountRepository
{
    public function __construct(private QueryBuilder $queryBuilder) {}

    public function findByUserId(int $userId): array
    {
        $rows = $this->queryBuilder->ownQuery(
            'SELECT
                a.account_id,
                a.account_title AS account_name,
                a.account_balance,
                a.account_user_id,
                a.account_currency_id,
                c.currency_code,
                c.currency_name,
                c.currency_symbol,
                c.currency_minor_units
            FROM accounts a
            INNER JOIN currencies c ON c.currency_id = a.account_currency_id
            WHERE a.account_user_id = :user_id
            ORDER BY a.account_id DESC',
            ['user_id' => $userId],
        );

        if ($rows === false || !is_array($rows)) {
            return [];
        }

        return array_map(fn (array $row): Account => $this->mapAccount($row), $rows);
    }

    public function findOneByUser(int $accountId, int $userId): ?Account
    {
        $rows = $this->queryBuilder->ownQuery(
            'SELECT
                a.account_id,
                a.account_title AS account_name,
                a.account_balance,
                a.account_user_id,
                a.account_currency_id,
                c.currency_code,
                c.currency_name,
                c.currency_symbol,
                c.currency_minor_units
            FROM accounts a
            INNER JOIN currencies c ON c.currency_id = a.account_currency_id
            WHERE a.account_id = :account_id AND a.account_user_id = :user_id
            LIMIT 1',
            [
                'account_id' => $accountId,
                'user_id' => $userId,
            ],
        );

        if ($rows === false || !is_array($rows) || $rows === []) {
            return null;
        }

        return $this->mapAccount($rows[0]);
    }

    /**
     * Retrieve and lock an account belonging to a user for an atomic balance update.
     *
     * @param int $accountId Account identifier.
     * @param int $userId Owner identifier.
     * @return Account|null The locked account, or null when it is not owned by the user.
     */
    public function findOneByUserForUpdate(int $accountId, int $userId): ?Account
    {
        $rows = $this->queryBuilder->ownQuery(
            'SELECT
                a.account_id,
                a.account_title AS account_name,
                a.account_balance,
                a.account_user_id,
                a.account_currency_id,
                c.currency_code,
                c.currency_name,
                c.currency_symbol,
                c.currency_minor_units
            FROM accounts a
            INNER JOIN currencies c ON c.currency_id = a.account_currency_id
            WHERE a.account_id = :account_id AND a.account_user_id = :user_id
            LIMIT 1
            FOR UPDATE',
            [
                'account_id' => $accountId,
                'user_id' => $userId,
            ],
        );

        if (!is_array($rows) || $rows === []) {
            return null;
        }

        return $this->mapAccount($rows[0]);
    }

    /**
     * Insert an account and return its generated identifier.
     *
     * @param int $userId Owner of the new account.
     * @param string $title Account title.
     * @param int $currencyId Currency attached to the account.
     * @param int $balanceMinor Initial balance in minor currency units.
     * @return int|false New account identifier, or false when insertion fails.
     */
    public function createAccount(int $userId, string $title, int $currencyId, int $balanceMinor): int|false
    {
        $created = $this->queryBuilder->ownQuery(
            'INSERT INTO accounts (account_title, account_balance, account_user_id, account_currency_id)
            VALUES (:title, :balance, :user_id, :currency_id)',
            [
                'title' => $title,
                'balance' => $balanceMinor,
                'user_id' => $userId,
                'currency_id' => $currencyId,
            ],
            true
        );

        if ($created !== true) {
            return false;
        }

        $accountId = filter_var($this->queryBuilder->lastInsertId(), FILTER_VALIDATE_INT);

        return $accountId !== false && $accountId > 0 ? $accountId : false;
    }

    /**
     * Update the stored balance for an account.
     *
     * @param int $accountId Account to update.
     * @param int $balanceMinor New balance in minor currency units.
     * @return bool True when the update statement succeeds.
     */
    public function updateBalance(int $accountId, int $balanceMinor): bool
    {
        return $this->queryBuilder->ownQuery(
            'UPDATE accounts SET account_balance = :balance WHERE account_id = :account_id',
            [
                'balance' => $balanceMinor,
                'account_id' => $accountId,
            ],
            true
        ) === true;
    }

    /**
     * Delete an account only when it belongs to the specified user.
     *
     * The account_transactions foreign key removes associated transactions by cascade.
     *
     * @param int $accountId Account identifier.
     * @param int $userId Owner identifier.
     * @return bool True when the delete statement executes successfully.
     */
    public function deleteOneByUser(int $accountId, int $userId): bool
    {
        return $this->queryBuilder->ownQuery(
            'DELETE FROM accounts WHERE account_id = :account_id AND account_user_id = :user_id',
            [
                'account_id' => $accountId,
                'user_id' => $userId,
            ],
            true
        ) === true;
    }

    private function mapAccount(array $row): Account
    {
        return new Account(
            (int) $row['account_id'],
            (string) $row['account_name'],
            (int) $row['account_balance'],
            (int) $row['account_user_id'],
            (int) $row['account_currency_id'],
            isset($row['currency_code']) ? (string) $row['currency_code'] : null,
            isset($row['currency_name']) ? (string) $row['currency_name'] : null,
            isset($row['currency_symbol']) ? (string) $row['currency_symbol'] : null,
            isset($row['currency_minor_units']) ? (int) $row['currency_minor_units'] : null,
        );
    }
}
