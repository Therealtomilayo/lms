<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP Response Representation
 */
class Response
{
    private int $statusCode;
    private array $headers = [];
    private string $content;
    private ?string $streamFilePath = null;
    private int $streamStart = 0;
    private int $streamLength = 0;

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public static function html(string $content, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'text/html; charset=UTF-8';

        return new self($content, $statusCode, $headers);
    }

    public static function json(mixed $data, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json; charset=UTF-8';
        $content = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return new self($content !== false ? $content : '{}', $statusCode, $headers);
    }

    public static function redirect(string $url, int $statusCode = 302, array $headers = []): self
    {
        $headers['Location'] = $url;

        return new self('', $statusCode, $headers);
    }

    public static function notFound(string $message = 'Not Found'): self
    {
        return self::html("<h1>404 Not Found</h1><p>{$message}</p>", 404);
    }

    public static function download(
        string $filePath,
        string $fileName,
        string $mimeType = 'application/octet-stream'
    ): self {
        if (!file_exists($filePath)) {
            return self::json(['error' => 'File not found'], 404);
        }

        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . addslashes($fileName) . '"',
            'Content-Length' => (string)filesize($filePath),
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $content = file_get_contents($filePath) ?: '';

        return new self($content, 200, $headers);
    }

    /**
     * Stream an inline file with full HTTP Range (Accept-Ranges: bytes) support.
     * Supports HTTP 206 Partial Content for smooth streaming in PDF.js / mobile browsers.
     */
    public static function streamFile(
        string $filePath,
        string $fileName,
        string $mimeType = 'application/pdf',
        ?string $rangeHeader = null
    ): self {
        if (!file_exists($filePath) || !is_file($filePath)) {
            return self::json(['error' => 'File not found'], 404);
        }

        $fileSize = (int)filesize($filePath);
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . addslashes(basename($fileName)) . '"',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, max-age=3600, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ];

        // Parse byte range request if present
        if ($rangeHeader !== null && preg_match('/bytes=\s*(\d*)\s*-\s*(\d*)/i', $rangeHeader, $matches)) {
            $rawStart = $matches[1];
            $rawEnd = $matches[2];

            if ($rawStart === '' && $rawEnd === '') {
                return new self('', 416, [
                    'Content-Range' => "bytes */{$fileSize}",
                    'Accept-Ranges' => 'bytes',
                ]);
            }

            if ($rawStart === '') {
                $suffixLength = (int)$rawEnd;
                if ($suffixLength <= 0) {
                    return new self('', 416, [
                        'Content-Range' => "bytes */{$fileSize}",
                        'Accept-Ranges' => 'bytes',
                    ]);
                }
                $start = max(0, $fileSize - $suffixLength);
                $end = $fileSize - 1;
            } elseif ($rawEnd === '') {
                $start = (int)$rawStart;
                $end = $fileSize - 1;
            } else {
                $start = (int)$rawStart;
                $end = (int)$rawEnd;
            }

            if ($start > $end || $start >= $fileSize || $end >= $fileSize || $start < 0) {
                return new self('', 416, [
                    'Content-Range' => "bytes */{$fileSize}",
                    'Accept-Ranges' => 'bytes',
                ]);
            }

            $length = $end - $start + 1;
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$fileSize}";
            $headers['Content-Length'] = (string)$length;

            $response = new self('', 206, $headers);
            $response->streamFilePath = $filePath;
            $response->streamStart = $start;
            $response->streamLength = $length;

            return $response;
        }

        // Full content response (200 OK)
        $headers['Content-Length'] = (string)$fileSize;
        $response = new self('', 200, $headers);
        $response->streamFilePath = $filePath;
        $response->streamStart = 0;
        $response->streamLength = $fileSize;

        return $response;
    }

    public function getStreamFilePath(): ?string
    {
        return $this->streamFilePath;
    }

    public function getStreamStart(): int
    {
        return $this->streamStart;
    }

    public function getStreamLength(): int
    {
        return $this->streamLength;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function isRedirect(): bool
    {
        return in_array($this->statusCode, [301, 302, 303, 307, 308], true);
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getBody(): string
    {
        return $this->content;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                header(sprintf('%s: %s', $name, $value));
            }
        }

        if ($this->streamFilePath !== null && file_exists($this->streamFilePath)) {
            $fp = @fopen($this->streamFilePath, 'rb');
            if ($fp !== false) {
                if ($this->streamStart > 0) {
                    fseek($fp, $this->streamStart);
                }
                $bytesRemaining = $this->streamLength;
                $chunkSize = 64 * 1024; // 64 KB buffer per chunk
                while (!feof($fp) && $bytesRemaining > 0 && connection_status() === CONNECTION_NORMAL) {
                    $readSize = min($chunkSize, $bytesRemaining);
                    $chunk = fread($fp, $readSize);
                    if ($chunk === false || $chunk === '') {
                        break;
                    }
                    echo $chunk;
                    flush();
                    $bytesRemaining -= strlen($chunk);
                }
                fclose($fp);
            }
            return;
        }

        echo $this->content;
    }
}
