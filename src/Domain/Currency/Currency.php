<?php

declare(strict_types=1);

namespace App\Domain\Currency;

/**
 * Represents a currency available when creating an account.
 */
class Currency
{
    /**
     * @param int $id Database identifier for the currency.
     * @param string $name Display name of the currency.
     * @param string $code ISO-style currency code.
     * @param string $symbol Display symbol for monetary amounts.
     * @param int $decimals Number of decimal places used by the currency.
     */
    public function __construct(
        private int $id,
        private string $name,
        private string $code,
        private string $symbol,
        private int $decimals
    ) {
    }

    /**
     * @return int Database identifier for the currency.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string Display name of the currency.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string ISO-style currency code.
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string Display symbol for monetary amounts.
     */
    public function getSymbol(): string
    {
        return $this->symbol;
    }

    /**
     * @return int Number of decimal places used by the currency.
     */
    public function getDecimals(): int
    {
        return $this->decimals;
    }
}
