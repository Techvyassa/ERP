<?php

namespace App\Exceptions;

use Exception;

/**
 * Authentication Exception
 * 
 * Thrown when authentication fails due to invalid credentials,
 * inactive user, or suspended organization
 */
class AuthenticationException extends Exception
{
    public function __construct(string $message = "Authentication failed", int $code = 401)
    {
        parent::__construct($message, $code);
    }
}
