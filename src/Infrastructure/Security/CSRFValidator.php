<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

/**
 * Generates and validates CSRF tokens stored in the current session.
*/
class CSRFValidator
{
    private const BYTES = 32;

    /**
     * Creates and stores a cryptographically random token for the session.
     *
     * @return string The generated hexadecimal token.
    */
    public static function getCSRFToken(): string
    {
        // Only CSRF TOKEN PER-SESSION
        if(!is_string(Session::getData(Session::CSRF_TOKEN_KEY))){

            $token = static::generateCSRFToken();
            Session::setData(Session::CSRF_TOKEN_KEY, $token);
            return $token;
            
        }else{
            return Session::getData(Session::CSRF_TOKEN_KEY);
        }

    }

    /**
     * Compares a request token with the token stored in the current session.
     *
     * @param string $requestToken The token supplied by the client.
     * @return bool True when the token matches the session token.
     */
    public static function validateCSRFToken(string $requestToken): bool
    {

        $token = Session::getData(Session::CSRF_TOKEN_KEY);

        if (!is_string($token)) {
            return false;
        }

        return hash_equals($token, $requestToken);
    }

     /**
     * Generate cryptographically random token.
     *
     * @return string The generated hexadecimal token.
    */
    private static function generateCSRFToken(): string
    {
        return bin2hex(random_bytes(self::BYTES));
    }
}
