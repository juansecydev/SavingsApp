<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Actions\TwigAction;
use App\Application\Services\UserService;
use App\Domain\User\User;
use App\Infrastructure\Files\ProfilePictureUploadService;
use App\Infrastructure\Http\Sanitizer;
use App\Infrastructure\Http\Validator;
use App\Infrastructure\Security\CSRFValidator;
use App\Infrastructure\Security\Session;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Validates and persists editable data for the authenticated user's profile.
 */
class ProfileDataUserAction extends TwigAction
{
    /**
     * @param \Psr\Log\LoggerInterface $logger Application logger used by the action.
     * @param \Slim\Views\Twig $twig Twig renderer configured with the application's templates.
     * @param UserService $userService Service used to persist profile changes.
     * @param ProfilePictureUploadService $uploadService Service used to persist a selected temporary profile picture.
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Slim\Views\Twig $twig,
        private UserService $userService,
        private ProfilePictureUploadService $uploadService
    ) {
        parent::__construct($logger, $twig);
    }

    /**
     * Validate the profile form, render errors, or save valid data and redirect.
     *
     * @return Response The profile form with validation errors, or a redirect to the welcome page.
     */
    protected function action(): Response
    {
        /** @var User $user */
        $user = $this->request->getAttribute('user');
        $formData = $this->getFormData();
        $formData = is_array($formData) ? $formData : [];
        $sessionPicture = Session::getData('user_profile_picture');
        $profilePictureId = is_array($sessionPicture) && is_string($sessionPicture['id'] ?? null)
            ? $sessionPicture['id']
            : 'default';

        $profileData = [
            'id' => $user->getId(),
            'first_name' => Sanitizer::string($formData['first_name'] ?? $user->getFirstName()),
            'last_name' => Sanitizer::string($formData['last_name'] ?? $user->getLastName()),
            'email' => Sanitizer::email($formData['email'] ?? $user->getEmail()),
            'profile_picture' => $profilePictureId,
            'role_name' => $user->getRoleName(),
        ];

        $rules = [
            'user_id' => 'required|integer',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|string',
        ];

        $rules['email'] .= '|email';

        $validatedData = [
            'user_id' => $formData['user_id'] ?? null,
            'first_name' => $profileData['first_name'],
            'last_name' => $profileData['last_name'],
            'email' => $profileData['email'],
        ];

        $validator = Validator::make($validatedData, $rules);

        $validator->validate();
        $errors = $validator->errors();

        if (!isset($errors['user_id']) && (int) $formData['user_id'] !== $user->getId()) {
            $errors['user_id'][] = 'El usuario no coincide con la sesión actual';
        }

        if (empty($errors)) {
            $existingUser = $this->userService->getUserByEmail($profileData['email']);
            if ($existingUser !== null && $existingUser->getId() !== $user->getId()) {
                $errors['email'][] = 'El correo electrónico ya está en uso';
            }
        }

        if (!empty($errors)) {
            return $this->renderView('users/profile.html.twig', [
                'profile' => $profileData,
                'csrf_token' => CSRFValidator::getCSRFToken(),
                'errors' => $errors,
            ]);
        }

        if (!$this->hasProfileChanges(
            $user,
            $profileData,
            $formData['profile_picture_id'] ?? null,
            $sessionPicture,
        )) {
            return $this->renderView('users/profile.html.twig', [
                'profile' => $profileData,
                'csrf_token' => CSRFValidator::getCSRFToken(),
                'info' => 'No hay cambios para actualizar.',
            ]);
        }

        try {
            $profilePictureUpdate = $this->resolveProfilePictureUpdate(
                $formData['profile_picture_id'] ?? null,
                $sessionPicture,
            );
            $updatedUser = $this->userService->updateProfile(
                $user,
                $profileData['first_name'],
                $profileData['last_name'],
                $profileData['email'],
                $profilePictureUpdate['path'],
                $profilePictureUpdate['should_update'],
            );
        } catch (\Throwable $exception) {
            $this->logger->error('Profile update failed while handling the profile picture.', [
                'exception' => $exception,
            ]);

            $updatedUser = null;
        }

        if ($updatedUser === null) {
            return $this->renderView('users/profile.html.twig', [
                'profile' => $profileData,
                'csrf_token' => CSRFValidator::getCSRFToken(),
                'errors' => ['profile' => ['No fue posible actualizar el perfil. Inténtalo nuevamente.']],
            ]);
        }

        // Refresh the session because SessionMiddleware reads the authenticated user from it.
        Session::login($updatedUser);

        return $this->response
            ->withHeader('Location', '/welcome')
            ->withStatus(302);
    }

