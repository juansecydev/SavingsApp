<?php

declare(strict_types=1);

namespace App\Application\Actions\Account;

use App\Application\Actions\Action;
use App\Application\Services\AccountTransactionService;
use App\Domain\User\User;
use App\Infrastructure\Security\CSRFValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

/**
 * Deletes an authenticated user's account transaction and reverses its balance effect.
 */
class AccountTransactionDeleteAction extends Action
{
    /**
     * @param LoggerInterface $logger Application logger used by the action.
     * @param AccountTransactionService $accountTransactionService Service that atomically reverses and deletes
     *        transactions.
     */
    public function __construct(
        LoggerInterface $logger,
        private AccountTransactionService $accountTransactionService
    ) {
        parent::__construct($logger);
    }

    /**
     * Validate ownership and CSRF protection, then delete the requested account transaction.
     *
     * @return Response JSON success or error data with an appropriate HTTP status.
     */
    protected function action(): Response
    {
        $user = $this->request->getAttribute('user');
        if (!$user instanceof User || $user->getId() === null) {
            return $this->respondWithData(['error' => 'Authentication is required.'], 401);
        }

        $accountId = filter_var($this->args['accountId'] ?? null, FILTER_VALIDATE_INT);
        $transactionId = filter_var($this->args['transactionId'] ?? null, FILTER_VALIDATE_INT);
        if ($accountId === false || $accountId <= 0) {
            return $this->respondWithData(['error' => 'Invalid account identifier.'], 400);
        }
        if ($transactionId === false || $transactionId <= 0) {
            return $this->respondWithData(['error' => 'Invalid transaction identifier.'], 400);
        }

        $csrfToken = $this->request->getHeaderLine('X-CSRF-Token');
        if ($csrfToken === '') {
            $formData = $this->getFormData();
            $csrfToken = is_array($formData) && is_string($formData['csrf_token'] ?? null)
                ? $formData['csrf_token']
                : '';
        }
        if (!CSRFValidator::validateCSRFToken($csrfToken)) {
            return $this->respondWithData(['error' => 'Your session expired. Refresh the page and try again.'], 403);
        }

        $deleted = $this->accountTransactionService->deleteTransactionForUser(
            $user->getId(),
            $accountId,
            $transactionId,
        );
        if ($deleted === null) {
            return $this->respondWithData(['error' => 'Transaction not found for this account.'], 404);
        }
        if (!$deleted) {
            $this->logger->error('Unable to delete account transaction.', [
                'account_id' => $accountId,
                'transaction_id' => $transactionId,
                'user_id' => $user->getId(),
            ]);

            return $this->respondWithData(['error' => 'Unable to delete the transaction. Please try again.'], 500);
        }

        return $this->respondWithData(['message' => 'Transaction deleted successfully.']);
    }
}
