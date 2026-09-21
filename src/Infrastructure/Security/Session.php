<?php
declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\User\User;

class Session
{
    public const SESSION_KEY = 'user_id';
    private const COOKIE_SAMESITE = 'Strict';
    public const CSRF_TOKEN_KEY = 'csrf_token';
    public const IMG_DATA_KEY = 'profile_picture_uploads_tmp';

    public static function startSession(): void
    {
        /**
         * You must call session_set_cookie_params() for every request and before session_start() because its changes only last during the current script execution and it configures how the session cookie is created
         * Reset on load: PHP resets session settings back to the defaults stored in php.ini at the start of every new HTTP request
         * If you call session_set_cookie_params() after starting the session, PHP has already used the old or default parameters
        */
        if (session_status() === PHP_SESSION_NONE) {

            // Try to set secure flag when HTTPS is used
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

            $cookieParams = [
                'lifetime' => 0,
                'path' => '/',
                'domain' => '', // TODO: Set your domain if needed
                'secure' => $secure,
                'httponly' => true,
                'samesite' => self::COOKIE_SAMESITE,
            ];

            if (PHP_VERSION_ID >= 70300) {
                session_set_cookie_params($cookieParams);
            } else {
                // Fallback for older PHP versions (no samesite support)
                session_set_cookie_params(
                    $cookieParams['lifetime'],
                    $cookieParams['path'] . '; samesite=' . self::COOKIE_SAMESITE,
                    $cookieParams['domain'],
                    $cookieParams['secure'],
                    $cookieParams['httponly']
                );
            }

            session_start();
        }
    }

    public static function login(User $user): void
    {
        self::startSession();
        // Regenerate session id to prevent session fixation
        session_regenerate_id(true);

        $_SESSION[self::SESSION_KEY] = $user->getId();
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_version_id'] = $user->getVersionId();
        $_SESSION['user_version_number'] = $user->getVersionNumber();
        $_SESSION['user_first_name'] = $user->getFirstName();
        $_SESSION['user_last_name'] = $user->getLastName();
        $_SESSION['user_email'] = $user->getEmail();
        $_SESSION['user_role_id'] = $user->getRoleId();
        $_SESSION['user_profile_picture'] = [
            'id' =>  is_null($user->getProfilePicture()) ?  'default' : bin2hex(random_bytes(16)), 
            'path' => is_null($user->getProfilePicture()) ? STORAGE_USERS_PFP_PATH . 'default.png' : $user->getProfilePicture(),
        ];
        $_SESSION['user_is_admin'] = $user->isAdmin();
        $_SESSION['session_created_at'] = time();
        $_SESSION['session_last_activity'] = time();
        $_SESSION['session_fingerprint'] = self::generateFingerprint();
        // To re-generate csrf token
        $_SESSION[self::CSRF_TOKEN_KEY] = null;
        CSRFValidator::getCSRFToken();
    }
    
    public static function getData(string $key): mixed
    {
        self::startSession();
        return $_SESSION[$key] ?? null;

    }

    public static function setData(string $key, mixed $value): void{
        self::startSession();
        $_SESSION[$key] = $value;
    }

    public static function logout(): void
    {
        self::startSession();

        if (isset($_SESSION[self::SESSION_KEY])) {
            unset($_SESSION[self::SESSION_KEY]);
        }
        if (isset($_SESSION[self::CSRF_TOKEN_KEY])) {
            unset($_SESSION[self::CSRF_TOKEN_KEY]);
        }

        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );

        session_destroy();

    }

    public static function validateSession(): bool
    {
        self::startSession();

        $userId = $_SESSION[self::SESSION_KEY] ?? null;

        if ($userId === null) {
            return false;
        }

        if (!is_numeric($userId)) {
            self::logout();
            return false;
        }

        // Validate fingerprint
        $fingerprint = $_SESSION['session_fingerprint'] ?? null;
        if ($fingerprint === null || $fingerprint !== self::generateFingerprint()) {
            self::logout();
            return false;
        }

        // Validate inactivity timeout
        $last = $_SESSION['session_last_activity'] ?? $_SESSION['session_created_at'] ?? null;
        if ($last !== null && (time() - (int)$last) > env('SESSION_TIMEOUT', 1800)) { // Default to 30 minutes if not set
            self::logout();
            return false;
        }

        // Update last activity timestamp
        $_SESSION['session_last_activity'] = time();

        return true;
    }
    

    private static function generateFingerprint(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        return hash('sha256', $ua . '|' . $ip);
    }
}
