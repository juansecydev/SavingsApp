<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Currency;

use App\Domain\Currency\Currency;
use App\Domain\Currency\CurrencyRepository;
use App\Infrastructure\Persistence\Database\QueryBuilder;

/**
 * Retrieves currencies from the relational database.
 */
class DatabaseCurrencyRepository implements CurrencyRepository
{
    /**
     * @param QueryBuilder $queryBuilder Database query service used to read currency rows.
     */
    public function __construct(private QueryBuilder $queryBuilder)
    {
    }

    /**
     * Retrieve every currency required by the account-creation form.
     *
     * @return Currency[] Currencies ordered by name, or an empty array when the query fails.
     */
    public function findAll(): array
    {
        $rows = $this->queryBuilder->ownQuery(
            'SELECT currency_id, currency_name, currency_code, currency_symbol, currency_minor_units
            FROM currencies
            ORDER BY currency_name',
        );

        if ($rows === false) {
            return [];
        }

        return array_map(fn (array $row): Currency => $this->mapCurrency($row), $rows);
    }

    /**
     * Map a database currency row to its domain representation.
     *
     * @param array<string, mixed> $row Currency row returned by the database.
     * @return Currency Currency represented by the row.
     */
    private function mapCurrency(array $row): Currency
    {
        return new Currency(
            (int) $row['currency_id'],
            (string) $row['currency_name'],
            (string) $row['currency_code'],
            (string) $row['currency_symbol'],
            (int) $row['currency_minor_units'],
        );
    }
}
