<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\Models\ActivityProgress;
use App\Models\ClassSubject;
use App\Models\Module;
use App\Models\ModuleItem;
use App\Policies\ContentPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\ActivityPrerequisiteRepository;
use App\Repositories\ActivityProgressRepository;
use App\Repositories\AssignmentRepository;
use App\Repositories\ContentRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\FileRepository;
use App\Repositories\ModuleRepository;
use App\Repositories\QuizRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use PDO;

/**
 * Service for Course / Module Progression and Structured Learning Paths
 * Handles module lifecycle, deterministic ordering, activity assignment,
 * authoritative progress derivation, and prerequisite integration.
 */
class ModuleService
{
    private PDO $pdo;
    private ModuleRepository $moduleRepo;
    private ContentRepository $contentRepo;
    private QuizRepository $quizRepo;
    private AssignmentRepository $assignmentRepo;
    private ActivityProgressRepository $progressRepo;
    private ActivityPrerequisiteRepository $prereqRepo;
    private PrerequisiteService $prereqService;
    private AcademicRepository $academicRepo;
    private TeacherRepository $teacherRepo;
    private StudentRepository $studentRepo;
    private EnrollmentRepository $enrollmentRepo;
    private FileRepository $fileRepo;
    private ?BadgeService $badgeService = null;

    public function __construct(
        ?PDO $pdo = null,
        ?ModuleRepository $moduleRepo = null,
        ?ContentRepository $contentRepo = null,
        ?QuizRepository $quizRepo = null,
        ?AssignmentRepository $assignmentRepo = null,
        ?ActivityProgressRepository $progressRepo = null,
        ?PrerequisiteService $prereqService = null,
        ?AcademicRepository $academicRepo = null,
        ?TeacherRepository $teacherRepo = null,
        ?StudentRepository $studentRepo = null,
        ?EnrollmentRepository $enrollmentRepo = null,
        ?FileRepository $fileRepo = null,
        ?ActivityPrerequisiteRepository $prereqRepo = null,
        ?BadgeService $badgeService = null
    ) {
        $this->pdo = $pdo ?? Database::getInstance();
        $this->moduleRepo = $moduleRepo ?? new ModuleRepository($this->pdo);
        $this->contentRepo = $contentRepo ?? new ContentRepository($this->pdo);
        $this->quizRepo = $quizRepo ?? new QuizRepository($this->pdo);
        $this->assignmentRepo = $assignmentRepo ?? new AssignmentRepository($this->pdo);
        $this->progressRepo = $progressRepo ?? new ActivityProgressRepository($this->pdo);
        $this->prereqRepo = $prereqRepo ?? new ActivityPrerequisiteRepository($this->pdo);
        $this->academicRepo = $academicRepo ?? new AcademicRepository($this->pdo);
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository($this->pdo);
        $this->studentRepo = $studentRepo ?? new StudentRepository($this->pdo);
        $this->enrollmentRepo = $enrollmentRepo ?? new EnrollmentRepository($this->pdo);
        $this->fileRepo = $fileRepo ?? new FileRepository($this->pdo);
        $this->prereqService = $prereqService ?? new PrerequisiteService(
            prereqRepo: $this->prereqRepo,
            progressRepo: $this->progressRepo,
            contentRepo: $this->contentRepo,
            quizRepo: $this->quizRepo,
            assignmentRepo: $this->assignmentRepo,
            academicRepo: $this->academicRepo
        );
        $this->badgeService = $badgeService;
    }

    /**
     * Validate that the authenticated actor has teacher/admin rights to manage a class subject.
     *
     * @throws AuthorizationException
     * @throws ResourceNotFoundException
     */
    public function validateTeacherAccess(UserContext $actor, int $classSubjectId): void
    {
        if ($actor->hasAnyRole(['super_admin', 'admin'])) {
            return;
        }

        if (!$actor->hasRole('teacher')) {
            throw new AuthorizationException('Access denied: Teacher profile required.');
        }

        $teacher = $this->teacherRepo->findTeacherByUserId($actor->id);
        if (!$teacher) {
            throw new AuthorizationException('Access denied: Teacher profile not found.');
        }

        $classSubject = $this->academicRepo->findClassSubjectById($classSubjectId);
        if (!$classSubject) {
            throw new ResourceNotFoundException('Class subject offering not found.');
        }

        if ($classSubject->teacherId !== $teacher->id) {
            throw new AuthorizationException('Access denied: You are not the assigned teacher for this subject.');
        }
    }

