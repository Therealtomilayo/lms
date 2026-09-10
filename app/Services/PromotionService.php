<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\DTO\ServiceResult;
use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\Promotion;
use App\Models\SchoolClass;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\PromotionRepository;
use App\Repositories\ResultPublicationRepository;
use App\Repositories\StudentRepository;
use PDO;

/**
 * Service Layer for Student Promotions, Terminal Graduation & Cohort Advancement (SRS §17, §18, §58.3)
 */
class PromotionService
{
    public const PASSING_AVERAGE_THRESHOLD = 50.00;
    public const BORDERLINE_MIN_THRESHOLD = 40.00;

    private PromotionRepository $promotionRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;
    private StudentRepository $studentRepo;
    private ResultPublicationRepository $publicationRepo;
    private GradebookRepository $gradebookRepo;
    private ApprovalService $approvalService;
    private PDO $pdo;

    public function __construct(
        ?PromotionRepository $promotionRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?EnrollmentRepository $enrollmentRepo = null,
        ?StudentRepository $studentRepo = null,
        ?ResultPublicationRepository $publicationRepo = null,
        ?GradebookRepository $gradebookRepo = null,
        ?ApprovalService $approvalService = null,
        ?PDO $pdo = null
    ) {
        $this->pdo = $pdo ?? Database::getInstance();
        $this->promotionRepo = $promotionRepo ?? new PromotionRepository($this->pdo);
        $this->academicRepo = $academicRepo ?? new AcademicRepository($this->pdo);
        $this->enrollmentRepo = $enrollmentRepo ?? new EnrollmentRepository($this->pdo);
        $this->studentRepo = $studentRepo ?? new StudentRepository($this->pdo);
        $this->publicationRepo = $publicationRepo ?? new ResultPublicationRepository($this->pdo);
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository($this->pdo);
        $this->approvalService = $approvalService ?? new ApprovalService();
    }

    /**
     * Resolve the 3rd Term (final term) of an academic session.
     */
    public function getFinalTermOfSession(int $sessionId): ?object
    {
        $terms = $this->academicRepo->getTermsBySession($sessionId);
        if (empty($terms)) {
            return null;
        }

        // Look for Third / 3rd term by name or take the last term chronologically
        foreach ($terms as $t) {
            $name = strtolower($t->name);
            if (str_contains($name, 'third') || str_contains($name, '3rd')) {
                return $t;
            }
        }

        return end($terms) ?: null;
    }

    /**
     * Check if 3rd Term results for a session are published.
     */
    public function isFinalTermPublished(int $sessionId): bool
    {
        $finalTerm = $this->getFinalTermOfSession($sessionId);
        if (!$finalTerm) {
            return false;
        }

        return $this->publicationRepo->isPublished($finalTerm->id);
    }

