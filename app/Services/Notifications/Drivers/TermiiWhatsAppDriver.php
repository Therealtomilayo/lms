<?php

declare(strict_types=1);

namespace App\Services\Notifications\Drivers;

use App\Core\Config;
use App\DTO\NotificationResult;
use App\Services\Notifications\NotificationChannelInterface;
use Throwable;

/**
 * Driver for Termii WhatsApp Business Messaging
 */
final class TermiiWhatsAppDriver implements NotificationChannelInterface
{
    private string $apiKey;
    private string $baseUrl;
    private LogDriver $fallbackLogDriver;

    public function __construct(
        ?string $apiKey = null,
        ?string $baseUrl = null
    ) {
        $this->apiKey = $apiKey ?? (string)Config::get('termii.api_key', '');
        $this->baseUrl = rtrim($baseUrl ?? (string)Config::get('termii.base_url', 'https://api.ng.termii.com'), '/');
        $this->fallbackLogDriver = new LogDriver();
    }

    public function send(string $recipient, string $content, array $options = []): NotificationResult
    {
        $gateway = (string)Config::get('whatsapp.gateway', 'log');

        if (in_array(strtolower($gateway), ['log', 'mock'], true) || empty($this->apiKey)) {
            return $this->fallbackLogDriver->send($recipient, $content, array_merge($options, [
                'channel' => 'whatsapp',
            ]));
        }

        $formattedPhone = $this->formatPhoneNumber($recipient);

        $payload = [
            'to' => $formattedPhone,
            'from' => (string)Config::get('termii.sender_id', 'ClaretSch'),
            'sms' => $content,
            'type' => 'plain',
            'channel' => 'whatsapp',
            'api_key' => $this->apiKey,
        ];

        try {
            $url = $this->baseUrl . '/api/sms/send';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                throw new \RuntimeException("Termii WhatsApp network error: {$err}");
            }

            $body = json_decode((string)$response, true);

            if ($httpCode >= 200 && $httpCode < 300 && (isset($body['message_id']) || isset($body['code']) && $body['code'] === 'ok')) {
                return NotificationResult::success('termii_whatsapp', $body['message_id'] ?? null, $body ?? []);
            }

            $errorMsg = $body['message'] ?? $body['error'] ?? "HTTP error {$httpCode}";
            return NotificationResult::failure('termii_whatsapp', $errorMsg, $body ?? []);
        } catch (Throwable $e) {
            $this->fallbackLogDriver->send($recipient, "[WHATSAPP ATTEMPT FAILED: {$e->getMessage()}]\n\n" . $content, [
                'channel' => 'whatsapp',
            ]);

            return NotificationResult::failure('termii_whatsapp', "Termii WhatsApp dispatch failed: " . $e->getMessage());
        }
    }

    private function formatPhoneNumber(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone) ?? '';

        if (str_starts_with($clean, '0') && strlen($clean) === 11) {
            return '234' . substr($clean, 1);
        }

        if (strlen($clean) === 10) {
            return '234' . $clean;
        }

        return $clean;
    }

    public function getName(): string
    {
        return 'termii_whatsapp';
    }
}
