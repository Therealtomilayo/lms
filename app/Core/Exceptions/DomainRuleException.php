<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use Exception;

class DomainRuleException extends Exception
{
    public function __construct(string $message = 'A domain business rule was violated.', int $code = 422, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
