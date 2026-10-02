<?php

declare(strict_types=1);

namespace App\Application\Actions\Account;

use App\Application\Actions\TwigAction;
use App\Application\Services\AccountService;
use App\Domain\Account\Account;
use App\Infrastructure\Security\CSRFValidator;
use App\Domain\User\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class ViewAccountDetailAction extends TwigAction
{
    public function __construct(
        LoggerInterface $logger,
        Twig $twig,
        private AccountService $accountService
    ) {
        parent::__construct($logger, $twig);
    }

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

        return $this->renderView('accounts/detail.html.twig', [
            'account' => $this->formatAccountForView($account),
            'csrf_token' => CSRFValidator::getCSRFToken(),
            'errors' => [],
            'transactionTypes' => [],
            'old_transaction_amount' => '',
            'old_transaction_transaction_type_id' => '',
            'old_transaction_description' => '',
            'old_transaction_reference' => '',
        ]);
    }

    private function formatAccountForView(Account $account): array
    {
        $minorUnits = $account->getCurrencyMinorUnits() ?? 2;
        $divisor = pow(10, max(0, $minorUnits));

        return [
            'account_id' => $account->getId(),
            'account_name' => $account->getName(),
            'account_amount' => (float) ($account->getBalanceMinor() / $divisor),
            'currency' => [
                'currency_code' => $account->getCurrencyCode(),
                'currency_name' => $account->getCurrencyName(),
                'currency_symbol' => $account->getCurrencySymbol(),
                'currency_minor_units' => $account->getCurrencyMinorUnits(),
            ],
            'transactions' => [],
        ];
    }
}