    /**
     * Get system-wide promotion overview for an academic session.
     */
    public function getSessionPromotionOverview(int $sessionId): array
    {
        $session = $this->academicRepo->findSessionById($sessionId);
        $finalTerm = $this->getFinalTermOfSession($sessionId);
        $isPublished = $finalTerm ? $this->publicationRepo->isPublished($finalTerm->id) : false;

        $classes = $this->academicRepo->getAllClasses();
        $classRows = [];

        $totalStudents = 0;
        $totalPromoted = 0;
        $totalGraduated = 0;
        $totalRepeating = 0;
        $totalPending = 0;

        foreach ($classes as $c) {
            $roster = $this->enrollmentRepo->getClassRoster($c->id, $sessionId);
            $rosterCount = count($roster);
            $totalStudents += $rosterCount;

            $level = $this->academicRepo->findLevelById($c->academicLevelId);
            $isTerminal = $level ? $this->academicRepo->isTerminalLevel($level->id) : false;

            $promotions = $this->promotionRepo->getPromotionsByClass($c->id, $sessionId);
            $classFinalized = count(array_filter($promotions, fn($p) => $p->isFinalized())) === $rosterCount && $rosterCount > 0;

            $classPromoted = 0;
            $classGraduated = 0;
            $classRepeating = 0;

            foreach ($promotions as $p) {
                if ($p->isGraduated()) $classGraduated++;
                elseif ($p->isPromoted()) $classPromoted++;
                elseif ($p->isRepeating()) $classRepeating++;
            }

            $totalPromoted += $classPromoted;
            $totalGraduated += $classGraduated;
            $totalRepeating += $classRepeating;
            $totalPending += ($rosterCount - count($promotions));

            $classRows[] = [
                'class'            => $c,
                'level'            => $level,
                'level_name'       => $level?->name ?? 'Academic Level',
                'is_terminal'      => $isTerminal,
                'student_count'    => $rosterCount,
                'students_count'   => $rosterCount,
                'evaluated'        => count($promotions),
                'promoted'         => $classPromoted,
                'promoted_count'   => $classPromoted,
                'graduated'        => $classGraduated,
                'graduated_count'  => $classGraduated,
                'borderline_count' => 0,
                'repeating'        => $classRepeating,
                'repeating_count'  => $classRepeating,
                'is_finalized'     => $classFinalized,
            ];
        }

        $allSessions = $this->academicRepo->getAllSessions();
        $targetSessions = array_filter($allSessions, fn($s) => $s->id !== $sessionId);

        return [
            'session'          => $session,
            'final_term'       => $finalTerm,
            'is_published'     => $isPublished,
            'total_students'   => $totalStudents,
            'total_promoted'   => $totalPromoted,
            'total_graduated'  => $totalGraduated,
            'total_repeating'  => $totalRepeating,
            'total_pending'    => $totalPending,
            'classes'          => $classRows,
            'all_sessions'     => $allSessions,
            'target_sessions'  => array_values($targetSessions),
        ];
    }

