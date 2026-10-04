<?php

declare(strict_types=1);

use App\Application\Middleware\LoginMiddleware;
use App\Application\Middleware\SessionMiddleware;
use App\Application\Middleware\ProfilePictureProcessMiddleware;
use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\ViewLoginAction;
use App\Application\Actions\LoginAction;
use App\Application\Actions\LogoutAction;
use App\Application\Actions\WelcomeAction;
use App\Application\Actions\User\WelcomeUserAction;
use App\Application\Actions\User\ViewProfileUserAction;
use App\Application\Actions\User\ProfileDataUserAction;
use App\Application\Actions\User\ProcessProfilePictureAction;
use App\Application\Actions\User\GetProfilePictureAction;
use App\Application\Actions\Account\ViewCreateAccountAction;
use App\Application\Actions\Account\CreateAccountAction;
use App\Application\Actions\Account\ViewAccountDetailAction;
use App\Application\Actions\Account\GetAccountDataAction;
use App\Application\Actions\Account\AccountTransactionCreateAction;
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
    $app->get('/login', ViewLoginAction::class)->setName('login')->add(LoginMiddleware::class);
    $app->post('/login', LoginAction::class)->add(LoginMiddleware::class);
    $app->post('/logout', LogoutAction::class)->setName('logout');

    // Group routes for session middleware
    $app->group('', function (Group $group) {

        $group->get('/welcome', WelcomeUserAction::class)->setName('welcome');
        $group->get('/profile', ViewProfileUserAction::class)->setName('profile');
        $group->post('/profile', ProfileDataUserAction::class)->setName('profile.update');

        $group->get('/accounts', ViewCreateAccountAction::class)->setName('account');
        $group->post('/accounts', CreateAccountAction::class)->setName('account');
        $group->get('/accounts/{id}', ViewAccountDetailAction::class)->setName('account.detail');
        
        $group->group('/api/v1', function (Group $apiGroup) {
            $apiGroup->get('/account/data/{id}', GetAccountDataAction::class)
                ->setName('account.data');
            $apiGroup->post('/account/transaction/create', AccountTransactionCreateAction::class)
                ->setName('account.transaction.create');
            $apiGroup->post('/profile-picture/process', ProcessProfilePictureAction::class)
                ->add(ProfilePictureProcessMiddleware::class)
                ->setName('profile-picture.process');
            $apiGroup->get('profile-picture/{id}', GetProfilePictureAction::class)
                ->setName('profile-picture.show');
        });
    })->add(SessionMiddleware::class);

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
    });
};
