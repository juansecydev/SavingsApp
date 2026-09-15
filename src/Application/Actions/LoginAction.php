<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Domain\User\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use App\Infrastructure\Security\Session;

class LoginAction
{
    public function __construct(private Twig $twig) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        if ($request->getMethod() === 'GET') {
            return $this->twig->render($response, 'login.html.twig');
        }

        /** @var User $user */
        $user = $request->getAttribute('user');
        // Now we can create a session for the authenticated user and store their information in it.
        Session::login($user);
        return $response->withHeader('Location', '/welcome')->withStatus(302);

        /* return $this->twig->render($response, 'welcome.html.twig', [
            'user' => [
                'user_name' => $user->getFirstName(),
                'user_lastname' => $user->getLastName(),
                'user_email' => $user->getEmail(),
                'created_at' => null,
            ],
            'accounts' => [],
        ]); */
    }
}