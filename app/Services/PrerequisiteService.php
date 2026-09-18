<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Models\ActivityPrerequisite;
use App\Models\ActivityProgress;
use App\Repositories\AcademicRepository;
use App\Repositories\ActivityPrerequisiteRepository;
use App\Repositories\ActivityProgressRepository;
use App\Repositories\AssignmentRepository;
use App\Repositories\ContentRepository;
use App\Repositories\DocumentSectionRepository;
use App\Repositories\QuizRepository;

/**
 * Service for Learning Activity Prerequisites & Unlocking
 *
 * Responsibilities:
 * - Activity reference and existence validation
 * - Self-dependency rejection
 * - Circular dependency detection (DFS graph traversal)
 * - Prerequisite creation and deletion
 * - Authoritative server-side unlock evaluation
 * - Unmet prerequisites breakdown for student UI
 * - Cleanup upon activity deletion
 */
class PrerequisiteService
{
    public const SUPPORTED_TYPES = [
        ActivityProgress::TYPE_DOCUMENT,
        ActivityProgress::TYPE_DOCUMENT_SECTION,
        ActivityProgress::TYPE_QUIZ,
        ActivityProgress::TYPE_ASSIGNMENT,
    ];

    public function __construct(
        private ?ActivityPrerequisiteRepository $prereqRepo = null,
        private ?ActivityProgressRepository $progressRepo = null,
        private ?ContentRepository $contentRepo = null,
        private ?DocumentSectionRepository $sectionRepo = null,
        private ?QuizRepository $quizRepo = null,
        private ?AssignmentRepository $assignmentRepo = null,
        private ?AcademicRepository $academicRepo = null
    ) {
        $this->prereqRepo = $prereqRepo ?? new ActivityPrerequisiteRepository();
        $this->progressRepo = $progressRepo ?? new ActivityProgressRepository();
        $this->contentRepo = $contentRepo ?? new ContentRepository();
        $this->sectionRepo = $sectionRepo ?? new DocumentSectionRepository();
        $this->quizRepo = $quizRepo ?? new QuizRepository();
        $this->assignmentRepo = $assignmentRepo ?? new AssignmentRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    /**
     * Validate that an activity type is supported.
     */
    public function validateActivityType(string $type): void
    {
        if (!in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new ValidationException([
                'activity_type' => "Unsupported activity type '{$type}'. Supported types: " . implode(', ', self::SUPPORTED_TYPES)
            ]);
        }
    }

    /**
     * Validate that an activity exists in the database.
     */
    public function validateActivityExists(string $type, int $id): void
    {
        $this->validateActivityType($type);

        $exists = match ($type) {
            ActivityProgress::TYPE_DOCUMENT => $this->contentRepo->findById($id) !== null,
            ActivityProgress::TYPE_DOCUMENT_SECTION => $this->sectionRepo->findById($id) !== null,
            ActivityProgress::TYPE_QUIZ => $this->quizRepo->findById($id, false) !== null,
            ActivityProgress::TYPE_ASSIGNMENT => $this->assignmentRepo->findById($id) !== null,
            default => false,
        };

        if (!$exists) {
            throw new ResourceNotFoundException("Activity '{$type}' with ID {$id} does not exist.");
        }
    }

    /**
     * Get a human-readable title for an activity.
     */
    public function getActivityTitle(string $type, int $id): string
    {
        return match ($type) {
            ActivityProgress::TYPE_DOCUMENT => $this->contentRepo->findById($id)?->title ?? "Document #{$id}",
            ActivityProgress::TYPE_DOCUMENT_SECTION => $this->sectionRepo->findById($id)?->title ?? "Section #{$id}",
            ActivityProgress::TYPE_QUIZ => $this->quizRepo->findById($id, false)?->title ?? "Quiz #{$id}",
            ActivityProgress::TYPE_ASSIGNMENT => $this->assignmentRepo->findById($id)?->title ?? "Assignment #{$id}",
            default => ucfirst(str_replace('_', ' ', $type)) . " #{$id}",
        };
    }

    /**
     * Create a prerequisite: target activity requires prerequisite activity.
     *
     * @throws ValidationException If invalid types or self-dependency
     * @throws ResourceNotFoundException If target or prerequisite does not exist
     * @throws DomainRuleException If circular dependency detected or already exists
     */
    public function createPrerequisite(
        string $targetType,
        int $targetId,
        string $prereqType,
        int $prereqId,
        string $requirementType = ActivityPrerequisite::REQUIREMENT_COMPLETION
    ): ActivityPrerequisite {
        $this->validateActivityType($targetType);
        $this->validateActivityType($prereqType);

        // Self-dependency rejection
        if ($targetType === $prereqType && $targetId === $prereqId) {
            throw new DomainRuleException("An activity cannot require itself as a prerequisite.");
        }

        // Validate existence
        $this->validateActivityExists($targetType, $targetId);
        $this->validateActivityExists($prereqType, $prereqId);

        // Check if duplicate
        $existing = $this->prereqRepo->findRelationship($targetType, $targetId, $prereqType, $prereqId);
        if ($existing) {
            throw new DomainRuleException("This prerequisite relationship already exists.");
        }

        // Circular dependency check
        if ($this->detectCycle($targetType, $targetId, $prereqType, $prereqId)) {
            throw new DomainRuleException("Cannot add prerequisite: this would create a circular dependency loop.");
        }

        return $this->prereqRepo->create(
            activityType: $targetType,
            activityId: $targetId,
            prereqType: $prereqType,
            prereqId: $prereqId,
            requirementType: $requirementType
        );
    }

    /**
     * Delete a prerequisite relationship by ID.
     */
    public function deletePrerequisite(int $id): bool
    {
        return $this->prereqRepo->delete($id);
    }

    /**
     * Clean up all prerequisites for an activity when it is deleted.
     */
    public function deletePrerequisitesForActivity(string $type, int $id): int
    {
        return $this->prereqRepo->deleteForActivity($type, $id);
    }

    /**
     * Check whether adding an edge (target -> requires -> prereq) creates a cycle.
     * In dependency terms: Target depends on Prereq.
     * A cycle exists if Prereq already directly or indirectly depends on Target.
     * That is, if there is an existing path from Prereq to Target:
     * Prereq -> ... -> Target.
     * We run a DFS starting from ($prereqType, $prereqId) searching for ($targetType, $targetId).
     */
    public function detectCycle(
        string $targetType,
        int $targetId,
        string $prereqType,
        int $prereqId
    ): bool {
        $targetKey = "{$targetType}:{$targetId}";
        $startKey = "{$prereqType}:{$prereqId}";

        if ($startKey === $targetKey) {
            return true;
        }

        $visited = [];
        $stack = [$startKey];

        while (!empty($stack)) {
            $currKey = array_pop($stack);
            if (isset($visited[$currKey])) {
                continue;
            }
            $visited[$currKey] = true;

            [$cType, $cId] = explode(':', $currKey);
            $cId = (int)$cId;

            // Find what $currKey requires (its prerequisites)
            $prerequisites = $this->prereqRepo->getPrerequisitesForActivity($cType, $cId);
            foreach ($prerequisites as $p) {
                $nextKey = "{$p->prerequisiteActivityType}:{$p->prerequisiteActivityId}";
                if ($nextKey === $targetKey) {
                    return true; // Cycle detected!
                }
                if (!isset($visited[$nextKey])) {
                    $stack[] = $nextKey;
                }
            }
        }

        return false;
    }

    /**
     * Authoritative server-side check: Is activity unlocked for student?
     *
     * Rules:
     * 1. If activity has NO prerequisites => UNLOCKED (true).
     * 2. If activity has prerequisites => ALL must be completed in learning_activity_progress.
     * 3. If progress row is missing => NOT completed => LOCKED (false).
     */
    public function isActivityUnlocked(int $studentId, string $activityType, int $activityId): bool
    {
        $prerequisites = $this->prereqRepo->getPrerequisitesForActivity($activityType, $activityId);
        if (empty($prerequisites)) {
            return true;
        }

        foreach ($prerequisites as $prereq) {
            $progress = $this->progressRepo->findByStudentAndActivity(
                $studentId,
                $prereq->prerequisiteActivityType,
                $prereq->prerequisiteActivityId
            );

            if (!$progress || !$progress->isCompleted()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get detailed status of all prerequisites for an activity for a given student.
     * Returns:
     * [
     *   'is_unlocked' => bool,
     *   'total_count' => int,
     *   'completed_count' => int,
     *   'prerequisites' => [
     *       [
     *           'id' => int,
     *           'type' => string,
     *           'activity_id' => int,
     *           'title' => string,
     *           'is_completed' => bool,
     *           'completed_at' => ?string,
     *           'progress_percent' => float,
     *       ], ...
     *   ],
     *   'unmet' => [ ... list of unmet prerequisite items ... ]
     * ]
     */
    public function getPrerequisiteStatus(int $studentId, string $activityType, int $activityId): array
    {
        $prerequisites = $this->prereqRepo->getPrerequisitesForActivity($activityType, $activityId);

        if (empty($prerequisites)) {
            return [
                'is_unlocked' => true,
                'total_count' => 0,
                'completed_count' => 0,
                'prerequisites' => [],
                'unmet' => [],
            ];
        }

        $items = [];
        $unmet = [];
        $completedCount = 0;

        foreach ($prerequisites as $prereq) {
            $progress = $this->progressRepo->findByStudentAndActivity(
                $studentId,
                $prereq->prerequisiteActivityType,
                $prereq->prerequisiteActivityId
            );

            $isCompleted = $progress?->isCompleted() ?? false;
            if ($isCompleted) {
                $completedCount++;
            }

            $title = $this->getActivityTitle($prereq->prerequisiteActivityType, $prereq->prerequisiteActivityId);

            $item = [
                'prerequisite_id' => $prereq->id,
                'type' => $prereq->prerequisiteActivityType,
                'activity_id' => $prereq->prerequisiteActivityId,
                'title' => $title,
                'is_completed' => $isCompleted,
                'completed_at' => $progress?->completedAt,
                'progress_percent' => $progress?->progressPercent ?? 0.0,
            ];

            $items[] = $item;
            if (!$isCompleted) {
                $unmet[] = $item;
            }
        }

        return [
            'is_unlocked' => count($unmet) === 0,
            'total_count' => count($prerequisites),
            'completed_count' => $completedCount,
            'prerequisites' => $items,
            'unmet' => $unmet,
        ];
    }

    /**
     * Get list of configured prerequisites for an activity (without student status).
     *
     * @return array<int, array{id: int, type: string, activity_id: int, title: string}>
     */
    public function getConfiguredPrerequisites(string $activityType, int $activityId): array
    {
        $prerequisites = $this->prereqRepo->getPrerequisitesForActivity($activityType, $activityId);
        $result = [];

        foreach ($prerequisites as $p) {
            $result[] = [
                'id' => $p->id,
                'type' => $p->prerequisiteActivityType,
                'activity_id' => $p->prerequisiteActivityId,
                'title' => $this->getActivityTitle($p->prerequisiteActivityType, $p->prerequisiteActivityId),
                'requirement_type' => $p->requirementType,
            ];
        }

        return $result;
    }
}
