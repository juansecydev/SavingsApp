<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Actions\TwigAction;
use App\Infrastructure\Http\Sanitizer;
use App\Infrastructure\Http\Validator;
use App\Infrastructure\Security\CSRFValidator;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Validates submitted profile data without persisting changes yet.
 */
class ProfileDataUserAction extends TwigAction
{
    /**
     * Validate the profile form and render the profile view with its result.
     *
     * @return Response The profile form response.
     */
    protected function action(): Response
    {
        $user = $this->request->getAttribute('user');
        $formData = $this->getFormData();
        $formData = is_array($formData) ? $formData : [];

        $rules = [
            'user_id' => 'required|integer',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|string',
        ];

        if (is_string($formData['email'] ?? null)) {
            $rules['email'] .= '|email';
        }

        $validator = Validator::make($formData, $rules);

        $validator->validate();
        $errors = $validator->errors();

        if (!isset($errors['user_id']) && (int) $formData['user_id'] !== $user->getId()) {
            $errors['user_id'][] = 'El usuario no coincide con la sesión actual';
        }

        $profileData = [
            'id' => $user->getId(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getEmail(),
            'profile_picture' => $user->getProfilePicture(),
            'role_name' => $user->getRoleName(),
        ];

        if (empty($errors)) {
            $profileData['first_name'] = Sanitizer::string($formData['first_name']);
            $profileData['last_name'] = Sanitizer::string($formData['last_name']);
            $profileData['email'] = Sanitizer::email($formData['email']);
        }

        return $this->renderView('users/profile.html.twig', [
            'profile' => $profileData,
            'csrf_token' => CSRFValidator::getCSRFToken(),
            'errors' => $errors,
            'success' => empty($errors) ? 'Los datos son válidos. La actualización se implementará después.' : null,
        ]);
    }
}