    /**
     * Evaluate cohort performance for a specific class in an academic session.
     */
    public function evaluateClassCohort(int $classId, int $sessionId): array
    {
        $class = $this->academicRepo->findClassById($classId);
        if (!$class) {
            throw new \InvalidArgumentException("Class #{$classId} not found.");
        }

        $session = $this->academicRepo->findSessionById($sessionId);
        $finalTerm = $this->getFinalTermOfSession($sessionId);
        $isPublished = $finalTerm ? $this->publicationRepo->isPublished($finalTerm->id) : false;

        $level = $this->academicRepo->findLevelById($class->academicLevelId);
        $isTerminal = $level ? $this->academicRepo->isTerminalLevel($level->id) : false;
        $nextLevel = $level ? $this->academicRepo->getNextAcademicLevel($level->id) : null;

        // Candidate destination classes in next level
        $destinationClasses = $nextLevel ? $this->academicRepo->getClassesByLevel($nextLevel->id) : [];
        $sameLevelClasses = $level ? $this->academicRepo->getClassesByLevel($level->id) : [];

        $roster = $this->enrollmentRepo->getClassRoster($classId, $sessionId);
        $existingPromotions = $this->promotionRepo->getPromotionsByClass($classId, $sessionId);

        $evaluations = [];

        foreach ($roster as $enrollment) {
            $studentId = $enrollment->studentId;
            $stats = $this->promotionRepo->getCumulativeSessionStats($studentId, $sessionId);
            $annualAvg = $stats['annual_average'];

            // Determine default automated recommendation
            $autoDecision = Promotion::DECISION_REPEATING;
            $recommendedClassId = null;

            if ($annualAvg >= self::PASSING_AVERAGE_THRESHOLD) {
                if ($isTerminal) {
                    $autoDecision = Promotion::DECISION_GRADUATED;
                    $recommendedClassId = null;
                } else {
                    $autoDecision = Promotion::DECISION_PROMOTED;
                    // Auto-select corresponding arm if available (e.g. JSS 1A -> JSS 2A)
                    if (!empty($destinationClasses)) {
                        $matched = null;
                        foreach ($destinationClasses as $dc) {
                            if (!empty($class->sectionArm) && $dc->sectionArm === $class->sectionArm) {
                                $matched = $dc->id;
                                break;
                            }
                        }
                        $recommendedClassId = $matched ?? $destinationClasses[0]->id;
                    }
                }
            } elseif ($annualAvg >= self::BORDERLINE_MIN_THRESHOLD) {
                // Borderline case: repeat by default, but flag as review candidate
                $autoDecision = Promotion::DECISION_REPEATING;
                $recommendedClassId = $class->id;
            } else {
                $autoDecision = Promotion::DECISION_REPEATING;
                $recommendedClassId = $class->id;
            }

            $promotionRecord = $existingPromotions[$studentId] ?? null;
            $currentDecision = $promotionRecord ? $promotionRecord->decision : $autoDecision;
            $targetClassId = $promotionRecord ? $promotionRecord->toClassId : $recommendedClassId;

            $studentObj = $enrollment->student ?? $this->studentRepo->findById($studentId);
            $studentName = $studentObj?->user?->name ?? $studentObj?->name ?? 'Student';
            $admissionNumber = $studentObj?->admissionNumber ?? '';
            $isBorderline = $annualAvg >= self::BORDERLINE_MIN_THRESHOLD && $annualAvg < self::PASSING_AVERAGE_THRESHOLD;
            $status = $promotionRecord ? $promotionRecord->evaluationStatus : Promotion::STATUS_PRELIMINARY;

            $evaluations[] = [
                'enrollment'          => $enrollment,
                'student'             => $studentObj,
                'student_id'          => $studentId,
                'student_name'        => $studentName,
                'admission_number'    => $admissionNumber,
                'term_stats'          => $stats['terms'],
                'cumulative_stats'    => $stats,
                'annual_average'      => $annualAvg,
                'suggested_decision'  => $isBorderline ? 'borderline' : $autoDecision,
                'auto_decision'       => $autoDecision,
                'decision'            => $currentDecision,
                'current_decision'    => $currentDecision,
                'target_class_id'     => $targetClassId,
                'is_borderline'       => $isBorderline,
                'is_terminal'         => $isTerminal,
                'status'              => $status,
                'promotion_record'    => $promotionRecord,
            ];
        }

        usort($evaluations, fn($a, $b) => $b['annual_average'] <=> $a['annual_average']);
        foreach ($evaluations as $idx => &$evalRow) {
            $evalRow['rank'] = $idx + 1;
        }
        unset($evalRow);

        $promotedCount = count(array_filter($evaluations, fn($e) => $e['auto_decision'] === Promotion::DECISION_PROMOTED));
        $graduatedCount = count(array_filter($evaluations, fn($e) => $e['auto_decision'] === Promotion::DECISION_GRADUATED));
        $borderlineCount = count(array_filter($evaluations, fn($e) => $e['is_borderline']));
        $repeatingCount = count(array_filter($evaluations, fn($e) => $e['auto_decision'] === Promotion::DECISION_REPEATING && !$e['is_borderline']));
        $allSessions = $this->academicRepo->getAllSessions();
        $targetSessions = array_filter($allSessions, fn($s) => $s->id !== $sessionId);
        $isFinalized = !empty($evaluations) && count(array_filter($evaluations, fn($e) => ($e['status'] ?? '') === Promotion::STATUS_FINALIZED)) === count($evaluations);

        return [
            'class'                   => $class,
            'level'                   => $level,
            'is_terminal'             => $isTerminal,
            'next_level'              => $nextLevel,
            'session'                 => $session,
            'final_term'              => $finalTerm,
            'is_published'            => $isPublished,
            'is_final_term_published' => $isPublished,
            'destination_classes'     => $destinationClasses,
            'suggested_next_classes'  => $destinationClasses,
            'same_level_classes'      => $sameLevelClasses,
            'evaluations'             => $evaluations,
            'students'                => $evaluations,
            'total_students'          => count($evaluations),
            'promoted_count'          => $promotedCount,
            'graduated_count'         => $graduatedCount,
            'borderline_count'        => $borderlineCount,
            'repeating_count'         => $repeatingCount,
            'is_finalized'            => $isFinalized,
            'target_sessions'         => array_values($targetSessions),
        ];
    }

