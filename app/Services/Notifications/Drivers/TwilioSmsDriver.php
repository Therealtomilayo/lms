<?php

declare(strict_types=1);

namespace App\Services\Notifications\Drivers;

use App\Core\Config;
use App\DTO\NotificationResult;
use App\Services\Notifications\NotificationChannelInterface;
use Throwable;

/**
 * Driver for Twilio SMS API
 */
final class TwilioSmsDriver implements NotificationChannelInterface
{
    private string $accountSid;
    private string $authToken;
    private string $fromNumber;
    private LogDriver $fallbackLogDriver;

    public function __construct(
        ?string $accountSid = null,
        ?string $authToken = null,
        ?string $fromNumber = null
    ) {
        $this->accountSid = $accountSid ?? (string)Config::get('twilio.sid', '');
        $this->authToken = $authToken ?? (string)Config::get('twilio.token', '');
        $this->fromNumber = $fromNumber ?? (string)Config::get('twilio.from_number', '');
        $this->fallbackLogDriver = new LogDriver();
    }

    public function send(string $recipient, string $content, array $options = []): NotificationResult
    {
        $gateway = (string)Config::get('sms.gateway', 'log');

        if (in_array(strtolower($gateway), ['log', 'mock'], true) || empty($this->accountSid) || empty($this->authToken)) {
            return $this->fallbackLogDriver->send($recipient, $content, array_merge($options, [
                'channel' => 'sms',
            ]));
        }

        $formattedTo = str_starts_with($recipient, '+') ? $recipient : ('+' . ltrim($recipient, '0'));

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";
        $postFields = http_build_query([
            'To' => $formattedTo,
            'From' => $this->fromNumber,
            'Body' => $content,
        ]);

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_USERPWD, "{$this->accountSid}:{$this->authToken}");
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                throw new \RuntimeException("Twilio network error: {$err}");
            }

            $body = json_decode((string)$response, true);

            if ($httpCode >= 200 && $httpCode < 300 && isset($body['sid'])) {
                return NotificationResult::success('twilio', $body['sid'], $body ?? []);
            }

            $errorMsg = $body['message'] ?? "HTTP error {$httpCode}";
            return NotificationResult::failure('twilio', $errorMsg, $body ?? []);
        } catch (Throwable $e) {
            $this->fallbackLogDriver->send($recipient, "[DELIVERY ATTEMPT FAILED: {$e->getMessage()}]\n\n" . $content, [
                'channel' => 'sms',
            ]);

            return NotificationResult::failure('twilio', "Twilio dispatch failed: " . $e->getMessage());
        }
    }

    public function getName(): string
    {
        return 'twilio';
    }
}
