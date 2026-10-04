<?php

declare(strict_types=1);

namespace App\Application\Actions\Account;

use App\Application\Actions\Action;
use App\Application\Services\AccountService;
use App\Domain\User\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

/**
 * Deletes an account owned by the authenticated user and redirects to the welcome page.
 */
class DeleteAccountAction extends Action
{
    /**
     * @param LoggerInterface $logger Application logger used by the action.
     * @param AccountService $accountService Service used to delete an owned account.
     */
    public function __construct(
        LoggerInterface $logger,
        private AccountService $accountService
    ) {
        parent::__construct($logger);
    }

    /**
     * Delete the requested owned account; account transactions are removed by the FK cascade.
     *
     * @return Response Redirect to the welcome page, with an error indicator if persistence fails.
     */
    protected function action(): Response
    {
        /** @var User|null $user */
        $user = $this->request->getAttribute('user');
        $accountId = filter_var($this->args['id'] ?? null, FILTER_VALIDATE_INT);

        if ($user === null || $user->getId() === null || $accountId === false || $accountId <= 0) {
            return $this->redirectToWelcome();
        }

        $userId = (int) $user->getId();
        if (!$this->accountService->deleteAccountForUser($accountId, $userId)) {
            $this->logger->error('Account deletion failed.', [
                'account_id' => $accountId,
                'user_id' => $userId,
            ]);

            return $this->redirectToWelcome('?account_delete=failed');
        }

        return $this->redirectToWelcome();
    }

    /**
     * Return a redirect response to the welcome page.
     *
     * @param string $query Optional query string to indicate a failed operation.
     * @return Response HTTP 303 redirect response.
     */
    private function redirectToWelcome(string $query = ''): Response
    {
        return $this->response
            ->withHeader('Location', '/welcome' . $query)
            ->withStatus(303);
    }
}
