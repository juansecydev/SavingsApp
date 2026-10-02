<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Currency\Currency;
use App\Domain\Currency\CurrencyRepository;

/**
 * Exposes currencies used by application features.
 */
class CurrencyService
{
    /**
     * @param CurrencyRepository $currencyRepository Repository used to retrieve currencies.
     */
    public function __construct(private CurrencyRepository $currencyRepository)
    {
    }

    /**
     * Get currencies available when creating an account.
     *
     * @return Currency[] Currencies ordered by display name.
     */
    public function getCurrencies(): array
    {
        return $this->currencyRepository->findAll();
    }

    /**
     * Get specific currency data of a currency by id.
     *
     * @return ?Currency Currency model instance if exists, else return null.
     */
    public function getCurrency(string|int $id): ?Currency
    {
        return $this->currencyRepository->findOne($id);
    }

}
