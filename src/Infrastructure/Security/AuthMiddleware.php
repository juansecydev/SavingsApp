<?php
declare(strict_types=1);

namespace App\Infrastructure\Security;

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;

class AuthMiddleware implements IMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
    }
}