    /**
     * Stage a borderline student repetition for Super Admin two-tier approval.
     */
    public function stageBorderlineRepetition(
        int $studentId,
        int $classId,
        int $sessionId,
        int $actorId,
        string $reason
    ): ServiceResult {
        $finalTerm = $this->getFinalTermOfSession($sessionId);
        $termId = $finalTerm ? (int)$finalTerm->id : 1;

        $stats = $this->promotionRepo->getCumulativeSessionStats($studentId, $sessionId);

        $request = $this->approvalService->stageStudentRepetition(
            studentId: $studentId,
            classId: $classId,
            termId: $termId,
            requesterId: $actorId,
            reason: $reason,
            metadata: [
                'session_id'     => $sessionId,
                'annual_average' => $stats['annual_average'],
                'term_averages'  => $stats['terms'],
            ]
        );

        $this->promotionRepo->recordPromotion([
            'student_id'          => $studentId,
            'from_session_id'     => $sessionId,
            'from_class_id'       => $classId,
            'decision'            => Promotion::DECISION_REPEATING,
            'annual_average'      => $stats['annual_average'],
            'term_averages'       => $stats['terms'],
            'evaluation_status'   => Promotion::STATUS_STAGED_REVIEW,
            'approval_request_id' => $request->id,
            'override_reason'     => $reason,
            'promoted_by'         => $actorId,
        ]);

        return ServiceResult::success([
            'approval_request' => $request,
            'message'          => "Borderline candidate staged to Super Admin approval queue (Request #{$request->id}).",
        ]);
    }

