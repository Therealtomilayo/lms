<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Immutable HTTP Request Representation
 */
class Request
{
    private array $queryParams;
    private array $postParams;
    private array $serverParams;
    private array $files;
    private ?string $rawBody;
    private ?array $jsonPayload = null;
    private array $attributes = [];

    public function __construct(
        array $queryParams = [],
        array $postParams = [],
        array $serverParams = [],
        array $files = [],
        ?string $rawBody = null
    ) {
        $this->queryParams = $queryParams;
        $this->postParams = $postParams;
        $this->serverParams = $serverParams;
        $this->files = $files;
        $this->rawBody = $rawBody;
    }

    public static function createFromGlobals(): self
    {
        $rawBody = file_get_contents('php://input');

        return new self(
            queryParams: $_GET,
            postParams: $_POST,
            serverParams: $_SERVER,
            files: $_FILES,
            rawBody: $rawBody !== false ? $rawBody : null
        );
    }

    public function getMethod(): string
    {
        return strtoupper($this->serverParams['REQUEST_METHOD'] ?? 'GET');
    }

    public function isMethod(string $method): bool
    {
        return $this->getMethod() === strtoupper($method);
    }

    public function getPath(): string
    {
        $uri = $this->serverParams['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // Strip subfolder if hosted under a subdirectory
        $scriptName = $this->serverParams['SCRIPT_NAME'] ?? '';
        $baseDir = dirname($scriptName);
        if ($baseDir !== '/' && $baseDir !== '\\' && str_starts_with($path, $baseDir)) {
            $path = substr($path, strlen($baseDir));
            if ($path === '' || $path[0] !== '/') {
                $path = '/' . $path;
            }
        }

        return $path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->postParams[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $json = $this->json();
        if ($json !== null && array_key_exists($key, $json)) {
            return $json[$key];
        }

        if (array_key_exists($key, $this->postParams)) {
            return $this->postParams[$key];
        }

        if (array_key_exists($key, $this->queryParams)) {
            return $this->queryParams[$key];
        }

        return $default;
    }

    public function all(): array
    {
        $json = $this->json();
        if ($json !== null) {
            return array_merge($this->queryParams, $json);
        }

        return array_merge($this->queryParams, $this->postParams);
    }

    public function json(): ?array
    {
        if ($this->jsonPayload !== null) {
            return $this->jsonPayload;
        }

        if ($this->rawBody !== null && $this->isJson()) {
            $decoded = json_decode($this->rawBody, true);
            if (is_array($decoded)) {
                $this->jsonPayload = $decoded;
                return $this->jsonPayload;
            }
        }

        return null;
    }

    public function isJson(): bool
    {
        $contentType = (string)$this->header('Content-Type', '');
        $accept = (string)$this->header('Accept', '');

        return str_contains($contentType, 'application/json') || str_contains($accept, 'application/json');
    }

    public function isAjax(): bool
    {
        return strtolower((string)$this->header('X-Requested-With', '')) === 'xmlhttprequest' || $this->isJson();
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $normalized = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        if (isset($this->serverParams[$normalized])) {
            return $this->serverParams[$normalized];
        }

        if ($key === 'Content-Type' && isset($this->serverParams['CONTENT_TYPE'])) {
            return $this->serverParams['CONTENT_TYPE'];
        }

        if ($key === 'Content-Length' && isset($this->serverParams['CONTENT_LENGTH'])) {
            return $this->serverParams['CONTENT_LENGTH'];
        }

        return $default;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('Authorization');
        if ($auth !== null && preg_match('/Bearer\s+(.*)$/i', (string)$auth, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function files(): array
    {
        return $this->files;
    }

    public function clientIp(): string
    {
        return (string)($this->serverParams['REMOTE_ADDR'] ?? '127.0.0.1');
    }

    public function getIp(): string
    {
        return $this->clientIp();
    }

    public function getScheme(): string
    {
        if (isset($this->serverParams['HTTPS']) && ($this->serverParams['HTTPS'] === 'on' || $this->serverParams['HTTPS'] === '1')) {
            return 'https';
        }
        if (isset($this->serverParams['HTTP_X_FORWARDED_PROTO']) && $this->serverParams['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return 'https';
        }
        return 'http';
    }

    public function userAgent(): string
    {
        return (string)($this->serverParams['HTTP_USER_AGENT'] ?? '');
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
