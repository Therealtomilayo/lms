<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use Exception;

class ValidationException extends Exception
{
    private array $errors;

    public function __construct(array $errors, string $message = 'The given data was invalid.', int $code = 422, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
