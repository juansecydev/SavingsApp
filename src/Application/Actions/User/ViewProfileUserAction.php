<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Actions\TwigAction;
use App\Infrastructure\Security\CSRFValidator;
use App\Infrastructure\Security\Session;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Renders the authenticated user's profile form.
 */
class ViewProfileUserAction extends TwigAction
{
    /**
     * Render the current user's profile data.
     *
     * @return Response The profile view response.
     */
    protected function action(): Response
    {
        $user = $this->request->getAttribute('user');
        $sessionPicture = Session::getData('user_profile_picture');
        $profilePictureId = is_array($sessionPicture)
            && is_string($sessionPicture['id'] ?? null)
            ? $sessionPicture['id']
            : 'default';

        return $this->renderView('users/profile.html.twig', [
            'profile' => [
                'id' => $user->getId(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'email' => $user->getEmail(),
                'profile_picture' => $profilePictureId,
                'role_name' => $user->getRoleName(),
            ],
            'csrf_token' => CSRFValidator::getCSRFToken(),
        ]);
    }
}
