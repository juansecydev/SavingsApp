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
     * Create an account and record its opening balance as one atomic operation.
     *
     * The account starts at zero within the transaction, then the opening entry
     * updates its balance. A negative opening balance is recorded as an egress.
     *
     * @param int $userId Authenticated account owner.
     * @param string $accountTitle New account title.
     * @param int $currencyId Currency attached to the new account.
     * @param int $balanceMinor Opening balance in minor currency units.
     * @param TransactionOperation $operation Income or egress operation matching the balance sign.
     * @return bool True when both inserts and the balance update commit; false after failure.
     */
    public function createAccountWithOpeningTransaction(
        int $userId,
        string $accountTitle,
        int $currencyId,
        int $balanceMinor,
        TransactionOperation $operation
    ): bool {
        $expectedSymbol = $balanceMinor < 0 ? '-' : '+';
        if ($operation->getSymbol() !== $expectedSymbol || $balanceMinor === PHP_INT_MIN) {
            return false;
        }

        $result = $this->queryBuilder->transaction(function () use (
            $userId,
            $accountTitle,
            $currencyId,
            $balanceMinor,
            $operation
        ): bool {
            $accountId = $this->accountService->createAccount($userId, $accountTitle, $currencyId, 0);

            if ($accountId === false) {
                throw new RuntimeException('Unable to persist the new account.');
            }

            if (
                !$this->createTransaction(
                    $userId,
                    $accountId,
                    $operation,
                    abs($balanceMinor),
                    'Account created',
                    null,
                )
            ) {
                throw new RuntimeException('Unable to record the account opening transaction.');
            }

            return true;
        });

        return $result === true;
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

    /**
     * Delete a transaction owned by the user and reverse its effect on the account balance atomically.
     *
     * @param int $userId Authenticated account owner.
     * @param int $accountId Account that must own the transaction.
     * @param int $transactionId Transaction to remove.
     * @return bool|null True when deletion commits, null when the account or transaction is not found,
     *        and false when persistence or balance processing fails.
     */
    public function deleteTransactionForUser(int $userId, int $accountId, int $transactionId): bool|null
    {
        $result = $this->queryBuilder->transaction(function () use ($userId, $accountId, $transactionId): ?bool {
            $account = $this->accountService->getAccountByIdForUserForUpdate($accountId, $userId);
            if (!$account instanceof Account) {
                return null;
            }

            $transaction = $this->accountTransactionRepository->findByIdForAccount($transactionId, $accountId);
            if ($transaction === false) {
                throw new RuntimeException('Unable to retrieve the account transaction.');
            }
            if ($transaction === null) {
                return null;
            }

            $amountMinor = filter_var($transaction['account_transaction_amount'] ?? null, FILTER_VALIDATE_INT);
            $symbol = $transaction['transaction_operation_symbol'] ?? null;
            if ($amountMinor === false || $amountMinor <= 0 || !is_string($symbol)) {
                throw new RuntimeException('The account transaction data is invalid.');
            }

            $currencyCode = $account->getCurrencyCode();
            if ($currencyCode === null || $currencyCode === '') {
                throw new RuntimeException('Account currency is unavailable.');
            }

            $balance = Money::ofMinor($account->getBalanceMinor(), $currencyCode);
            $amount = Money::ofMinor($amountMinor, $currencyCode);
            if ($symbol === '+') {
                $newBalance = $balance->minus($amount);
            } elseif ($symbol === '-') {
                $newBalance = $balance->plus($amount);
            } else {
                throw new RuntimeException('Unsupported transaction operation symbol.');
            }

            if (!$this->accountTransactionRepository->deleteForAccount($transactionId, $accountId)) {
                throw new RuntimeException('Unable to delete the account transaction.');
            }

            if (!$this->accountService->updateBalance($accountId, $newBalance->getMinorAmount()->toInt())) {
                throw new RuntimeException('Unable to reverse the account transaction balance.');
            }

            return true;
        });

        return $result === true ? true : ($result === null ? null : false);
    }
}
