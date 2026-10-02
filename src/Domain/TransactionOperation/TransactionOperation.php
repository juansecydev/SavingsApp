<?php

declare(strict_types=1);

namespace App\Domain\TransactionOperation;

/**
 * Represents an operation that can be applied to an account transaction.
 */
class TransactionOperation
{
    /**
     * @param int $id Database identifier for the operation.
     * @param string $description Display name of the operation.
     * @param string $symbol Symbol displayed alongside the operation name.
     */
    public function __construct(
        private int $id,
        private string $description,
        private string $symbol
    ) {
    }

    /**
     * @return int Database identifier for the operation.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string Display name of the operation.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string Symbol displayed alongside the operation name.
     */
    public function getSymbol(): string
    {
        return $this->symbol;
    }
}