    /**
     * Resolve the submitted FilePond identifier to the intended picture-column update.
     *
     * An absent, unchanged, or unknown identifier does not change the database column.
     * The literal removed clears it. A matching temporary-upload ID is moved to permanent
     * storage and produces the relative path stored in users.user_profile_picture.
     *
     * @param mixed $submittedPictureId Value submitted by the profile form.
     * @param mixed $sessionPicture Current picture metadata from the session.
     * @return array{should_update: bool, path: string|null} Profile-picture update instruction.
     * @throws \Throwable When a selected temporary picture cannot be stored permanently.
     */
    private function resolveProfilePictureUpdate(mixed $submittedPictureId, mixed $sessionPicture): array
    {
        if (!is_string($submittedPictureId) || $submittedPictureId === '') {
            return ['should_update' => false, 'path' => null];
        }

        $currentPictureId = is_array($sessionPicture) && is_string($sessionPicture['id'] ?? null)
            ? $sessionPicture['id']
            : null;
        if ($submittedPictureId === $currentPictureId) {
            return ['should_update' => false, 'path' => null];
        }

        if ($submittedPictureId === 'removed') {
            return ['should_update' => true, 'path' => null];
        }

        $temporaryUploads = Session::getData(Session::IMG_DATA_KEY);
        $temporaryUpload = is_array($temporaryUploads) ? ($temporaryUploads[$submittedPictureId] ?? null) : null;
        if (!is_array($temporaryUpload)) {
            return ['should_update' => false, 'path' => null];
        }

        return [
            'should_update' => true,
            'path' => $this->uploadService->storeTemporaryUpload($temporaryUpload),
        ];
    }

    /**
     * Determine whether any editable profile field differs from its persisted value.
     *
     * A temporary picture is considered a change only when its FilePond ID is
     * present in the current session. This check does not move the temporary file.
     *
     * @param User $user Authenticated user containing the current profile values.
     * @param array{first_name: string, last_name: string, email: string} $profileData Sanitized submitted profile data.
     * @param mixed $submittedPictureId Value submitted by the profile form.
     * @param mixed $sessionPicture Current picture metadata from the session.
     * @return bool True when a database update or picture move is required.
     */
    private function hasProfileChanges(
        User $user,
        array $profileData,
        mixed $submittedPictureId,
        mixed $sessionPicture
    ): bool {
        if (
            $profileData['first_name'] !== $user->getFirstName()
            || $profileData['last_name'] !== $user->getLastName()
            || $profileData['email'] !== $user->getEmail()
        ) {
            return true;
        }

        if (!is_string($submittedPictureId) || $submittedPictureId === '') {
            return false;
        }

        $currentPictureId = is_array($sessionPicture) && is_string($sessionPicture['id'] ?? null)
            ? $sessionPicture['id']
            : null;
        if ($submittedPictureId === $currentPictureId) {
            return false;
        }

        if ($submittedPictureId === 'removed') {
            return $user->getProfilePicture() !== null;
        }

        $temporaryUploads = Session::getData(Session::IMG_DATA_KEY);

        return is_array($temporaryUploads) && is_array($temporaryUploads[$submittedPictureId] ?? null);
    }
}
