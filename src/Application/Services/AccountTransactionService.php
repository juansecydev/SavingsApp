<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\AccountTransaction\AccountTransactionRepository;
use App\Domain\Account\Account;
use App\Domain\TransactionOperation\TransactionOperation;
use App\Infrastructure\Persistence\Database\QueryBuilder;
use Brick\Money\Money;
use RuntimeException;

/**
 * Provides account transaction data to application actions.
 */
class AccountTransactionService
{
    /**
     * @param AccountTransactionRepository $accountTransactionRepository Repository used to retrieve
     *        and store account transactions.
     * @param AccountService $accountService Service used to lock owned accounts and update balances.
     * @param QueryBuilder $queryBuilder Database transaction coordinator shared by persistence repositories.
     */
    public function __construct(
        private AccountTransactionRepository $accountTransactionRepository,
        private AccountService $accountService,
        private QueryBuilder $queryBuilder
    ) {
    }

    /**
     * Retrieve transactions for one account.
     *
     * @param int $accountId Account whose transactions are requested.
     * @return array<int, array<string, mixed>>|false Transaction rows, or false when retrieval fails.
     */
    public function getTransactionsByAccountId(int $accountId): array|false
    {
        return $this->accountTransactionRepository->findByAccountId($accountId);
    }

    /**
     * Create a transaction and update its account balance atomically.
     *
     * The account row is locked before calculating its new balance, so concurrent
     * transactions cannot overwrite one another. Negative balances are supported.
     *
     * @param int $userId Authenticated account owner.
     * @param int $accountId Account to update.
     * @param TransactionOperation $operation Operation determining whether to add or subtract.
     * @param int $amountMinor Positive transaction amount in minor units.
     * @param string $title Transaction description.
     * @param string|null $reference Optional transaction reference.
     * @return bool True when both writes commit; false after rollback on failure.
     */
    public function createTransaction(
        int $userId,
        int $accountId,
        TransactionOperation $operation,
        int $amountMinor,
        string $title,
        ?string $reference
    ): bool {
        $result = $this->queryBuilder->transaction(function () use (
            $userId,
            $accountId,
            $operation,
            $amountMinor,
            $title,
            $reference
        ): bool {
            $account = $this->accountService->getAccountByIdForUserForUpdate($accountId, $userId);

            if (!$account instanceof Account) {
                throw new RuntimeException('Account not found for transaction creation.');
            }

            $currencyCode = $account->getCurrencyCode();
            if ($currencyCode === null || $currencyCode === '') {
                throw new RuntimeException('Account currency is unavailable.');
            }

            $balance = Money::ofMinor($account->getBalanceMinor(), $currencyCode);
            $amount = Money::ofMinor($amountMinor, $currencyCode);

            if ($operation->getSymbol() === '+') {
                $newBalance = $balance->plus($amount);
            } elseif ($operation->getSymbol() === '-') {
                $newBalance = $balance->minus($amount);
            } else {
                throw new RuntimeException('Unsupported transaction operation symbol.');
            }

            if (
                !$this->accountTransactionRepository->create(
                    $accountId,
                    $operation->getId(),
                    $amountMinor,
                    $title,
                    $reference,
                )
            ) {
                throw new RuntimeException('Unable to register account transaction.');
            }

            if (
                !$this->accountService->updateBalance(
                    $accountId,
                    $newBalance->getMinorAmount()->toInt(),
                )
            ) {
                throw new RuntimeException('Unable to update account balance.');
            }

            return true;
        });

        return $result === true;
    }
}
