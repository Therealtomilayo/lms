<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Grading Scale Entity
 */
final class GradingScale
{
    /**
     * @param array<int, GradeBoundary> $boundaries
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly bool $isDefault = false,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly array $boundaries = []
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, GradeBoundary> $boundaries
     */
    public static function fromArray(array $data, array $boundaries = []): self
    {
        if (empty($boundaries) && !empty($data['boundaries']) && is_array($data['boundaries'])) {
            $boundaries = array_map(function ($b) {
                return $b instanceof GradeBoundary ? $b : GradeBoundary::fromArray($b);
            }, $data['boundaries']);
        }

        return new self(
            id: (int)($data['id'] ?? 0),
            name: (string)($data['name'] ?? ''),
            description: isset($data['description']) && $data['description'] !== '' ? (string)$data['description'] : null,
            isDefault: (bool)($data['is_default'] ?? false),
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            boundaries: $boundaries
        );
    }

    /**
     * Match a numeric score against the boundaries in descending order.
     */
    public function resolveGrade(float $score): ?GradeBoundary
    {
        // Round to 2 decimal places to ensure consistent boundary matching
        $rounded = round($score, 2);

        foreach ($this->boundaries as $boundary) {
            if ($rounded >= $boundary->minScore && $rounded <= $boundary->maxScore) {
                return $boundary;
            }
        }

        // If score is slightly higher than 100 due to float, match top boundary if exists
        if (!empty($this->boundaries) && $rounded > 100.0) {
            return $this->boundaries[0];
        }

        return null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_default' => $this->isDefault,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'boundaries' => array_map(fn (GradeBoundary $b) => $b->toArray(), $this->boundaries),
        ];
    }
}