    /**
     * Commit batch cohort advancement for a class.
     * 
     * @param int $classId Outgoing class
     * @param int $fromSessionId Outgoing session
     * @param int $toSessionId Incoming session
     * @param int $actorId Authenticated administrator ID
     * @param array<int, array{decision: string, to_class_id: ?int, reason: ?string}> $decisions Custom student overrides
     */
    public function commitBatchPromotion(
        int $classId,
        int $fromSessionId,
        int $toSessionId,
        int $actorId,
        array $decisions = []
    ): ServiceResult {
        // 1. Strict Gate: 3rd Term results must be officially released!
        if (!$this->isFinalTermPublished($fromSessionId)) {
            return ServiceResult::error(
                "Cannot finalize promotions: 3rd Term results have not yet been approved and officially published by the administration."
            );
        }

        if ($fromSessionId === $toSessionId) {
            return ServiceResult::error("Destination academic session must be different from the outgoing session.");
        }

        $class = $this->academicRepo->findClassById($classId);
        if (!$class) {
            return ServiceResult::error("Class #{$classId} not found.");
        }

        $level = $this->academicRepo->findLevelById($class->academicLevelId);
        $isTerminal = $level ? $this->academicRepo->isTerminalLevel($level->id) : false;
        $roster = $this->enrollmentRepo->getClassRoster($classId, $fromSessionId);

        if (empty($roster)) {
            return ServiceResult::error("No active student enrollments found in class {$class->name} for the selected session.");
        }

        $now = date('Y-m-d H:i:s');
        $promotedCount = 0;
        $graduatedCount = 0;
        $repeatingCount = 0;

        $this->pdo->beginTransaction();

        try {
            foreach ($roster as $enrollment) {
                $studentId = $enrollment->studentId;
                $stats = $this->promotionRepo->getCumulativeSessionStats($studentId, $fromSessionId);
                $annualAvg = $stats['annual_average'];

                // Check override decision or default
                $override = $decisions[$studentId] ?? null;
                $decision = $override['decision'] ?? null;
                $toClassId = isset($override['to_class_id']) && $override['to_class_id'] !== '' ? (int)$override['to_class_id'] : null;
                $reason = $override['reason'] ?? null;

                if (!$decision) {
                    if ($annualAvg >= self::PASSING_AVERAGE_THRESHOLD) {
                        $decision = $isTerminal ? Promotion::DECISION_GRADUATED : Promotion::DECISION_PROMOTED;
                    } else {
                        $decision = Promotion::DECISION_REPEATING;
                    }
                }

                // Enforce Terminal Graduation: Primary 5 and SS 3 students can NEVER advance to another class
                if ($isTerminal && $decision === Promotion::DECISION_PROMOTED) {
                    $decision = Promotion::DECISION_GRADUATED;
                    $toClassId = null;
                }

                // 1. Record Promotion in student_promotions
                $this->promotionRepo->recordPromotion([
                    'student_id'        => $studentId,
                    'from_session_id'   => $fromSessionId,
                    'from_class_id'     => $classId,
                    'to_session_id'     => $decision === Promotion::DECISION_GRADUATED ? null : $toSessionId,
                    'to_class_id'       => $toClassId,
                    'decision'          => $decision,
                    'annual_average'    => $annualAvg,
                    'term_averages'     => $stats['terms'],
                    'evaluation_status' => Promotion::STATUS_FINALIZED,
                    'override_reason'   => $reason,
                    'promoted_by'       => $actorId,
                    'promoted_at'       => $now,
                ]);

                // 2. Update outgoing session class_enrollments status
                $enrollmentStatus = match($decision) {
                    Promotion::DECISION_GRADUATED => 'graduated',
                    Promotion::DECISION_PROMOTED  => 'promoted',
                    Promotion::DECISION_REPEATING => 'repeating',
                    default                       => 'active'
                };
                $this->enrollmentRepo->updateClassEnrollmentStatus($enrollment->id, $enrollmentStatus);

                // 3. Update student_term_summaries for final term
                $finalTerm = $this->getFinalTermOfSession($fromSessionId);
                if ($finalTerm) {
                    $termSummaryStatus = match($decision) {
                        Promotion::DECISION_GRADUATED => 'graduated',
                        Promotion::DECISION_PROMOTED  => 'promoted',
                        Promotion::DECISION_REPEATING => 'repeating',
                        default                       => 'pending'
                    };
                    $sumStmt = $this->pdo->prepare("
                        UPDATE `student_term_summaries` 
                        SET `promotion_status` = :status, `updated_at` = :now
                        WHERE `student_id` = :student_id AND `term_id` = :term_id
                    ");
                    $sumStmt->execute([
                        ':status'     => $termSummaryStatus,
                        ':now'        => $now,
                        ':student_id' => $studentId,
                        ':term_id'    => $finalTerm->id,
                    ]);
                }

                // 4. Handle target action
                if ($decision === Promotion::DECISION_GRADUATED) {
                    $graduatedCount++;
                    // Remove current class pointer for graduated alumnus
                    $stuStmt = $this->pdo->prepare("UPDATE `students` SET `current_class_id` = NULL, `updated_at` = :now WHERE `id` = :id");
                    $stuStmt->execute([':now' => $now, ':id' => $studentId]);

                } elseif ($decision === Promotion::DECISION_PROMOTED && $toClassId) {
                    $promotedCount++;
                    // Create new active enrollment in destination session & class
                    $this->enrollmentRepo->enrollInClass($studentId, $toClassId, $toSessionId, 'active');
                    // Update current class on student record
                    $stuStmt = $this->pdo->prepare("UPDATE `students` SET `current_class_id` = :cid, `updated_at` = :now WHERE `id` = :id");
                    $stuStmt->execute([':cid' => $toClassId, ':now' => $now, ':id' => $studentId]);

                } elseif ($decision === Promotion::DECISION_REPEATING && $toClassId) {
                    $repeatingCount++;
                    // Create repeating enrollment in destination session
                    $this->enrollmentRepo->enrollInClass($studentId, $toClassId, $toSessionId, 'repeating');
                    $stuStmt = $this->pdo->prepare("UPDATE `students` SET `current_class_id` = :cid, `updated_at` = :now WHERE `id` = :id");
                    $stuStmt->execute([':cid' => $toClassId, ':now' => $now, ':id' => $studentId]);
                }
            }

            $this->pdo->commit();

            return ServiceResult::success([
                'promoted'        => $promotedCount,
                'graduated'       => $graduatedCount,
                'repeating'       => $repeatingCount,
                'promoted_count'  => $promotedCount,
                'graduated_count' => $graduatedCount,
                'repeating_count' => $repeatingCount,
                'total'           => count($roster),
                'message'         => "Batch advancement committed successfully: {$promotedCount} promoted, {$graduatedCount} graduated alumni, {$repeatingCount} repeating.",
            ]);

        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            return ServiceResult::error("Batch advancement failed: " . $e->getMessage());
        }
    }
}