    /**
     * Get all modules for a subject for teacher management, populated with item details.
     *
     * @return Module[]
     */
    public function getModulesForTeacher(int $classSubjectId, UserContext $actor): array
    {
        $this->validateTeacherAccess($actor, $classSubjectId);

        $modules = $this->moduleRepo->getModulesByClassSubject($classSubjectId, false);
        if (empty($modules)) {
            return [];
        }

        $moduleIds = array_map(fn(Module $m) => $m->id, $modules);
        $itemsGrouped = $this->moduleRepo->getItemsByModuleIds($moduleIds);

        foreach ($modules as $module) {
            $items = $itemsGrouped[$module->id] ?? [];
            foreach ($items as $item) {
                $this->populateItemMetadata($item);
            }
            $module->items = $items;
            $module->totalItemsCount = count($items);
        }

        return $modules;
    }

    /**
     * Create a new module.
     *
     * @throws ValidationException
     * @throws AuthorizationException
     */
    public function createModule(int $classSubjectId, array $data, UserContext $actor): Module
    {
        $this->validateTeacherAccess($actor, $classSubjectId);

        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') {
            throw new ValidationException(['title' => 'Module title is required.']);
        }
        if (mb_strlen($title) > 200) {
            throw new ValidationException(['title' => 'Module title cannot exceed 200 characters.']);
        }

        $description = isset($data['description']) && trim((string)$data['description']) !== ''
            ? trim((string)$data['description'])
            : null;

        $sequenceOrder = isset($data['sequence_order']) && (int)$data['sequence_order'] > 0
            ? (int)$data['sequence_order']
            : $this->moduleRepo->getNextModuleSequenceOrder($classSubjectId);

        $status = (string)($data['status'] ?? Module::STATUS_PUBLISHED);
        if (!in_array($status, [Module::STATUS_PUBLISHED, Module::STATUS_DRAFT], true)) {
            $status = Module::STATUS_PUBLISHED;
        }

        $moduleId = $this->moduleRepo->createModule([
            'class_subject_id' => $classSubjectId,
            'title' => $title,
            'description' => $description,
            'sequence_order' => $sequenceOrder,
            'status' => $status,
        ]);

        $created = $this->moduleRepo->findModuleById($moduleId);
        if (!$created) {
            throw new ResourceNotFoundException('Failed to retrieve newly created module.');
        }

