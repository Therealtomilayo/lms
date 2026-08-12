<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\ValidationException;

/**
 * Server-Side Input Validator
 */
class Validator
{
    private array $data;
    private array $rules;
    private array $customMessages;
    private array $errors = [];
    private array $validatedData = [];

    public function __construct(array $data, array $rules, array $customMessages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $customMessages;
        $this->runValidation();
    }

    public static function make(array $data, array $rules, array $customMessages = []): self
    {
        return new self($data, $rules, $customMessages);
    }

    public static function validateData(array $data, array $rules, array $customMessages = []): array
    {
        $validator = new self($data, $rules, $customMessages);
        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        return $validator->validated();
    }

    private function runValidation(): void
    {
        foreach ($this->rules as $field => $ruleDefinition) {
            $rulesList = is_string($ruleDefinition) ? explode('|', $ruleDefinition) : $ruleDefinition;
            $value = $this->getValue($field);

            $isRequired = in_array('required', $rulesList, true);

            // If value is missing and not required, skip other rules
            if (!$isRequired && ($value === null || $value === '')) {
                continue;
            }

            foreach ($rulesList as $rule) {
                $ruleName = $rule;
                $parameters = [];

                if (str_contains($rule, ':')) {
                    [$ruleName, $paramStr] = explode(':', $rule, 2);
                    $parameters = explode(',', $paramStr);
                }

                $this->validateRule($field, $value, $ruleName, $parameters);
            }

            if (!isset($this->errors[$field])) {
                $this->validatedData[$field] = $value;
            }
        }
    }

    private function validateRule(string $field, mixed $value, string $rule, array $parameters): void
    {
        switch ($rule) {
            case 'required':
                if ($value === null || (is_string($value) && trim($value) === '') || (is_array($value) && empty($value))) {
                    $this->addError($field, 'required', 'The ' . $this->humanize($field) . ' field is required.');
                }
                break;

            case 'email':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'email', 'The ' . $this->humanize($field) . ' must be a valid email address.');
                }
                break;

            case 'min':
                $min = (int)($parameters[0] ?? 0);
                if (is_string($value) && mb_strlen($value) < $min) {
                    $this->addError($field, 'min', 'The ' . $this->humanize($field) . ' must be at least ' . $min . ' characters.');
                } elseif (is_numeric($value) && (float)$value < $min) {
                    $this->addError($field, 'min', 'The ' . $this->humanize($field) . ' must be at least ' . $min . '.');
                } elseif (is_array($value) && count($value) < $min) {
                    $this->addError($field, 'min', 'The ' . $this->humanize($field) . ' must have at least ' . $min . ' items.');
                }
                break;

            case 'max':
                $max = (int)($parameters[0] ?? 0);
                if (is_string($value) && mb_strlen($value) > $max) {
                    $this->addError($field, 'max', 'The ' . $this->humanize($field) . ' must not exceed ' . $max . ' characters.');
                } elseif (is_numeric($value) && (float)$value > $max) {
                    $this->addError($field, 'max', 'The ' . $this->humanize($field) . ' must not exceed ' . $max . '.');
                } elseif (is_array($value) && count($value) > $max) {
                    $this->addError($field, 'max', 'The ' . $this->humanize($field) . ' must not have more than ' . $max . ' items.');
                }
                break;

            case 'confirmed':
                $confirmationField = $field . '_confirmation';
                $confirmationValue = $this->getValue($confirmationField);
                if ($value !== $confirmationValue) {
                    $this->addError($field, 'confirmed', 'The ' . $this->humanize($field) . ' confirmation does not match.');
                }
                break;

            case 'same':
                $otherField = $parameters[0] ?? '';
                $otherValue = $this->getValue($otherField);
                if ($value !== $otherValue) {
                    $this->addError($field, 'same', 'The ' . $this->humanize($field) . ' and ' . $this->humanize($otherField) . ' must match.');
                }
                break;

            case 'different':
                $otherField = $parameters[0] ?? '';
                $otherValue = $this->getValue($otherField);
                if ($value === $otherValue) {
                    $this->addError($field, 'different', 'The ' . $this->humanize($field) . ' and ' . $this->humanize($otherField) . ' must be different.');
                }
                break;

            case 'in':
                if (!in_array((string)$value, $parameters, true)) {
                    $this->addError($field, 'in', 'The selected ' . $this->humanize($field) . ' is invalid.');
                }
                break;

            case 'integer':
                if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, 'integer', 'The ' . $this->humanize($field) . ' must be an integer.');
                }
                break;

            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    $this->addError($field, 'numeric', 'The ' . $this->humanize($field) . ' must be a number.');
                }
                break;
        }
    }

    private function getValue(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    private function addError(string $field, string $rule, string $defaultMessage): void
    {
        $customKey = $field . '.' . $rule;
        $message = $this->customMessages[$customKey]
            ?? $this->customMessages[$field]
            ?? $defaultMessage;

        $this->errors[$field][] = $message;
    }

    private function humanize(string $field): string
    {
        return str_replace(['_', '-'], ' ', $field);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(?string $field = null): ?string
    {
        if ($field !== null) {
            return $this->errors[$field][0] ?? null;
        }

        if (empty($this->errors)) {
            return null;
        }

        $firstKey = array_key_first($this->errors);
        return $firstKey !== null && !empty($this->errors[$firstKey]) ? $this->errors[$firstKey][0] : null;
    }

    public function validated(): array
    {
        return $this->validatedData;
    }
}
