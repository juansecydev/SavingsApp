<?php

declare(strict_types=1);

namespace App\Application\Actions\Account;

use App\Application\Actions\TwigAction;
use App\Application\Services\AccountService;
use App\Application\Services\TransactionOperationService;
use App\Domain\TransactionOperation\TransactionOperation;
use App\Domain\User\User;
use App\Infrastructure\Security\CSRFValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class ViewAccountDetailAction extends TwigAction
{
    /**
     * @param LoggerInterface $logger Application logger used by the action.
     * @param Twig $twig Twig renderer configured with the application's templates.
     * @param AccountService $accountService Service used to load the owned account.
     * @param TransactionOperationService $transactionOperationService Service used to load transaction operations.
     */
    public function __construct(
        LoggerInterface $logger,
        Twig $twig,
        private AccountService $accountService,
        private TransactionOperationService $transactionOperationService
    ) {
        parent::__construct($logger, $twig);
    }

    /**
     * Render an owned account's name and the page's transaction form data.
     *
     * @return Response Account detail response or redirect when the account is unavailable.
     */
    protected function action(): Response
    {
        /** @var User $user */
        $user = $this->request->getAttribute('user');
        $accountId = (int) ($this->args['id'] ?? 0);

        if ($user === null || $user->getId() === null || $accountId <= 0) {
            return $this->response
                ->withHeader('Location', '/welcome')
                ->withStatus(302);
        }

        $account = $this->accountService->getAccountByIdForUser($accountId, (int) $user->getId());

        if ($account === null) {
            return $this->response
                ->withHeader('Location', '/welcome')
                ->withStatus(302);
        }

        $transactionOperations = array_map(
            static fn (TransactionOperation $operation): array => [
                'transaction_operation_id' => $operation->getId(),
                'transaction_operation_description' => $operation->getDescription(),
                'transaction_operation_symbol' => $operation->getSymbol(),
            ],
            $this->transactionOperationService->getTransactionOperations(),
        );

        return $this->renderView('accounts/detail.html.twig', [
            'account' => [
                'account_id' => $account->getId(),
                'account_name' => $account->getName(),
            ],
            'csrf_token' => CSRFValidator::getCSRFToken(),
            'errors' => [],
            'transactionOperations' => $transactionOperations,
            'old_transaction_amount' => '',
            'old_transaction_operation_id' => '',
            'old_transaction_description' => '',
            'old_transaction_reference' => '',
        ]);
    }
}
