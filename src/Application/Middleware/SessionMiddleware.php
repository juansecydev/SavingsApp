<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Infrastructure\Security\Session;
use App\Domain\User\UserRepository;

class SessionMiddleware implements Middleware
{

    public function __construct(private UserRepository $userRepository) {}
    
    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        if(!Session::validateSession()){
            Session::logout();
            $response = new \Slim\Psr7\Response();
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $user = $this->userRepository->findUserById(Session::getData(Session::SESSION_KEY));

        if ($user === null) {
            Session::logout();
            $response = new \Slim\Psr7\Response();
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $request = $request->withAttribute('user', $user);
        return $handler->handle($request);
    }
}
