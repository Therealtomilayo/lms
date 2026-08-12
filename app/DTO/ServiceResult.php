<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Standardized Transport-Independent Service Result Envelope
 */
final class ServiceResult
{
    public function __construct(
        public readonly bool $success,
        public readonly mixed $data = null,
        public readonly array $errors = [],
        public readonly ?string $errorCode = null
    ) {
    }

    public static function success(mixed $data = null): self
    {
        return new self(
            success: true,
            data: $data,
            errors: [],
            errorCode: null
        );
    }

    public static function failure(array|string $errors, ?string $errorCode = null): self
    {
        $normalizedErrors = is_array($errors) ? $errors : ['general' => [$errors]];

        return new self(
            success: false,
            data: null,
            errors: $normalizedErrors,
            errorCode: $errorCode
        );
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return !$this->success;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getFirstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }

        $firstKey = array_key_first($this->errors);
        if ($firstKey === null) {
            return null;
        }

        $first = $this->errors[$firstKey];
        if (is_array($first)) {
            $firstSubKey = array_key_first($first);
            return $firstSubKey !== null && is_string($first[$firstSubKey]) ? $first[$firstSubKey] : null;
        }

        return is_string($first) ? $first : null;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'errors' => $this->errors,
            'error_code' => $this->errorCode,
        ];
    }
}
