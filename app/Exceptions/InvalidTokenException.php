<?php

namespace App\Exceptions;

use Exception;

/**
 * Invalid Token Exception
 * 
 * Thrown when a JWT token is invalid, expired, or malformed
 */
class InvalidTokenException extends Exception
{
    public function __construct(string $message = "Invalid or expired token", int $code = 401)
    {
        parent::__construct($message, $code);
    }
}
