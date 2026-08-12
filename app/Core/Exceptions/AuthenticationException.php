<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use Exception;

class AuthenticationException extends Exception
{
    public function __construct(string $message = 'Unauthenticated.', int $code = 401, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
