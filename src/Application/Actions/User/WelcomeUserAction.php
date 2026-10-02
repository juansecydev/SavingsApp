<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Actions\TwigAction;
use App\Application\Services\AccountService;
use App\Domain\Account\Account;
use App\Infrastructure\Security\CSRFValidator;
use App\Infrastructure\Security\Session;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class WelcomeUserAction extends TwigAction
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
        $user = $this->request->getAttribute('user');
        $sessionPicture = Session::getData('user_profile_picture');

        $profilePictureId = is_array($sessionPicture)
            && is_string($sessionPicture['id'] ?? null)
            ? $sessionPicture['id']
            : 'default';

        $accounts = $user !== null && $user->getId() !== null
            ? $this->accountService->getAccountsByUserId((int) $user->getId())
            : [];

        return $this->renderView('welcome.html.twig', [
            'user' => [
                'user_name' => $user->getFirstName(),
                'user_lastname' => $user->getLastName(),
                'profile_picture' => $profilePictureId,
            ],
            'accounts' => array_map(fn (Account $account): array => $this->formatAccountForView($account), $accounts),
            'csrf_token' => CSRFValidator::getCSRFToken(),
        ]);
    }

    private function formatAccountForView(Account $account): array
    {
        return [
            'account_id' => $account->getId(),
            'account_name' => $account->getName(),
        ];
    }
}