        return $created;
    }

    /**
     * Update an existing module.
     *
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ResourceNotFoundException
     */
    public function updateModule(int $moduleId, array $data, UserContext $actor): Module
    {
        $module = $this->moduleRepo->findModuleById($moduleId);
        if (!$module) {
            throw new ResourceNotFoundException("Module with ID {$moduleId} does not exist.");
        }

        $this->validateTeacherAccess($actor, $module->classSubjectId);

        $updateData = [];

        if (array_key_exists('title', $data)) {
            $title = trim((string)$data['title']);
            if ($title === '') {
                throw new ValidationException(['title' => 'Module title cannot be empty.']);
            }
            if (mb_strlen($title) > 200) {
                throw new ValidationException(['title' => 'Module title cannot exceed 200 characters.']);
            }
            $updateData['title'] = $title;
        }

        if (array_key_exists('description', $data)) {
            $desc = trim((string)$data['description']);
            $updateData['description'] = $desc !== '' ? $desc : null;
        }

        if (array_key_exists('sequence_order', $data)) {
            $seq = (int)$data['sequence_order'];
            if ($seq > 0) {
                $updateData['sequence_order'] = $seq;
            }
        }

        if (array_key_exists('status', $data)) {
            $status = (string)$data['status'];
            if (in_array($status, [Module::STATUS_PUBLISHED, Module::STATUS_DRAFT], true)) {
                $updateData['status'] = $status;
            }
        }

        if (!empty($updateData)) {
            $this->moduleRepo->updateModule($moduleId, $updateData);
        }

        return $this->moduleRepo->findModuleById($moduleId);
    }

    /**
     * Safely delete a module.
     *
     * @throws DomainRuleException If module still contains activities and force is false
     * @throws AuthorizationException
     * @throws ResourceNotFoundException
     */
    public function deleteModule(int $moduleId, UserContext $actor, bool $force = false): bool
    {
        $module = $this->moduleRepo->findModuleById($moduleId);
        if (!$module) {
            throw new ResourceNotFoundException("Module with ID {$moduleId} does not exist.");
        }

        $this->validateTeacherAccess($actor, $module->classSubjectId);

        $itemCount = $this->moduleRepo->countItemsInModule($moduleId);
        if ($itemCount > 0 && !$force) {
            throw new DomainRuleException(
                "Cannot delete module '{$module->title}' because it contains {$itemCount} learning activity(ies). " .
                "Please remove or reassign the activities first."
            );
        }

        return $this->moduleRepo->deleteModule($moduleId);
    }

    /**
     * Reorder modules within a class subject.
     *
     * @param int $classSubjectId
     * @param int[] $moduleIds
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function reorderModules(int $classSubjectId, array $moduleIds, UserContext $actor): bool
    {
        $this->validateTeacherAccess($actor, $classSubjectId);

        $cleanIds = array_values(array_unique(array_filter(array_map('intval', $moduleIds), fn($id) => $id > 0)));
        if (empty($cleanIds)) {
            throw new ValidationException(['modules' => 'Module IDs list cannot be empty.']);
        }

        // Verify all modules belong to this class subject
        $existingModules = $this->moduleRepo->getModulesByClassSubject($classSubjectId, false);
        $existingMap = [];
        foreach ($existingModules as $m) {
            $existingMap[$m->id] = true;
        }

        foreach ($cleanIds as $id) {
            if (!isset($existingMap[$id])) {
                throw new ValidationException(
                    ['modules' => "Module ID {$id} does not belong to this class subject."],
                    "Module ID {$id} does not belong to this class subject."
                );
            }
        }

        return $this->moduleRepo->reorderModules($classSubjectId, $cleanIds);
    }

    /**
     * Assign an activity (document, quiz, assignment) to a module.
     *
     * @throws ResourceNotFoundException
     * @throws DomainRuleException
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function addActivityToModule(
        int $moduleId,
        string $activityType,
        int $activityId,
        ?int $sequenceOrder = null,
        bool $isRequired = true,
        UserContext $actor = null
    ): ModuleItem {
        $module = $this->moduleRepo->findModuleById($moduleId);
        if (!$module) {
            throw new ResourceNotFoundException("Module with ID {$moduleId} does not exist.");
        }

        if ($actor !== null) {
            $this->validateTeacherAccess($actor, $module->classSubjectId);
        }

        // Validate supported activity types
        if (!in_array($activityType, [ModuleItem::TYPE_DOCUMENT, ModuleItem::TYPE_QUIZ, ModuleItem::TYPE_ASSIGNMENT], true)) {
            throw new ValidationException([
                'activity_type' => "Unsupported activity type '{$activityType}'. Supported: document, quiz, assignment."
            ]);
        }

        // Validate activity exists and belongs to the exact same class_subject_id
        $this->validateActivitySubjectAssociation($activityType, $activityId, $module->classSubjectId);

        // Check if already present in this module
        $existing = $this->moduleRepo->findItem($moduleId, $activityType, $activityId);
        if ($existing) {
            throw new DomainRuleException("This activity is already attached to this module.");
        }

        $itemId = $this->moduleRepo->addItem($moduleId, $activityType, $activityId, $sequenceOrder, $isRequired);
        $item = $this->moduleRepo->findItemById($itemId);
        $this->populateItemMetadata($item);

        return $item;
    }

    /**
     * Remove an activity from a module.
     *
     * @throws ResourceNotFoundException
     * @throws AuthorizationException
     */
    public function removeActivityFromModule(int $itemId, UserContext $actor): bool
    {
        $item = $this->moduleRepo->findItemById($itemId);
        if (!$item) {
            throw new ResourceNotFoundException("Module item with ID {$itemId} does not exist.");
        }

        $module = $this->moduleRepo->findModuleById($item->moduleId);
        if (!$module) {
            throw new ResourceNotFoundException("Parent module for item ID {$itemId} not found.");
        }

        $this->validateTeacherAccess($actor, $module->classSubjectId);

        return $this->moduleRepo->removeItem($itemId);
    }

    /**
     * Reorder activities within a module.
     *
     * @param int $moduleId
     * @param int[] $itemIds
     * @throws ResourceNotFoundException
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function reorderActivities(int $moduleId, array $itemIds, UserContext $actor): bool
    {
        $module = $this->moduleRepo->findModuleById($moduleId);
        if (!$module) {
            throw new ResourceNotFoundException("Module with ID {$moduleId} does not exist.");
        }

        $this->validateTeacherAccess($actor, $module->classSubjectId);

        $cleanIds = array_values(array_unique(array_filter(array_map('intval', $itemIds), fn($id) => $id > 0)));
        if (empty($cleanIds)) {
            throw new ValidationException(['items' => 'Item IDs list cannot be empty.']);
        }

        $existingItems = $this->moduleRepo->getItemsByModuleId($moduleId);
        $existingMap = [];
        foreach ($existingItems as $item) {
            $existingMap[$item->id] = true;
        }

        foreach ($cleanIds as $id) {
            if (!isset($existingMap[$id])) {
                throw new ValidationException(['items' => "Item ID {$id} does not belong to module ID {$moduleId}."]);
            }
        }

        return $this->moduleRepo->reorderItems($moduleId, $cleanIds);
    }

    /**
     * Get unassigned activities for a class subject that can be added to modules.
     *
     * @return array{documents: array, quizzes: array, assignments: array}
     */
    public function getAvailableActivitiesForSubject(int $classSubjectId, UserContext $actor): array
    {
        $this->validateTeacherAccess($actor, $classSubjectId);

        // Fetch all documents for this subject
        $allDocs = $this->contentRepo->findByClassSubject($classSubjectId);
        $docs = array_filter($allDocs, fn($d) => in_array($d->type, ['document', 'note']));

        // Fetch published quizzes
        $quizzes = $this->quizRepo->findByClassSubject($classSubjectId, publishedOnly: true);

        // Fetch assignments
        $assignments = $this->assignmentRepo->findByClassSubject($classSubjectId);

        // Fetch all currently assigned module items across all modules in this subject
        $modules = $this->moduleRepo->getModulesByClassSubject($classSubjectId, false);
        $moduleIds = array_map(fn($m) => $m->id, $modules);
        $itemsGrouped = $this->moduleRepo->getItemsByModuleIds($moduleIds);

        $assignedTuples = [];
        foreach ($itemsGrouped as $items) {
            foreach ($items as $item) {
                $assignedTuples["{$item->activityType}:{$item->activityId}"] = true;
            }
        }

        return [
            'documents' => array_map(function($doc) use ($assignedTuples) {
                return [
                    'id' => $doc->id,
                    'title' => $doc->title,
                    'type' => $doc->type,
                    'is_assigned' => isset($assignedTuples[ModuleItem::TYPE_DOCUMENT . ":{$doc->id}"]),
                ];
            }, $docs),
            'quizzes' => array_map(function($quiz) use ($assignedTuples) {
                return [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'is_assigned' => isset($assignedTuples[ModuleItem::TYPE_QUIZ . ":{$quiz->id}"]),
                ];
            }, $quizzes),
            'assignments' => array_map(function($ass) use ($assignedTuples) {
                return [
                    'id' => $ass->id,
                    'title' => $ass->title,
                    'is_assigned' => isset($assignedTuples[ModuleItem::TYPE_ASSIGNMENT . ":{$ass->id}"]),
                ];
            }, $assignments),
        ];
    }

    /**
     * Compute and build the full structured learning path and progress for a student.
     *
     * @return array{
     *     modules: Module[],
     *     course_progress_percent: float,
     *     is_course_completed: bool,
     *     total_required_items: int,
     *     total_completed_items: int,
     *     has_modules: bool
     * }
     */
    public function getLearningPathForStudent(int $classSubjectId, int $studentId, ?UserContext $actor = null): array
    {
        $modules = $this->moduleRepo->getModulesByClassSubject($classSubjectId, true);
        if (empty($modules)) {
            return [
                'modules' => [],
                'course_progress_percent' => 0.0,
                'is_course_completed' => false,
                'total_required_items' => 0,
                'total_completed_items' => 0,
                'has_modules' => false,
            ];
        }

        $moduleIds = array_map(fn(Module $m) => $m->id, $modules);
        $itemsGrouped = $this->moduleRepo->getItemsByModuleIds($moduleIds);

        $totalCourseRequired = 0;
        $totalCourseCompleted = 0;

        foreach ($modules as $module) {
            $items = $itemsGrouped[$module->id] ?? [];
            $modRequiredCount = 0;
            $modCompletedCount = 0;
            $hasAnyInProgress = false;
            $hasAnyUnlocked = false;

            foreach ($items as $item) {
                $this->populateItemMetadata($item);

                // Check Authoritative Learning Activity Progress
                $progress = $this->progressRepo->findByStudentAndActivity(
                    $studentId,
                    $item->activityType,
                    $item->activityId
                );

                if ($progress) {
                    $item->progressPercent = $progress->progressPercent;
                    $item->isCompleted = $progress->isCompleted();
                    $item->completedAt = $progress->completedAt;
                } else {
                    $item->progressPercent = 0.0;
                    $item->isCompleted = false;
                }

                // Check Prerequisite Unlocking
                $item->isUnlocked = $this->prereqService->isActivityUnlocked(
                    $studentId,
                    $item->activityType,
                    $item->activityId
                );

                if (!$item->isUnlocked) {
                    $prereqStatus = $this->prereqService->getPrerequisiteStatus(
                        $studentId,
                        $item->activityType,
                        $item->activityId
                    );
                    $item->unmetPrerequisites = $prereqStatus['unmet'] ?? [];
                    $item->statusState = 'locked';
                } elseif ($item->isCompleted) {
                    $item->statusState = 'completed';
                    $hasAnyUnlocked = true;
                } elseif ($item->progressPercent > 0.0) {
                    $item->statusState = 'in_progress';
                    $hasAnyInProgress = true;
                    $hasAnyUnlocked = true;
                } else {
                    $item->statusState = 'not_started';
                    $hasAnyUnlocked = true;
                }

                // Tally required items for module progression
                if ($item->isRequired) {
                    $modRequiredCount++;
                    if ($item->isCompleted) {
                        $modCompletedCount++;
                    }
                }
            }

            $module->items = $items;
            $module->totalItemsCount = count($items);
            $module->completedItemsCount = $modCompletedCount;

            // Derived Module Progress Calculation
            if ($modRequiredCount > 0) {
                $module->progressPercent = round(($modCompletedCount / $modRequiredCount) * 100, 1);
                $module->isCompleted = ($modCompletedCount === $modRequiredCount);
            } else {
                $module->progressPercent = 100.0;
                $module->isCompleted = true;
            }

            // Derive module badge state
            if ($module->isCompleted && $modRequiredCount > 0) {
                $module->computedStatus = 'Completed';
            } elseif ($hasAnyInProgress || $modCompletedCount > 0) {
                $module->computedStatus = 'In Progress';
            } elseif (!$hasAnyUnlocked && count($items) > 0) {
                $module->computedStatus = 'Locked';
            } else {
                $module->computedStatus = 'Not Started';
            }

            $totalCourseRequired += $modRequiredCount;
            $totalCourseCompleted += $modCompletedCount;
        }

        // Derived Course/Subject Progress Calculation
        $courseProgress = $totalCourseRequired > 0
            ? round(($totalCourseCompleted / $totalCourseRequired) * 100, 1)
            : 0.0;

        $isCourseCompleted = ($totalCourseRequired > 0 && $totalCourseCompleted >= $totalCourseRequired);

        if ($isCourseCompleted) {
            try {
                if ($this->badgeService === null) {
                    $this->badgeService = new BadgeService();
                }
                $this->badgeService->evaluateCourseCompletionBadge($studentId, $classSubjectId, $actor);
            } catch (\Throwable) {
                // Non-blocking: failure in badge evaluation must not break path rendering
            }
        }

        return [
            'modules' => $modules,
            'course_progress_percent' => $courseProgress,
            'is_course_completed' => $isCourseCompleted,
            'total_required_items' => $totalCourseRequired,
            'total_completed_items' => $totalCourseCompleted,
            'has_modules' => true,
        ];
    }

    /**
     * Generate cohort learning progress report for an authorized teacher and class subject.
     * Bounded query execution: does NOT loop query per student or per activity item.
     *
     * @return array{
     *     cohort_metrics: array{
     *         enrolled_student_count: int,
     *         not_started_count: int,
     *         in_progress_count: int,
     *         completed_count: int,
     *         average_progress_percentage: float
     *     },
     *     students: array<int, array{
     *         student_id: int,
     *         name: string,
     *         admission_number: string,
     *         email: string,
     *         progress_percent: float,
     *         completed_required_count: int,
     *         total_required_count: int,
     *         status: string,
     *         last_active_at: ?string
     *     }>,
     *     class_subject: ClassSubject,
     *     total_required_items: int,
     *     has_modules: bool
     * }
     * @throws AuthorizationException
     * @throws ResourceNotFoundException
     */
    public function getCohortProgressionReport(int $classSubjectId, UserContext $actor): array
    {
        $this->validateTeacherAccess($actor, $classSubjectId);

        $classSubject = $this->academicRepo->findClassSubjectById($classSubjectId);
        if (!$classSubject) {
            throw new ResourceNotFoundException('Class subject offering not found.');
        }

        // QUERY 1: Enrolled students
        $students = $this->enrollmentRepo->getEnrolledStudentsForClassSubject($classSubjectId);
        $studentIds = array_map(fn($s) => $s->id, $students);

        // QUERY 2: Published modules and module items
        $modules = $this->moduleRepo->getModulesByClassSubject($classSubjectId, true);
        $moduleIds = array_map(fn($m) => $m->id, $modules);
        $itemsGrouped = !empty($moduleIds) ? $this->moduleRepo->getItemsByModuleIds($moduleIds) : [];

        // Collect activity tuples
        $requiredActivityTuples = [];
        $allActivityTuples = [];
        $totalCourseRequired = 0;

        foreach ($modules as $module) {
            $items = $itemsGrouped[$module->id] ?? [];
            foreach ($items as $item) {
                $tuple = ['activity_type' => $item->activityType, 'activity_id' => $item->activityId];
                $allActivityTuples[] = $tuple;
                if ($item->isRequired) {
                    $requiredActivityTuples[] = $tuple;
                    $totalCourseRequired++;
                }
            }
        }

        // QUERY 3 & 4: Bulk progress map for cohort across all activities in published modules
        $progressMap = (!empty($studentIds) && !empty($allActivityTuples))
            ? $this->progressRepo->getProgressMapForCohort($studentIds, $allActivityTuples)
            : [];

        // Aggregate statistics in PHP
        $studentRows = [];
        $notStartedCount = 0;
        $inProgressCount = 0;
        $completedCount = 0;
        $sumProgressPercent = 0.0;

        foreach ($students as $student) {
            $completedRequiredCount = 0;
            $hasAnyProgress = false;
            $latestActiveAt = null;

            // Check required activities for completion
            foreach ($requiredActivityTuples as $req) {
                $key = "{$req['activity_type']}:{$req['activity_id']}";
                $prog = $progressMap[$student->id][$key] ?? null;
                if ($prog) {
                    if ($prog->isCompleted()) {
                        $completedRequiredCount++;
                    }
                    if ($prog->progressPercent > 0.0) {
                        $hasAnyProgress = true;
                    }
                    if ($prog->lastAccessedAt !== null) {
                        if ($latestActiveAt === null || $prog->lastAccessedAt > $latestActiveAt) {
                            $latestActiveAt = $prog->lastAccessedAt;
                        }
                    }
                }
            }

            // Check non-required activities for activity timestamps / in-progress detection
            if (isset($progressMap[$student->id])) {
                foreach ($progressMap[$student->id] as $prog) {
                    if ($prog->progressPercent > 0.0) {
                        $hasAnyProgress = true;
                    }
                    if ($prog->lastAccessedAt !== null) {
                        if ($latestActiveAt === null || $prog->lastAccessedAt > $latestActiveAt) {
                            $latestActiveAt = $prog->lastAccessedAt;
                        }
                    }
                }
            }

            $progressPercent = $totalCourseRequired > 0
                ? round(($completedRequiredCount / $totalCourseRequired) * 100, 1)
                : 0.0;

            // Determine Status: Completed, In Progress, Not Started
            if ($totalCourseRequired > 0 && $completedRequiredCount === $totalCourseRequired) {
                $status = 'Completed';
                $completedCount++;
            } elseif ($completedRequiredCount > 0 || $hasAnyProgress) {
                $status = 'In Progress';
                $inProgressCount++;
            } else {
                $status = 'Not Started';
                $notStartedCount++;
            }

            $sumProgressPercent += $progressPercent;

            $studentRows[] = [
                'student_id' => $student->id,
                'name' => $student->user?->name ?? "Student #{$student->id}",
                'admission_number' => $student->admissionNumber,
                'email' => $student->user?->email ?? '',
                'progress_percent' => $progressPercent,
                'completed_required_count' => $completedRequiredCount,
                'total_required_count' => $totalCourseRequired,
                'status' => $status,
                'last_active_at' => $latestActiveAt,
            ];
        }

        $enrolledCount = count($students);
        $avgProgress = $enrolledCount > 0 ? round($sumProgressPercent / $enrolledCount, 1) : 0.0;

        return [
            'cohort_metrics' => [
                'enrolled_student_count' => $enrolledCount,
                'not_started_count' => $notStartedCount,
                'in_progress_count' => $inProgressCount,
                'completed_count' => $completedCount,
                'average_progress_percentage' => $avgProgress,
            ],
            'students' => $studentRows,
            'class_subject' => $classSubject,
            'total_required_items' => $totalCourseRequired,
            'has_modules' => !empty($modules),
        ];
    }

    /**
     * Detailed individual student progression report for an authorized teacher.
     * Scoped strictly to the class subject offering and enrolled student.
     *
     * @throws AuthorizationException
     * @throws ResourceNotFoundException
     */
    public function getStudentProgressionDetail(int $classSubjectId, int $studentId, UserContext $actor): array
    {
        $this->validateTeacherAccess($actor, $classSubjectId);

        $classSubject = $this->academicRepo->findClassSubjectById($classSubjectId);
        if (!$classSubject) {
            throw new ResourceNotFoundException('Class subject offering not found.');
        }

        // Verify student exists
        $student = $this->studentRepo->findById($studentId);
        if (!$student) {
            throw new ResourceNotFoundException("Student with ID {$studentId} not found.");
        }

        // Verify enrollment in this class subject
        $enrolledStudents = $this->enrollmentRepo->getEnrolledStudentsForClassSubject($classSubjectId);
        $isEnrolled = false;
        foreach ($enrolledStudents as $s) {
            if ($s->id === $studentId) {
                $isEnrolled = true;
                break;
            }
        }
        if (!$isEnrolled) {
            throw new AuthorizationException("Student #{$studentId} is not enrolled in this class subject cohort.");
        }

        // Fetch student's learning path
        $learningPath = $this->getLearningPathForStudent($classSubjectId, $studentId);

        // Resume target
        $resumeTarget = $this->getStudentResumeTarget($classSubjectId, $studentId);

        return [
            'student' => $student,
            'class_subject' => $classSubject,
            'learning_path' => $learningPath,
            'resume_target' => $resumeTarget,
        ];
    }

    /**
     * Determine the optimal "Continue Learning" / "Resume" target for a student.
     * Finds the first incomplete, unlocked activity that has progress > 0%.
     * If none is in progress, finds the first incomplete, unlocked activity in sequential order.
     */
    public function getStudentResumeTarget(int $classSubjectId, int $studentId): ?array
    {
        $learningPath = $this->getLearningPathForStudent($classSubjectId, $studentId);
        if (empty($learningPath['modules']) || $learningPath['is_course_completed']) {
            return null;
        }

        $inProgressCandidate = null;
        $nextUnlockedCandidate = null;

        foreach ($learningPath['modules'] as $module) {
            foreach ($module->items as $item) {
                // Must be unlocked
                if (!$item->isUnlocked) {
                    continue;
                }

                // If already completed, skip
                if ($item->isCompleted) {
                    continue;
                }

                // Fetch detailed progress record for resume page/position
                $progress = $this->progressRepo->findByStudentAndActivity($studentId, $item->activityType, $item->activityId);

                // Check if in progress (> 0% or has progress record)
                if ($item->statusState === 'in_progress' || ($progress && $progress->progressPercent > 0.0)) {
                    if ($inProgressCandidate === null) {
                        $label = match ($item->itemSubtype) {
                            'pdf' => ($progress && $progress->lastPage)
                                ? "Resume Reading (Page {$progress->lastPage})"
                                : "Resume Reading",
                            'docx' => "Resume Reading",
                            'quiz' => "Continue Quiz",
                            'assignment' => "Continue Assignment",
                            default => "Resume Activity",
                        };

                        $inProgressCandidate = [
                            'type' => 'resume',
                            'module_id' => $module->id,
                            'module_title' => $module->title,
                            'item_id' => $item->id,
                            'activity_type' => $item->activityType,
                            'activity_id' => $item->activityId,
                            'title' => $item->title,
                            'subtype' => $item->itemSubtype,
                            'progress_percent' => $progress?->progressPercent ?? $item->progressPercent,
                            'last_page' => $progress?->lastPage,
                            'total_pages' => $progress?->totalPages,
                            'url' => $item->resumeUrl,
                            'label' => $label,
                            'last_accessed_at' => $progress?->lastAccessedAt,
                        ];

                        // An in-progress item takes top priority
                        return $inProgressCandidate;
                    }
                }

                // First unlocked incomplete item (not started)
                if ($nextUnlockedCandidate === null) {
                    $label = match ($item->itemSubtype) {
                        'pdf', 'docx', 'document', 'note' => "Start Reading",
                        'quiz' => "Take Quiz",
                        'assignment' => "View Assignment",
                        default => "Start Activity",
                    };

                    $nextUnlockedCandidate = [
                        'type' => 'next',
                        'module_id' => $module->id,
                        'module_title' => $module->title,
                        'item_id' => $item->id,
                        'activity_type' => $item->activityType,
                        'activity_id' => $item->activityId,
                        'title' => $item->title,
                        'subtype' => $item->itemSubtype,
                        'progress_percent' => 0.0,
                        'last_page' => null,
                        'total_pages' => null,
                        'url' => $item->resumeUrl,
                        'label' => $label,
                        'last_accessed_at' => null,
                    ];
                }
            }
        }

        return $inProgressCandidate ?? $nextUnlockedCandidate;
    }

    /**
     * Validate that an activity belongs to the given class subject.
     */
    private function validateActivitySubjectAssociation(string $type, int $id, int $classSubjectId): void
    {
        $actualSubjectId = match ($type) {
            ModuleItem::TYPE_DOCUMENT => $this->contentRepo->findById($id)?->classSubjectId,
            ModuleItem::TYPE_QUIZ => $this->quizRepo->findById($id, false)?->classSubjectId,
            ModuleItem::TYPE_ASSIGNMENT => $this->assignmentRepo->findById($id)?->classSubjectId,
            default => null,
        };

        if ($actualSubjectId === null) {
            throw new ResourceNotFoundException("Activity '{$type}' with ID {$id} not found.");
        }

        if ($actualSubjectId !== $classSubjectId) {
            throw new DomainRuleException(
                "Activity '{$type}' #{$id} belongs to class subject #{$actualSubjectId}, but this module belongs to class subject #{$classSubjectId}."
            );
        }
    }

    /**
     * Enrich a ModuleItem with title, subtype, and resume URL.
     */
    private function populateItemMetadata(ModuleItem $item): void
    {
        switch ($item->activityType) {
            case ModuleItem::TYPE_DOCUMENT:
                $doc = $this->contentRepo->findById($item->activityId);
                if ($doc) {
                    $item->title = $doc->title;
                    $item->description = $doc->description;
                    $item->entity = $doc;

                    if ($doc->fileId) {
                        $file = $this->fileRepo->findById($doc->fileId);
                        if ($file) {
                            $mime = strtolower((string)$file->mimeType);
                            if (str_contains($mime, 'pdf')) {
                                $item->itemSubtype = 'pdf';
                            } elseif (str_contains($mime, 'wordprocessingml') || str_contains($mime, 'docx')) {
                                $item->itemSubtype = 'docx';
                            } else {
                                $item->itemSubtype = 'file';
                            }
                        }
                    } else {
                        $item->itemSubtype = $doc->type;
                    }

                    $item->resumeUrl = "/student/content/{$doc->id}";
                } else {
                    $item->title = "Document #{$item->activityId}";
                    $item->resumeUrl = "/student/content/{$item->activityId}";
                }
                break;

            case ModuleItem::TYPE_QUIZ:
                $quiz = $this->quizRepo->findById($item->activityId, false);
                if ($quiz) {
                    $item->title = $quiz->title;
                    $item->description = $quiz->instructions;
                    $item->itemSubtype = 'quiz';
                    $item->entity = $quiz;
                    $item->resumeUrl = "/student/quizzes/{$quiz->id}";
                } else {
                    $item->title = "Quiz #{$item->activityId}";
                    $item->resumeUrl = "/student/quizzes/{$item->activityId}";
                }
                break;

            case ModuleItem::TYPE_ASSIGNMENT:
                $assignment = $this->assignmentRepo->findById($item->activityId);
                if ($assignment) {
                    $item->title = $assignment->title;
                    $item->description = $assignment->instructions;
                    $item->itemSubtype = 'assignment';
                    $item->entity = $assignment;
                    $item->resumeUrl = "/student/assignments/{$assignment->id}";
                } else {
                    $item->title = "Assignment #{$item->activityId}";
                    $item->resumeUrl = "/student/assignments/{$item->activityId}";
                }
                break;
        }
    }
}
