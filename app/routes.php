<?php

declare(strict_types=1);

use App\Application\Middleware\LoginMiddleware;
use App\Application\Middleware\SessionMiddleware;
use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\ViewLoginAction;
use App\Application\Actions\LoginAction;
use App\Application\Actions\LogoutAction;
use App\Application\Actions\WelcomeAction;
use App\Application\Actions\User\WelcomeUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', WelcomeAction::class);
    $app->get('/login', ViewLoginAction::class)->setName('login');
    $app->post('/login', LoginAction::class)->add(LoginMiddleware::class);
    $app->post('/logout', LogoutAction::class)->setName('logout');
    
    // Group routes for session middleware
    $app->group('', function (Group $group) {
        $group->get('/welcome', WelcomeUserAction::class);
    })->add(SessionMiddleware::class);

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
    });
};
