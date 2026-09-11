<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Database;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Small PDO-based query builder for common single-table operations.
 *
 * Values are always sent through prepared statements. SQL interpolation is
 * used only to make diagnostic entries readable in the SQL log.
 */
class QueryBuilder
{
    private LoggerInterface $logger;

    /**
     * @param PDO $connection PDO connection used to execute statements.
     * @param LoggerInterface|null $logger Logger for SQL queries and errors.
     */
    public function __construct(private PDO $connection, ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Insert one row into a table.
     *
     * @param string $tableName Table to modify.
     * @param array<string, mixed> $properties Column names mapped to values.
     * @param bool $asTransaction Start and finish a transaction for this operation.
     * @return bool True when the row is inserted, otherwise false.
     */
    public function createOne(string $tableName, array $properties, bool $asTransaction = true): bool
    {
        if ($properties === []) {
            return false;
        }

        $fields = array_keys($properties);
        $query = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->identifier($tableName),
            implode(', ', array_map([$this, 'identifier'], $fields)),
            implode(', ', array_fill(0, count($fields), '?'))
        );

        return $this->executeWrite($query, array_values($properties), $asTransaction);
    }

    /**
     * Select every row from a table.
     *
     * @param string $tableName Table to read.
     * @return array<int, array<string, mixed>>|false Rows on success, otherwise false.
     */
    public function selectAll(string $tableName): array|false
    {
        return $this->fetchRows(sprintf('SELECT * FROM %s', $this->identifier($tableName)), []);
    }

    /**
     * Select the first row matching a primary-key value.
     *
     * @param string $tableName Table to read.
     * @param string $primaryKey Column used for filtering.
     * @param string $id Value matched against the primary key.
     * @return array<string, mixed>|false A row, an empty array when not found, or false on error.
     */
    public function selectOne(string $tableName, string $primaryKey, string $id): array|false
    {
        $rows = $this->fetchRows(
            sprintf(
                'SELECT * FROM %s WHERE %s = ? LIMIT 1',
                $this->identifier($tableName),
                $this->identifier($primaryKey)
            ),
            [$id]
        );

        return $rows === false ? false : ($rows[0] ?? []);
    }

    /**
     * Update one row by its primary-key value.
     *
     * @param string $tableName Table to modify.
     * @param string $primaryKey Column used for filtering.
     * @param string $id Value matched against the primary key.
     * @param array<string, mixed> $properties Columns mapped to replacement values.
     * @param bool $asTransaction Start and finish a transaction for this operation.
     * @return bool True when the statement executes, otherwise false.
     */
    public function updateOne(
        string $tableName,
        string $primaryKey,
        string $id,
        array $properties,
        bool $asTransaction = true
    ): bool {
        if ($properties === []) {
            return false;
        }

        $assignments = array_map(
            fn (string|int $field): string => sprintf('%s = ?', $this->identifier((string) $field)),
            array_keys($properties)
        );
        $query = sprintf(
            'UPDATE %s SET %s WHERE %s = ?',
            $this->identifier($tableName),
            implode(', ', $assignments),
            $this->identifier($primaryKey)
        );

        return $this->executeWrite($query, [...array_values($properties), $id], $asTransaction);
    }

    /**
     * Delete one row by its primary-key value.
     *
     * @param string $tableName Table to modify.
     * @param string $primaryKey Column used for filtering.
     * @param string $id Value matched against the primary key.
     * @param bool $asTransaction Start and finish a transaction for this operation.
     * @return bool True when the statement executes, otherwise false.
     */
    public function deleteOne(
        string $tableName,
        string $primaryKey,
        string $id,
        bool $asTransaction = true
    ): bool {
        $query = sprintf(
            'DELETE FROM %s WHERE %s = ?',
            $this->identifier($tableName),
            $this->identifier($primaryKey)
        );

        return $this->executeWrite($query, [$id], $asTransaction);
    }

    /**
     * Execute a custom prepared SQL statement.
     *
     * SELECT statements return rows; other statements return true on success.
     *
     * @param string $query SQL statement, optionally containing placeholders.
     * @param array<int|string, mixed> $values Positional or named placeholder values.
     * @return array<int, array<string, mixed>>|bool Rows for SELECT, true for writes, or false on error.
     */
    public function ownQuery(string $query, array $values = []): array|bool
    {
        try {
            $this->logSqlQuery($query, $values);
            $statement = $this->connection->prepare($query);
            $statement->execute($values);

            return preg_match('/^\s*SELECT\b/i', $query) === 1
                ? $statement->fetchAll(PDO::FETCH_ASSOC)
                : true;
        } catch (Throwable $error) {
            $this->logSqlError($error, $query, $values);
            return false;
        }
    }

