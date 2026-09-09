<?php
namespace App\Infrastructure\Security;

/**
 * This class has the responsability in the generation and provide the tokens in themes of security
*/
class TokenGenerator{

    private static $BYTES = 32;

    /**
     * This function return a random crst token
     * @return string return an random string that is the CSRF token
    */
    public static function generateCSRFToken(): string{
        return bin2hex(random_bytes(static::$BYTES));
    }

}