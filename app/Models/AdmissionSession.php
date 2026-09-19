<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Entity for Academic Admission Sessions & Configuration
 */
final class AdmissionSession
{
    /**
     * @param array<string, mixed>|null $requiredDocuments
     */
    public function __construct(
        public readonly int $id,
        public readonly int $academicSessionId,
        public readonly string $title,
        public readonly float $applicationFee,
        public readonly string $currency,
        public readonly string $opensAt,
        public readonly string $closesAt,
        public readonly bool $isActive,
        public readonly ?array $requiredDocuments = null,
        public readonly ?string $instructions = null,
        public readonly int $createdBy = 0,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?string $academicSessionName = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        $requiredDocs = null;
        if (!empty($data['required_documents_json'])) {
            $requiredDocs = is_string($data['required_documents_json'])
                ? json_decode($data['required_documents_json'], true)
                : (array)$data['required_documents_json'];
        }

        return new self(
            id: (int)$data['id'],
            academicSessionId: (int)$data['academic_session_id'],
            title: (string)$data['title'],
            applicationFee: (float)($data['application_fee'] ?? 0.00),
            currency: (string)($data['currency'] ?? 'NGN'),
            opensAt: (string)$data['opens_at'],
            closesAt: (string)$data['closes_at'],
            isActive: (bool)($data['is_active'] ?? true),
            requiredDocuments: $requiredDocs,
            instructions: $data['instructions'] ?? null,
            createdBy: (int)($data['created_by'] ?? 0),
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            academicSessionName: $data['academic_session_name'] ?? null
        );
    }

    public function isOpen(): bool
    {
        if (!$this->isActive) {
            return false;
        }

        $now = time();
        $openTime = strtotime($this->opensAt);
        $closeTime = strtotime($this->closesAt);

        return $now >= $openTime && $now <= $closeTime;
    }

    public function isUpcoming(): bool
    {
        return $this->isActive && time() < strtotime($this->opensAt);
    }

    public function isPast(): bool
    {
        return time() > strtotime($this->closesAt);
    }

    public function getFormattedFee(): string
    {
        return '₦' . number_format($this->applicationFee, 2);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'academic_session_id' => $this->academicSessionId,
            'title' => $this->title,
            'application_fee' => $this->applicationFee,
            'currency' => $this->currency,
            'opens_at' => $this->opensAt,
            'closes_at' => $this->closesAt,
            'is_active' => $this->isActive,
            'required_documents' => $this->requiredDocuments,
            'instructions' => $this->instructions,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
