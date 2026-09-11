<?php

declare(strict_types=1);

namespace App\Application\Actions;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class WelcomeAction
{
    public function __construct(private Twig $twig, private PDO $db) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        return $this->twig->render($response, 'landing.html.twig');
    }
}