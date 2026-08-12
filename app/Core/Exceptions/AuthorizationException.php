<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use Exception;

class AuthorizationException extends Exception
{
    public function __construct(string $message = 'Forbidden. You do not have permission to access this resource.', int $code = 403, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
