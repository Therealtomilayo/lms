<?php

declare(strict_types=1);

namespace App\Services\Notifications\Drivers;

use App\Core\Config;
use App\DTO\NotificationResult;
use App\Services\Notifications\NotificationChannelInterface;
use Throwable;

/**
 * Native Socket-based SMTP Email Driver supporting TLS/SSL and Auth Login
 */
final class SmtpEmailDriver implements NotificationChannelInterface
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $encryption;
    private string $fromAddress;
    private string $fromName;
    private LogDriver $fallbackLogDriver;

    public function __construct(
        ?string $host = null,
        ?int $port = null,
        ?string $username = null,
        ?string $password = null,
        ?string $encryption = null,
        ?string $fromAddress = null,
        ?string $fromName = null
    ) {
        $this->host = $host ?? (string)Config::get('mail.host', '127.0.0.1');
        $this->port = $port ?? (int)Config::get('mail.port', 587);
        $this->username = $username ?? (string)Config::get('mail.username', '');
        $this->password = $password ?? (string)Config::get('mail.password', '');
        $this->encryption = $encryption ?? (string)Config::get('mail.encryption', 'tls');
        $this->fromAddress = $fromAddress ?? (string)Config::get('mail.from_address', 'notifications@claret.edu.ng');
        $this->fromName = $fromName ?? (string)Config::get('mail.from_name', 'Claret International School');
        $this->fallbackLogDriver = new LogDriver();
    }

    public function send(string $recipient, string $content, array $options = []): NotificationResult
    {
        $mailer = (string)Config::get('mail.mailer', 'log');
        $subject = (string)($options['subject'] ?? 'Notification from Claret International School');
        $htmlBody = $options['html'] ?? $content;

        // If configured as log or mock, or credentials are unconfigured in local dev
        if (in_array(strtolower($mailer), ['log', 'mock'], true) || empty($this->host) || $this->host === '127.0.0.1') {
            return $this->fallbackLogDriver->send($recipient, $content, array_merge($options, [
                'channel' => 'email',
                'subject' => $subject,
            ]));
        }

        try {
            $messageId = $this->dispatchSmtp($recipient, $subject, $htmlBody, $content);
            return NotificationResult::success('smtp', $messageId, [
                'host' => $this->host,
                'port' => $this->port,
            ]);
        } catch (Throwable $e) {
            // Log fallback so messages are never silently discarded
            $this->fallbackLogDriver->send($recipient, "[DELIVERY ATTEMPT FAILED: {$e->getMessage()}]\n\n" . $content, [
                'channel' => 'email',
                'subject' => $subject,
            ]);

            return NotificationResult::failure('smtp', "SMTP dispatch error: " . $e->getMessage());
        }
    }

    private function dispatchSmtp(string $to, string $subject, string $htmlBody, string $plainText): string
    {
        $protocol = strtolower($this->encryption) === 'ssl' ? 'ssl://' : '';
        $socket = @fsockopen($protocol . $this->host, $this->port, $errno, $errstr, 15);

        if (!$socket) {
            throw new \RuntimeException("Could not connect to SMTP host {$this->host}:{$this->port} ({$errstr})");
        }

        $this->readResponse($socket, 220);

        // HELO/EHLO
        $this->sendCommand($socket, "EHLO " . gethostname());
        $this->readResponse($socket, 250);

        // STARTTLS if port 587 or tls configured
        if (strtolower($this->encryption) === 'tls') {
            $this->sendCommand($socket, "STARTTLS");
            $this->readResponse($socket, 220);

            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new \RuntimeException("Failed to negotiate TLS encryption with SMTP server.");
            }

            // Re-send EHLO after TLS handshake
            $this->sendCommand($socket, "EHLO " . gethostname());
            $this->readResponse($socket, 250);
        }

        // Authentication
        if (!empty($this->username)) {
            $this->sendCommand($socket, "AUTH LOGIN");
            $this->readResponse($socket, 334);
            $this->sendCommand($socket, base64_encode($this->username));
            $this->readResponse($socket, 334);
            $this->sendCommand($socket, base64_encode($this->password));
            $this->readResponse($socket, 235);
        }

        // Envelope
        $this->sendCommand($socket, "MAIL FROM:<{$this->fromAddress}>");
        $this->readResponse($socket, 250);

        $this->sendCommand($socket, "RCPT TO:<{$to}>");
        $this->readResponse($socket, 250);

        // Data
        $this->sendCommand($socket, "DATA");
        $this->readResponse($socket, 354);

        $messageId = sprintf('<%s.%s@%s>', time(), bin2hex(random_bytes(6)), parse_url((string)Config::get('app.url', 'claret.edu.ng'), PHP_URL_HOST) ?: 'claret.edu.ng');
        $boundary = '=_claret_' . md5(uniqid((string)time(), true));

        $headers = [];
        $headers[] = "Date: " . date('r');
        $headers[] = "From: =?UTF-8?B?" . base64_encode($this->fromName) . "?= <{$this->fromAddress}>";
        $headers[] = "To: <{$to}>";
        $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
        $headers[] = "Message-ID: {$messageId}";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
        $headers[] = "";

        $body = [];
        // Plain text part
        $body[] = "--{$boundary}";
        $body[] = "Content-Type: text/plain; charset=UTF-8";
        $body[] = "Content-Transfer-Encoding: base64";
        $body[] = "";
        $body[] = chunk_split(base64_encode($plainText));

        // HTML part
        $body[] = "--{$boundary}";
        $body[] = "Content-Type: text/html; charset=UTF-8";
        $body[] = "Content-Transfer-Encoding: base64";
        $body[] = "";
        $body[] = chunk_split(base64_encode($htmlBody));
        $body[] = "--{$boundary}--";

        $fullPayload = implode("\r\n", $headers) . "\r\n" . implode("\r\n", $body) . "\r\n.";

        $this->sendCommand($socket, $fullPayload);
        $this->readResponse($socket, 250);

        $this->sendCommand($socket, "QUIT");
        fclose($socket);

        return $messageId;
    }

    private function sendCommand($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
    }

    private function readResponse($socket, int $expectedCode): string
    {
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int)substr($response, 0, 3);
        if ($code !== $expectedCode) {
            throw new \RuntimeException("Unexpected SMTP response [{$code}]: {$response} (expected {$expectedCode})");
        }

        return $response;
    }

    public function getName(): string
    {
        return 'smtp';
    }
}
