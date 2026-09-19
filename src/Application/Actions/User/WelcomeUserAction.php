<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Actions\TwigAction;
use App\Infrastructure\Security\CSRFValidator;
use App\Infrastructure\Security\Session;
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
        $sessionPicture = Session::getData('user_profile_picture');

        // The image endpoint handles authorization, the default avatar, and its cache headers.
        $profilePictureId = is_array($sessionPicture)
            && is_string($sessionPicture['id'] ?? null)
            ? $sessionPicture['id']
            : 'default';

        return $this->renderView('welcome.html.twig', [
            'user' => [
                'user_name' => $user->getFirstName(),
                'user_lastname' => $user->getLastName(),
                'profile_picture' => $profilePictureId,
            ],
            'accounts' => [],
            'csrf_token' => CSRFValidator::getCSRFToken(),
        ]);
    }
}
