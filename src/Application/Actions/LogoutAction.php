<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Infrastructure\Security\Session;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LogoutAction
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        Session::logout();
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
