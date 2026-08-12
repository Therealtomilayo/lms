<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use Exception;

class ResourceNotFoundException extends Exception
{
    public function __construct(string $message = 'Resource not found.', int $code = 404, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
