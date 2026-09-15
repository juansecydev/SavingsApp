<?php

declare(strict_types=1);

namespace App\Application\Actions\User;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class WelcomeUserAction
{

    public function __construct(private Twig $twig) {}
    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        return $this->twig->render($response, 'welcome.html.twig', [
            'user' => [
                'user_name' => $user->getFirstName(),
                'user_lastname' => $user->getLastName(),
                'user_email' => $user->getEmail(),
                'created_at' => null,
            ],
            'accounts' => [],
        ]);
    }
}
