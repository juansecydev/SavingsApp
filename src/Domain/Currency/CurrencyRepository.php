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
}
