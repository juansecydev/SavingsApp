<?php

declare(strict_types=1);

namespace App\Application\Actions\Account;

use App\Application\Actions\Action;
use App\Application\Services\AccountService;
use App\Application\Services\AccountTransactionService;
use App\Domain\User\User;
use Brick\Money\Money;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Returns an authenticated user's account balance, currency, and transactions.
 */
class GetAccountDataAction extends Action
{
    /**
     * @param \Psr\Log\LoggerInterface $logger Application logger used by the action.
     * @param AccountService $accountService Service used to find accounts owned by the user.
     * @param AccountTransactionService $accountTransactionService Service used to retrieve account transactions.
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        private AccountService $accountService,
        private AccountTransactionService $accountTransactionService
    ) {
        parent::__construct($logger);
    }

    /**
     * Return the account data required by the account-detail page.
     *
     * @return Response JSON account data, or a JSON error with an appropriate HTTP status.
     */
    protected function action(): Response
    {
        $user = $this->request->getAttribute('user');
        $accountId = filter_var($this->args['id'] ?? null, FILTER_VALIDATE_INT);

        if (!$user instanceof User || $user->getId() === null) {
            return $this->respondWithData(['error' => 'Authentication is required.'], 401);
        }

        if ($accountId === false || $accountId <= 0) {
            return $this->respondWithData(['error' => 'Invalid account identifier.'], 400);
        }

        $account = $this->accountService->getAccountByIdForUser($accountId, $user->getId());

        if ($account === null) {
            return $this->respondWithData(['error' => 'Account not found.'], 404);
        }

        $currencyCode = $account->getCurrencyCode();

        if ($currencyCode === null || $currencyCode === '') {
            $this->logger->error('Account currency is unavailable.', ['account_id' => $accountId]);

            return $this->respondWithData(['error' => 'Unable to load account data.'], 500);
        }

        $transactionRows = $this->accountTransactionService->getTransactionsByAccountId($accountId);

        if ($transactionRows === false || !is_array($transactionRows)) {
            $this->logger->error('Unable to retrieve account transactions.', ['account_id' => $accountId]);

            return $this->respondWithData(['error' => 'Unable to load account data.'], 500);
        }

        $transactions = array_map(
            static fn (array $row): array => [
                'id' => (int) $row['account_transaction_id'],
                'date' => (string) $row['account_transaction_created_at'],
                'type' => (string) $row['transaction_operation_description'],
                'symbol' => (string) $row['transaction_operation_symbol'],
                'amount' => (string) Money::ofMinor(
                    (int) $row['account_transaction_amount'],
                    $currencyCode,
                )->getAmount(),
                'description' => (string) $row['account_transaction_title'],
                'reference' => (string) ($row['account_transaction_reference'] ?? ''),
            ],
            $transactionRows,
        );

        return $this->respondWithData([
            'account' => [
                'id' => $account->getId(),
                'name' => $account->getName(),
                'amount' => (string) Money::ofMinor($account->getBalanceMinor(), $currencyCode)->getAmount(),
                'currency' => [
                    'code' => $currencyCode,
                    'name' => $account->getCurrencyName(),
                    'symbol' => $account->getCurrencySymbol(),
                    'minorUnits' => $account->getCurrencyMinorUnits() ?? 2,
                ],
            ],
            'transactions' => $transactions,
        ]);
    }
}
