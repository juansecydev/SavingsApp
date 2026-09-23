<?php

declare(strict_types=1);

namespace App\Infrastructure\Files;

use InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Throwable;
use App\Infrastructure\Files\FileValidationException;

/**
 * Validates profile-picture uploads before temporary and permanent storage.
 */
class ProfilePictureUploadService
{
    private const MAXIMUM_SIZE = 2097152;

    /** @var string[] */
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png'];

    /** @var string[] */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    /**
     * Process one uploaded profile picture.
     *
     * The validated file is moved into STORAGE_TMP_PATH with a random name.
     * The returned path is an absolute filesystem path stored temporarily in
     * the session until the profile form is submitted.
     *
     * @param UploadedFileInterface $uploadedFile Uploaded profile picture.
     * @return array{path: string, mime_type: string, size: int} Stored file data.
     * @throws FileValidationException When the file violates an upload rule.
     * @throws InvalidArgumentException When a storage directory is unavailable.
     * @throws RuntimeException When the file cannot be moved or stored.
     */
    public function process(UploadedFileInterface $uploadedFile): array
    {
        $this->assertWritableDirectory(STORAGE_TMP_PATH);
        $this->assertWritableDirectory(STORAGE_USERS_PFP_PATH);

        $clientFilename = $uploadedFile->getClientFilename() ?? 'profile_picture';
        $stagingFilename = File::sanitizeFileName($clientFilename, true);
        $stagingPath = STORAGE_TMP_PATH . $stagingFilename;

        try {
            $uploadedFile->moveTo($stagingPath);
            $file = new File($stagingPath);
            $file->validate(
                self::ALLOWED_MIME_TYPES,
                self::ALLOWED_EXTENSIONS,
                self::MAXIMUM_SIZE
            );

            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();
            return [
                'path' => $file->getRealPath(),
                'mime_type' => $mimeType,
                'size' => $fileSize,
            ];
        } catch (FileValidationException $exception) {
            $this->removeFile($stagingPath);
            throw $exception;
        } catch (Throwable $exception) {
            $this->removeFile($stagingPath);
            throw $exception;
        }
    }

    /**
     * Move a session-owned temporary upload to permanent profile-picture storage.
     *
     * The upload is validated again and must resolve inside STORAGE_TMP_PATH.
     * Its existing safe filename is preserved, while the returned path is relative
     * to STORAGE_PATH so it can be stored in the database.
     *
     * @param array{path: mixed, mime_type?: mixed, size?: mixed} $upload Temporary upload data stored in the session.
     * @return string Relative path in the form users/pfp/<filename>.
     * @throws FileValidationException When the temporary file no longer meets the image rules.
     * @throws InvalidArgumentException When the temporary upload is invalid or outside temporary storage.
     * @throws RuntimeException When the file cannot be moved to permanent storage.
     */
    public function storeTemporaryUpload(array $upload): string
    {
        $temporaryPath = $upload['path'] ?? null;
        if (!is_string($temporaryPath) || $temporaryPath === '') {
            throw new InvalidArgumentException('The temporary profile picture is invalid.');
        }

        $temporaryDirectory = realpath(STORAGE_TMP_PATH);
        $resolvedTemporaryPath = realpath($temporaryPath);
        if ($temporaryDirectory === false || $resolvedTemporaryPath === false) {
            throw new InvalidArgumentException('The temporary profile picture is unavailable.');
        }

        $temporaryDirectory .= DIRECTORY_SEPARATOR;
        if (strpos($resolvedTemporaryPath, $temporaryDirectory) !== 0) {
            throw new InvalidArgumentException('The temporary profile picture is outside temporary storage.');
        }

        $this->assertWritableDirectory(STORAGE_USERS_PFP_PATH);

        $file = new File($resolvedTemporaryPath);
        $file->validate(
            self::ALLOWED_MIME_TYPES,
            self::ALLOWED_EXTENSIONS,
            self::MAXIMUM_SIZE
        );

        $filename = $file->getName();
        $permanentPath = STORAGE_USERS_PFP_PATH . $filename;
        if (!rename($resolvedTemporaryPath, $permanentPath)) {
            throw new RuntimeException('The profile picture could not be moved to permanent storage.');
        }

        return 'users/pfp/' . $filename;
    }

    /**
     * Verify that a directory exists and can receive uploads.
     *
     * @param string $directory Directory path to verify.
     * @return void
     * @throws InvalidArgumentException When the directory is unavailable.
     */
    private function assertWritableDirectory(string $directory): void
    {
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new InvalidArgumentException('The profile picture storage directory is not writable.');
        }
    }

    /**
     * Remove a temporary file after a failed operation.
     *
     * @param string $path File path to remove.
     * @return void
     */
    private function removeFile(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }
}
