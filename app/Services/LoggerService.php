<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use Throwable;

/**
 * Structured JSON Application Logger with PII Redaction & Correlation Tracking
 */
class LoggerService
{
    private static ?string $correlationId = null;
    private string $logPath;

    private static array $redactedKeys = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'csrf_token',
        '_csrf_token',
        'secret',
        'api_key',
        'authorization',
        'cookie',
        'session',
        'database_password',
        'credit_card',
        'card_number',
        'cvv',
    ];

    public function __construct(?string $logPath = null)
    {
        if ($logPath !== null) {
            $this->logPath = $logPath;
        } else {
            $baseStorage = Config::get('storage.path') ?: (dirname(__DIR__, 2) . '/storage');
            $this->logPath = $baseStorage . '/logs/lms-' . gmdate('Y-m-d') . '.log';
        }
    }

    public static function getCorrelationId(): string
    {
        if (self::$correlationId === null) {
            self::$correlationId = bin2hex(random_bytes(16));
        }

        return self::$correlationId;
    }

    public static function setCorrelationId(string $id): void
    {
        self::$correlationId = $id;
    }

    public static function resetCorrelationId(): void
    {
        self::$correlationId = null;
    }

    public function log(string $level, string $message, array $context = []): array
    {
        $record = [
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'level' => strtolower($level),
            'message' => $message,
            'correlation_id' => self::getCorrelationId(),
            'context' => $this->redact($context),
        ];

        $this->write($record);

        return $record;
    }

    public function debug(string $message, array $context = []): array
    {
        return $this->log('debug', $message, $context);
    }

    public function info(string $message, array $context = []): array
    {
        return $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): array
    {
        return $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): array
    {
        return $this->log('error', $message, $context);
    }

    public function critical(string $message, array $context = []): array
    {
        return $this->log('critical', $message, $context);
    }

    public function logException(Throwable $e, array $context = []): array
    {
        $context['exception'] = [
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
        ];

        return $this->log('error', 'Unhandled Exception: ' . $e->getMessage(), $context);
    }

    public function redact(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        $clean = [];
        foreach ($data as $key => $value) {
            $isRedactedKey = is_string($key) && in_array(strtolower($key), self::$redactedKeys, true);

            if ($isRedactedKey) {
                $clean[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $clean[$key] = $this->redact($value);
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    private function write(array $record): void
    {
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $json = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json !== false) {
            @file_put_contents($this->logPath, $json . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }
}