    /**
     * Execute several operations inside one transaction.
     *
     * The callback should throw when an operation cannot continue. Existing
     * outer transactions are respected and are not committed by this method.
     *
     * @param callable(): mixed $operation Work to execute inside the transaction.
     * @return mixed The callback result, or false after rollback on error.
     */
    public function transaction(callable $operation): mixed
    {
        $startedTransaction = !$this->connection->inTransaction();

        try {
            if ($startedTransaction) {
                $this->connection->beginTransaction();
            }

            $result = $operation();

            if ($startedTransaction) {
                $this->connection->commit();
            }

            return $result;
        } catch (Throwable $error) {
            if ($startedTransaction && $this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            $this->logSqlError($error, 'TRANSACTION', []);
            return false;
        }
    }

    /**
     * Execute a write statement and optionally own its transaction.
     *
     * @param string $query Parameterized SQL statement.
     * @param array<int, mixed> $values Positional statement values.
     * @param bool $asTransaction Whether this method should start a transaction.
     * @return bool True on successful execution, otherwise false.
     */
    private function executeWrite(string $query, array $values, bool $asTransaction): bool
    {
        $startedTransaction = $asTransaction && !$this->connection->inTransaction();

        try {
            if ($startedTransaction) {
                $this->logSqlQuery('BEGIN', []);
                $this->connection->beginTransaction();
            }

            $this->logSqlQuery($query, $values);
            $statement = $this->connection->prepare($query);
            $statement->execute($values);

            if ($startedTransaction) {
                $this->logSqlQuery('COMMIT', []);
                $this->connection->commit();
            }

            return true;
        } catch (Throwable $error) {
            if ($startedTransaction && $this->connection->inTransaction()) {
                $this->logSqlQuery('ROLLBACK', []);
                $this->connection->rollBack();
            }

            $this->logSqlError($error, $query, $values);
            return false;
        }
    }

    /**
     * Execute a read statement and fetch associative rows.
     *
     * @param string $query Parameterized SQL statement.
     * @param array<int, mixed> $values Positional statement values.
     * @return array<int, array<string, mixed>>|false Rows on success, otherwise false.
     */
    private function fetchRows(string $query, array $values): array|false
    {
        try {
            $this->logSqlQuery($query, $values);
            $statement = $this->connection->prepare($query);
            $statement->execute($values);

            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $error) {
            $this->logSqlError($error, $query, $values);
            return false;
        }
    }

    /**
     * Validate an interpolated table or column identifier.
     *
     * Identifiers cannot be bound as PDO parameters, so invalid names are
     * rejected before they can be added to a SQL statement.
     *
     * @param string $identifier Table or column name.
     * @return string The validated identifier.
     * @throws PDOException When the identifier is not safe to interpolate.
     */
    private function identifier(string $identifier): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier) !== 1) {
            throw new PDOException('Invalid SQL identifier: ' . $identifier);
        }

        return $identifier;
    }

    /**
     * Write a failed SQL operation to the SQL logger.
     *
     * @param Throwable $error Exception raised by PDO or the operation.
     * @param string $query Parameterized SQL statement or transaction label.
     * @param array<int|string, mixed> $values Values supplied to the statement.
     * @return void
     */
    private function logSqlError(Throwable $error, string $query, array $values): void
    {
        $this->logger->error($error->getMessage(), [
            'sql' => $query,
            'parameters' => $values,
            'error_info' => $error instanceof PDOException ? $error->errorInfo : null,
        ]);
    }

    /**
     * Write a readable emulation of a SQL statement to the SQL logger.
     *
     * The interpolated statement is diagnostic output only; PDO still executes
     * the original prepared statement.
     *
     * @param string $query Parameterized SQL statement or transaction command.
     * @param array<int|string, mixed> $values Values supplied to the statement.
     * @return void
     */
    private function logSqlQuery(string $query, array $values): void
    {
        $this->logger->debug(SqlLogger::interpolateQuery($query, $values), [
            'sql' => $query,
            'parameters' => SqlLogger::formatBindings($values),
        ]);
    }
}
