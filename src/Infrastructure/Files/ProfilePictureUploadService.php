<?php

declare(strict_types=1);

namespace App\Infrastructure\Files;

use InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Throwable;
use App\Infrastructure\Files\FileValidationException;

/**
 * Moves, validates, and stores profile-picture uploads temporarily.
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
     * The validated file is moved from storage/tmp to storage/users/pfp with
     * a random name. The returned paths are absolute filesystem paths.
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
            /* $storedPath = STORAGE_USERS_PFP_PATH
                . bin2hex(random_bytes(16))
                . '.'
            . $this->extensionForMimeType($mimeType);

            if (!rename($stagingPath, $storedPath)) {
                throw new RuntimeException('The profile picture could not be stored.');
            } */

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
     * Resolve a validated MIME type to its stored extension.
     *
     * @param string $mimeType Validated MIME type.
     * @return string Safe file extension.
     * @throws FileValidationException When the MIME type is unsupported.
     */
    private function extensionForMimeType(string $mimeType): string
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];

        if (!isset($extensions[$mimeType])) {
            throw new FileValidationException('The profile picture MIME type is not allowed.');
        }

        return $extensions[$mimeType];
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
