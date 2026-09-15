<?php

declare(strict_types=1);

namespace App\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use App\Infrastructure\Security\Session;

class LogoutAction
{
    public function __construct(private Twig $twig) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        Session::logout();
        return $this->twig->render($response, 'login.html.twig',);
    }
}