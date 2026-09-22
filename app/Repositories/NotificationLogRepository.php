<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\ExternalNotification;
use PDO;

/**
 * Repository for Outbound Notification Logs & Delivery Auditing
 */
final class NotificationLogRepository
{
    private readonly PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    public function log(
        string $channel,
        string $recipient,
        string $messageBody,
        ?string $subject = null,
        ?int $userId = null,
        string $eventType = 'general',
        string $status = 'sent',
        string $gatewayProvider = 'log',
        ?string $gatewayReference = null,
        ?string $errorMessage = null,
        ?array $metadata = null,
        ?string $sentAt = null
    ): int {
        $now = date('Y-m-d H:i:s');
        $metaJson = $metadata ? json_encode($metadata) : null;

        $stmt = $this->pdo->prepare('
            INSERT INTO `external_notifications`
            (`channel`, `recipient`, `user_id`, `event_type`, `subject`, `message_body`, `status`, `gateway_provider`, `gateway_reference`, `error_message`, `metadata`, `sent_at`, `created_at`, `updated_at`)
            VALUES
            (:channel, :recipient, :user_id, :event_type, :subject, :message_body, :status, :gateway_provider, :gateway_reference, :error_message, :metadata, :sent_at, :created_at, :updated_at)
        ');

        $stmt->execute([
            ':channel' => $channel,
            ':recipient' => $recipient,
            ':user_id' => $userId,
            ':event_type' => $eventType,
            ':subject' => $subject,
            ':message_body' => $messageBody,
            ':status' => $status,
            ':gateway_provider' => $gatewayProvider,
            ':gateway_reference' => $gatewayReference,
            ':error_message' => $errorMessage,
            ':metadata' => $metaJson,
            ':sent_at' => $sentAt ?? ($status === 'sent' ? $now : null),
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateStatus(
        int $id,
        string $status,
        ?string $gatewayReference = null,
        ?string $errorMessage = null,
        ?string $sentAt = null
    ): bool {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('
            UPDATE `external_notifications`
            SET `status` = :status,
                `gateway_reference` = COALESCE(:gateway_reference, `gateway_reference`),
                `error_message` = :error_message,
                `sent_at` = COALESCE(:sent_at, `sent_at`),
                `updated_at` = :updated_at
            WHERE `id` = :id
        ');

        return $stmt->execute([
            ':status' => $status,
            ':gateway_reference' => $gatewayReference,
            ':error_message' => $errorMessage,
            ':sent_at' => $sentAt ?? ($status === 'sent' ? $now : null),
            ':updated_at' => $now,
            ':id' => $id,
        ]);
    }

    public function findById(int $id): ?ExternalNotification
    {
        $stmt = $this->pdo->prepare('
            SELECT en.*, u.name AS user_name
            FROM `external_notifications` en
            LEFT JOIN `users` u ON u.id = en.user_id
            WHERE en.id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ExternalNotification::fromArray($row) : null;
    }

    /**
     * @return array<int, ExternalNotification>
     */
    public function getRecent(
        int $limit = 50,
        ?string $channel = null,
        ?string $status = null,
        ?string $eventType = null,
        ?string $search = null
    ): array {
        $where = [];
        $params = [];

        if (!empty($channel)) {
            $where[] = 'en.channel = :channel';
            $params[':channel'] = $channel;
        }

        if (!empty($status)) {
            $where[] = 'en.status = :status';
            $params[':status'] = $status;
        }

        if (!empty($eventType)) {
            $where[] = 'en.event_type = :event_type';
            $params[':event_type'] = $eventType;
        }

        if (!empty($search)) {
            $where[] = '(en.recipient LIKE :search OR en.subject LIKE :search OR en.message_body LIKE :search OR u.name LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $sql = '
            SELECT en.*, u.name AS user_name
            FROM `external_notifications` en
            LEFT JOIN `users` u ON u.id = en.user_id
        ';

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY en.created_at DESC, en.id DESC LIMIT ' . (int)$limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $r) => ExternalNotification::fromArray($r), $rows);
    }

    public function getStats(): array
    {
        $stmt = $this->pdo->query('
            SELECT 
                COUNT(*) AS total_count,
                SUM(CASE WHEN `channel` = \'email\' THEN 1 ELSE 0 END) AS email_count,
                SUM(CASE WHEN `channel` = \'sms\' THEN 1 ELSE 0 END) AS sms_count,
                SUM(CASE WHEN `channel` = \'whatsapp\' THEN 1 ELSE 0 END) AS whatsapp_count,
                SUM(CASE WHEN `status` IN (\'sent\', \'delivered\') THEN 1 ELSE 0 END) AS sent_count,
                SUM(CASE WHEN `status` = \'failed\' THEN 1 ELSE 0 END) AS failed_count
            FROM `external_notifications`
        ');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total' => (int)($row['total_count'] ?? 0),
            'email' => (int)($row['email_count'] ?? 0),
            'sms' => (int)($row['sms_count'] ?? 0),
            'whatsapp' => (int)($row['whatsapp_count'] ?? 0),
            'sent' => (int)($row['sent_count'] ?? 0),
            'failed' => (int)($row['failed_count'] ?? 0),
        ];
    }
}
