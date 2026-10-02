<?php

declare(strict_types=1);

namespace App\Domain\Currency;

/**
 * Provides the currencies available to the application.
 */
interface CurrencyRepository
{
    /**
     * Retrieve every available currency in display order.
     *
     * @return Currency[] Currencies ordered by their display name.
     */
    public function findAll(): array;

    /**
     * Get the currency data of a specific currency by id.
     *
     * @return ?Currency The currency model instance representation if exists, else return null.
     */
    public function findOne(string|int $id): ?Currency;
}
