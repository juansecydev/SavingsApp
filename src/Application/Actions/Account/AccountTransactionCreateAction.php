<?php

declare(strict_types=1);

namespace App\Application\Actions\Account;

use App\Application\Actions\Action;
use App\Application\Services\AccountService;
use App\Application\Services\AccountTransactionService;
use App\Application\Services\TransactionOperationService;
use App\Domain\TransactionOperation\TransactionOperation;
use App\Domain\User\User;
use App\Infrastructure\Http\Sanitizer;
use App\Infrastructure\Security\CSRFValidator;
use Brick\Math\Exception\MathException;
use Brick\Money\Exception\MoneyException;
use Brick\Money\Money;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Validates and atomically persists an account transaction submitted over AJAX.
 */
class AccountTransactionCreateAction extends Action
{
    /**
     * @param LoggerInterface $logger Application logger used by the action.
     * @param AccountService $accountService Service used to verify account ownership and currency.
     * @param AccountTransactionService $accountTransactionService Service that atomically records transactions
     *        and updates balances.
     * @param TransactionOperationService $transactionOperationService Service used to resolve transaction operations.
     */
    public function __construct(
        LoggerInterface $logger,
        private AccountService $accountService,
        private AccountTransactionService $accountTransactionService,
        private TransactionOperationService $transactionOperationService
    ) {
        parent::__construct($logger);
    }

    /**
     * Validate the request, persist the transaction, and return a JSON result for the account page.
     *
     * @return Response JSON success or error data with an appropriate HTTP status.
     */
    protected function action(): Response
    {
        $user = $this->request->getAttribute('user');
        $userId = $user instanceof User ? $user->getId() : null;
        if ($userId === null) {
            return $this->respondWithData(['error' => 'Authentication is required.'], 401);
        }

        $formData = $this->getFormData();
        if (!is_array($formData)) {
            return $this->respondWithData(['error' => 'Invalid transaction form data.'], 400);
        }

        $csrfToken = $this->request->getHeaderLine('X-CSRF-Token');
        if ($csrfToken === '') {
            $csrfToken = is_string($formData['csrf_token'] ?? null) ? $formData['csrf_token'] : '';
        }
        if (!CSRFValidator::validateCSRFToken($csrfToken)) {
            return $this->respondWithData(['error' => 'Your session expired. Refresh the page and try again.'], 403);
        }

        $accountId = filter_var($formData['account_id'] ?? null, FILTER_VALIDATE_INT);
        $operationId = filter_var($formData['transaction_operation_id'] ?? null, FILTER_VALIDATE_INT);
        $rawAmount = $formData['transaction_amount'] ?? null;
        $rawTitle = $formData['transaction_description'] ?? null;
        $rawReference = $formData['transaction_reference'] ?? '';

        if ($accountId === false || $accountId <= 0) {
            return $this->respondWithData(['error' => 'Invalid account identifier.'], 400);
        }
        if ($operationId === false || $operationId <= 0) {
            return $this->respondWithData(['error' => 'Select a valid transaction operation.'], 422);
        }
        if (!is_string($rawAmount) && !is_numeric($rawAmount)) {
            return $this->respondWithData(['error' => 'Enter a valid transaction amount.'], 422);
        }
        if (!is_string($rawTitle) || !is_string($rawReference)) {
            return $this->respondWithData(['error' => 'Enter valid transaction details.'], 422);
        }

        $title = Sanitizer::string($rawTitle);
        $reference = Sanitizer::string($rawReference);
        $normalizedAmount = $this->normalizeAmount((string) $rawAmount);
        if ($normalizedAmount === null || preg_match('/^0+(?:\.0+)?$/', $normalizedAmount) === 1) {
            return $this->respondWithData(['error' => 'The amount must be a positive valid number.'], 422);
        }
        if ($title === '' || $this->characterLength($title) > 30) {
            return $this->respondWithData(
                ['error' => 'The description is required and cannot exceed 30 characters.'],
                422,
            );
        }
        if ($this->characterLength($reference) > 50) {
            return $this->respondWithData(['error' => 'The reference cannot exceed 50 characters.'], 422);
        }

        $account = $this->accountService->getAccountByIdForUser($accountId, $userId);
        if ($account === null) {
            return $this->respondWithData(['error' => 'Account not found.'], 404);
        }
        $currencyCode = $account->getCurrencyCode();
        if ($currencyCode === null || $currencyCode === '') {
            $this->logger->error('Account currency is unavailable.', ['account_id' => $accountId]);

            return $this->respondWithData(['error' => 'Unable to process the transaction.'], 500);
        }

        $operation = $this->transactionOperationService->getTransactionOperationById($operationId);
        if ($operation === false) {
            $this->logger->error('Unable to retrieve the requested transaction operation.', [
                'transaction_operation_id' => $operationId,
            ]);

            return $this->respondWithData(['error' => 'Unable to process the transaction.'], 500);
        }
        if (!$operation instanceof TransactionOperation || !in_array($operation->getSymbol(), ['+', '-'], true)) {
            return $this->respondWithData(['error' => 'Select a valid transaction operation.'], 422);
        }

        try {
            $amountMinor = Money::of($normalizedAmount, $currencyCode)->getMinorAmount()->toInt();
        } catch (MoneyException | MathException $exception) {
            return $this->respondWithData(
                ['error' => 'The amount has too many decimal places for this currency.'],
                422,
            );
        }

        try {
            $created = $this->accountTransactionService->createTransaction(
                $userId,
                $accountId,
                $operation,
                $amountMinor,
                $title,
                $reference === '' ? null : $reference,
            );
        } catch (Throwable $exception) {
            $this->logger->error('Unable to create account transaction.', [
                'account_id' => $accountId,
                'exception' => $exception,
            ]);

            return $this->respondWithData(['error' => 'Unable to create the transaction. Please try again.'], 500);
        }

        if (!$created) {
            $this->logger->error('Account transaction was rolled back.', ['account_id' => $accountId]);

            return $this->respondWithData(['error' => 'Unable to create the transaction. Please try again.'], 500);
        }

        return $this->respondWithData(['message' => 'Transaction created successfully.']);
    }

    /**
     * Normalize a non-negative decimal amount accepted by Brick Money.
     *
     * @param string $amount Submitted amount.
     * @return string|null Normalized amount, or null when the input is malformed.
     */
    private function normalizeAmount(string $amount): ?string
    {
        $normalized = str_replace(',', '.', trim($amount));

        return preg_match('/^\d+(?:\.\d+)?$/', $normalized) === 1 ? $normalized : null;
    }

    /**
     * Count UTF-8 characters without requiring an optional PHP extension.
     *
     * @param string $value Text to measure.
     * @return int Character count, or byte count when the input is not valid UTF-8.
     */
    private function characterLength(string $value): int
    {
        $characterCount = preg_match_all('/./us', $value);

        return $characterCount === false ? strlen($value) : $characterCount;
    }
}
