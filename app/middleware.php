<?php

declare(strict_types=1);

use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use App\Application\Middleware\CsrfMiddleware;

return function (App $app) {
    $app->add(TwigMiddleware::createFromContainer($app, Twig::class));
    $app->add(CsrfMiddleware::class);
};
