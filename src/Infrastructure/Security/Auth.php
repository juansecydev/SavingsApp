<?php
declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\User\User;
use Adevlinux\SavingsApp\core\AppInjector as App;

class Auth
{
    public const SESSION_KEY = 'auth_user_id';
    private const COOKIE_SAMESITE = 'Lax';

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {

            // Try to set secure flag when HTTPS is used
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

            $cookieParams = [
                'lifetime' => 0,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'] ?? '',
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

        $_SESSION[self::SESSION_KEY] = $user->user_id;
        $_SESSION['auth_created_at'] = time();
        $_SESSION['auth_last_activity'] = time();
        $_SESSION['auth_fingerprint'] = self::generateFingerprint();
    }

    public static function logout(): void
    {
        self::startSession();

        if (isset($_SESSION[self::SESSION_KEY])) {
            unset($_SESSION[self::SESSION_KEY]);
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

    public static function user(): ?User
    {
        self::startSession();

        $userId = $_SESSION[self::SESSION_KEY] ?? null;

        if ($userId === null) {
            return null;
        }

        if (!is_numeric($userId)) {
            self::logout();
            return null;
        }

        // Validate fingerprint
        $fingerprint = $_SESSION['auth_fingerprint'] ?? null;
        if ($fingerprint === null || $fingerprint !== self::generateFingerprint()) {
            self::logout();
            return null;
        }

        // Validate inactivity timeout
        $last = $_SESSION['auth_last_activity'] ?? $_SESSION['auth_created_at'] ?? null;
        if ($last !== null && (time() - (int)$last) > App::get('session_timeout', 1800)) { // Default to 30 minutes if not set
            self::logout();
            return null;
        }

        // Update last activity timestamp
        $_SESSION['auth_last_activity'] = time();

        return User::find((int) $userId);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    private static function generateFingerprint(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        return hash('sha256', $ua . '|' . $ip);
    }
}
