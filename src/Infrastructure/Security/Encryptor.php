<?php
declare(strict_types = 1);

namespace App\Infrastructure\Security;
/**
 * This class make the encrypt actions. 
*/
class Encryptor {

    private const cost = 11; // ! Algorithm encryption cost (encryption iteration strength)

    /**
     * @param string $password is the password to encrypt
     * @return string the password encrypted by the current algorithm of PHP
    */
    public static function encryptPassword(string $password): string{
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => Encryptor::cost]);
    }

    /**
     * @param string $literal_password is the literal password (unencrypted | natural) to compare with the $encrypted_password
     * @param string $encrypted_password is the password encrypted to compare the nature and the hash
     * @return bool true if literal in it's encryption is same to the hash (encrypted_password)
    */
    public static function comparePassword(string $literal_password, string $encrypted_password) : bool {        
        return password_verify ($literal_password, $encrypted_password);
    }

}