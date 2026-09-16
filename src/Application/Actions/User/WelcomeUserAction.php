<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Actions\TwigAction;
use App\Infrastructure\Security\CSRFValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class WelcomeUserAction extends TwigAction
{
    public function __construct(LoggerInterface $logger, Twig $twig)
    {
        parent::__construct($logger, $twig);
    }

    protected function action(): Response
    {
        $user = $this->request->getAttribute('user');

        return $this->renderView('welcome.html.twig', [
            'user' => [
                'user_name' => $user->getFirstName(),
                'user_lastname' => $user->getLastName(),
                'user_email' => $user->getEmail(),
                'created_at' => null,
            ],
            'accounts' => [],
            'csrf_token' => CSRFValidator::getCSRFToken(),
        ]);
    }
}
