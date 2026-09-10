<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Promotion;
use PDO;

/**
 * Data Access Layer for Student Promotions, Evaluations, and Cohort Advancement
 */
class PromotionRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    /**
     * Insert or update a student promotion decision.
     */
    public function recordPromotion(array $data): Promotion
    {
        $now = date('Y-m-d H:i:s');
        $termAveragesJson = isset($data['term_averages']) && is_array($data['term_averages'])
            ? json_encode($data['term_averages'])
            : ($data['term_averages'] ?? null);

        $studentId = (int)$data['student_id'];
        $fromSessionId = (int)$data['from_session_id'];

        $existing = $this->findByStudentAndSession($studentId, $fromSessionId);

        if ($existing) {
            $sql = "
                UPDATE `student_promotions` SET
                    `from_class_id` = :from_class_id,
                    `to_session_id` = :to_session_id,
                    `to_class_id` = :to_class_id,
                    `decision` = :decision,
                    `annual_average` = :annual_average,
                    `term_averages` = :term_averages,
                    `evaluation_status` = :evaluation_status,
                    `approval_request_id` = :approval_request_id,
                    `override_reason` = :override_reason,
                    `promoted_by` = :promoted_by,
                    `promoted_at` = :promoted_at,
                    `updated_at` = :updated_at
                WHERE `id` = :id
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id'                  => $existing->id,
                ':from_class_id'       => $data['from_class_id'],
                ':to_session_id'       => $data['to_session_id'] ?? null,
                ':to_class_id'         => $data['to_class_id'] ?? null,
                ':decision'            => $data['decision'] ?? Promotion::DECISION_PROMOTED,
                ':annual_average'      => $data['annual_average'] ?? 0.0,
                ':term_averages'       => $termAveragesJson,
                ':evaluation_status'   => $data['evaluation_status'] ?? Promotion::STATUS_PRELIMINARY,
                ':approval_request_id' => $data['approval_request_id'] ?? null,
                ':override_reason'     => $data['override_reason'] ?? null,
                ':promoted_by'         => $data['promoted_by'] ?? 1,
                ':promoted_at'         => $data['promoted_at'] ?? $now,
                ':updated_at'          => $now,
            ]);

            return $this->findById($existing->id);
        }

        $sql = "
            INSERT INTO `student_promotions` (
                `student_id`, `from_session_id`, `from_class_id`, `to_session_id`, `to_class_id`,
                `decision`, `annual_average`, `term_averages`, `evaluation_status`,
                `approval_request_id`, `override_reason`, `promoted_by`, `promoted_at`,
                `created_at`, `updated_at`
            ) VALUES (
                :student_id, :from_session_id, :from_class_id, :to_session_id, :to_class_id,
                :decision, :annual_average, :term_averages, :evaluation_status,
                :approval_request_id, :override_reason, :promoted_by, :promoted_at,
                :created_at, :updated_at
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':student_id'          => $studentId,
            ':from_session_id'     => $fromSessionId,
            ':from_class_id'       => $data['from_class_id'],
            ':to_session_id'       => $data['to_session_id'] ?? null,
            ':to_class_id'         => $data['to_class_id'] ?? null,
            ':decision'            => $data['decision'] ?? Promotion::DECISION_PROMOTED,
            ':annual_average'      => $data['annual_average'] ?? 0.0,
            ':term_averages'       => $termAveragesJson,
            ':evaluation_status'   => $data['evaluation_status'] ?? Promotion::STATUS_PRELIMINARY,
            ':approval_request_id' => $data['approval_request_id'] ?? null,
            ':override_reason'     => $data['override_reason'] ?? null,
            ':promoted_by'         => $data['promoted_by'] ?? 1,
            ':promoted_at'         => $data['promoted_at'] ?? $now,
            ':created_at'          => $now,
            ':updated_at'          => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();
        return $this->findById($id ?: 1);
    }

    public function findById(int $id): ?Promotion
    {
        $sql = "
            SELECT p.*,
                   u.name as student_name,
                   s.admission_number as student_admission_number,
                   c1.name as from_class_name,
                   c2.name as to_class_name,
                   ses1.name as from_session_name,
                   ses2.name as to_session_name
            FROM `student_promotions` p
            JOIN `students` s ON s.id = p.student_id
            JOIN `users` u ON u.id = s.user_id
            JOIN `classes` c1 ON c1.id = p.from_class_id
            LEFT JOIN `classes` c2 ON c2.id = p.to_class_id
            JOIN `sessions` ses1 ON ses1.id = p.from_session_id
            LEFT JOIN `sessions` ses2 ON ses2.id = p.to_session_id
            WHERE p.id = :id LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Promotion::fromArray($row) : null;
    }

    public function findByStudentAndSession(int $studentId, int $sessionId): ?Promotion
    {
        $sql = "
            SELECT p.*,
                   u.name as student_name,
                   s.admission_number as student_admission_number,
                   c1.name as from_class_name,
                   c2.name as to_class_name,
                   ses1.name as from_session_name,
                   ses2.name as to_session_name
            FROM `student_promotions` p
            JOIN `students` s ON s.id = p.student_id
            JOIN `users` u ON u.id = s.user_id
            JOIN `classes` c1 ON c1.id = p.from_class_id
            LEFT JOIN `classes` c2 ON c2.id = p.to_class_id
            JOIN `sessions` ses1 ON ses1.id = p.from_session_id
            LEFT JOIN `sessions` ses2 ON ses2.id = p.to_session_id
            WHERE p.student_id = :student_id AND p.from_session_id = :session_id LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':student_id' => $studentId, ':session_id' => $sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Promotion::fromArray($row) : null;
    }

    /**
     * Get promotions recorded for a class in an academic session.
     *
     * @return array<int, Promotion> Keyed by student_id
     */
    public function getPromotionsByClass(int $classId, int $sessionId): array
    {
        $sql = "
            SELECT p.*,
                   u.name as student_name,
                   s.admission_number as student_admission_number,
                   c1.name as from_class_name,
                   c2.name as to_class_name,
                   ses1.name as from_session_name,
                   ses2.name as to_session_name
            FROM `student_promotions` p
            JOIN `students` s ON s.id = p.student_id
            JOIN `users` u ON u.id = s.user_id
            JOIN `classes` c1 ON c1.id = p.from_class_id
            LEFT JOIN `classes` c2 ON c2.id = p.to_class_id
            JOIN `sessions` ses1 ON ses1.id = p.from_session_id
            LEFT JOIN `sessions` ses2 ON ses2.id = p.to_session_id
            WHERE p.from_class_id = :class_id AND p.from_session_id = :session_id
            ORDER BY u.name ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':class_id' => $classId, ':session_id' => $sessionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $promotions = [];
        foreach ($rows as $row) {
            $promotions[(int)$row['student_id']] = Promotion::fromArray($row);
        }

        return $promotions;
    }

    /**
     * Compute cumulative session performance across terms for a student.
     */
    public function getCumulativeSessionStats(int $studentId, int $sessionId): array
    {
        // 1. Fetch all terms for this session ordered by start_date / id
        $termSql = "SELECT id, name, status FROM `terms` WHERE `session_id` = :session_id ORDER BY `id` ASC";
        $termStmt = $this->pdo->prepare($termSql);
        $termStmt->execute([':session_id' => $sessionId]);
        $sessionTerms = $termStmt->fetchAll(PDO::FETCH_ASSOC);

        $termScores = [];
        $totalSum = 0.0;
        $scoredTermsCount = 0;

        foreach ($sessionTerms as $term) {
            $tId = (int)$term['id'];
            
            // Check student_term_summaries first
            $sumSql = "SELECT average_score, total_score FROM `student_term_summaries` WHERE `student_id` = :student_id AND `term_id` = :term_id LIMIT 1";
            $sumStmt = $this->pdo->prepare($sumSql);
            $sumStmt->execute([':student_id' => $studentId, ':term_id' => $tId]);
            $sumRow = $sumStmt->fetch(PDO::FETCH_ASSOC);

            $avg = null;
            if ($sumRow && $sumRow['average_score'] !== null) {
                $avg = (float)$sumRow['average_score'];
            } else {
                // Compute average directly from term_results
                $resSql = "SELECT AVG(computed_score) as comp_avg FROM `term_results` WHERE `student_id` = :student_id AND `term_id` = :term_id";
                $resStmt = $this->pdo->prepare($resSql);
                $resStmt->execute([':student_id' => $studentId, ':term_id' => $tId]);
                $compAvg = $resStmt->fetchColumn();
                if ($compAvg !== false && $compAvg !== null) {
                    $avg = (float)$compAvg;
                }
            }

            $termScores[$tId] = [
                'term_id'   => $tId,
                'term_name' => $term['name'],
                'average'   => $avg !== null ? round($avg, 2) : null,
            ];

            if ($avg !== null) {
                $totalSum += $avg;
                $scoredTermsCount++;
            }
        }

        $annualAverage = $scoredTermsCount > 0 ? round($totalSum / $scoredTermsCount, 2) : 0.0;

        return [
            'terms'                 => $termScores,
            'annual_average'        => $annualAverage,
            'completed_terms_count' => $scoredTermsCount,
            'total_session_terms'   => count($sessionTerms),
            'has_all_terms'         => count($sessionTerms) > 0 && $scoredTermsCount === count($sessionTerms),
        ];
    }

    /**
     * Update decision and destination class.
     */
    public function updateDecision(
        int $studentId,
        int $sessionId,
        string $decision,
        ?int $toClassId = null,
        ?string $overrideReason = null
    ): bool {
        $sql = "
            UPDATE `student_promotions`
            SET `decision` = :decision,
                `to_class_id` = :to_class_id,
                `override_reason` = :override_reason,
                `updated_at` = NOW()
            WHERE `student_id` = :student_id AND `from_session_id` = :session_id
        ";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':decision'        => $decision,
            ':to_class_id'     => $toClassId,
            ':override_reason' => $overrideReason,
            ':student_id'      => $studentId,
            ':session_id'      => $sessionId,
        ]);
    }

    /**
     * Mark evaluation status as finalized.
     */
    public function markClassFinalized(int $classId, int $sessionId): int
    {
        $sql = "
            UPDATE `student_promotions`
            SET `evaluation_status` = 'finalized',
                `updated_at` = NOW()
            WHERE `from_class_id` = :class_id AND `from_session_id` = :session_id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':class_id' => $classId, ':session_id' => $sessionId]);

        return $stmt->rowCount();
    }
}
