<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Domain\User\User;
use App\Infrastructure\Security\Session;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LoginAction
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('user');
        // Now we can create a session for the authenticated user and store their information in it.
        Session::login($user);
        return $response->withHeader('Location', '/welcome')->withStatus(302);
    }
}
