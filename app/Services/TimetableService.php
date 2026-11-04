<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\TimetableSlot;
use App\Policies\TimetablePolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\TimetableRepository;
use App\Repositories\TimetableStructureRepository;
use App\Utils\PeriodSchedule;
use PDO;

/**
 * Timetable Application Service
 * Orchestrates weekly timetable creation, update, deletion, conflict validation, and role-scoped retrieval.
 */
class TimetableService
{
    private TimetableRepository $timetableRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;
    private TeacherRepository $teacherRepo;
    private StudentRepository $studentRepo;
    private ParentRepository $parentRepo;
    private ?AuditService $auditService;
    private TimetableStructureRepository $structureRepo;
    private PDO $db;

    public function __construct(
        ?TimetableRepository $timetableRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?EnrollmentRepository $enrollmentRepo = null,
        ?TeacherRepository $teacherRepo = null,
        ?StudentRepository $studentRepo = null,
        ?ParentRepository $parentRepo = null,
        ?AuditService $auditService = null,
        ?PDO $db = null,
        ?TimetableStructureRepository $structureRepo = null
    ) {
        $this->db = $db ?? Database::getConnection();
        $this->timetableRepo = $timetableRepo ?? new TimetableRepository($this->db);
        $this->academicRepo = $academicRepo ?? new AcademicRepository($this->db);
        $this->enrollmentRepo = $enrollmentRepo ?? new EnrollmentRepository($this->db);
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository($this->db);
        $this->studentRepo = $studentRepo ?? new StudentRepository($this->db);
        $this->parentRepo = $parentRepo ?? new ParentRepository($this->db);
        $this->auditService = $auditService ?? new AuditService($this->db);
        $this->structureRepo = $structureRepo ?? new TimetableStructureRepository($this->db);
    }

    /**
     * Get class timetable schedule organized by day of week.
     *
     * @return array{class: SchoolClass, term: Term, slots: array<int, TimetableSlot>, grid: array<string, array<int, TimetableSlot>>}
     */
    public function getClassTimetable(int $classId, ?int $termId = null, ?UserContext $actor = null): array
    {
        if ($actor !== null && !TimetablePolicy::canViewClassTimetable($actor, $classId, $this->academicRepo, $this->enrollmentRepo, $this->parentRepo)) {
            throw new AuthorizationException('You are not authorized to view the timetable for this class.');
        }

        $class = $this->academicRepo->findClassById($classId);
        if (!$class) {
            throw new ResourceNotFoundException("Class #{$classId} not found.");
        }

        $term = $this->resolveTerm($termId);
        $slots = $this->timetableRepo->findByClass($classId, $term->id);

        return [
            'class' => $class,
            'term' => $term,
            'slots' => $slots,
            'grid' => $this->organizeGrid($slots),
        ];
    }

    /**
     * Get teacher personal teaching timetable schedule.
     *
     * @return array{teacher: Teacher, term: Term, slots: array<int, TimetableSlot>, grid: array<string, array<int, TimetableSlot>>}
     */
    public function getTeacherTimetable(int $teacherId, ?int $termId = null, ?UserContext $actor = null): array
    {
        if ($actor !== null && !TimetablePolicy::canViewTeacherTimetable($actor, $teacherId, $this->teacherRepo)) {
            throw new AuthorizationException('You are not authorized to view this teacher schedule.');
        }

        $teacher = $this->teacherRepo->findTeacherById($teacherId);
        if (!$teacher) {
            throw new ResourceNotFoundException("Teacher #{$teacherId} not found.");
        }

        $term = $this->resolveTerm($termId);
        $slots = $this->timetableRepo->findByTeacher($teacherId, $term->id);

        return [
            'teacher' => $teacher,
            'term' => $term,
            'slots' => $slots,
            'grid' => $this->organizeGrid($slots),
        ];
    }

    /**
     * Get student personalized learning timetable schedule.
     *
     * @return array{student: Student, class: ?SchoolClass, term: Term, slots: array<int, TimetableSlot>, grid: array<string, array<int, TimetableSlot>>}
     */
    public function getStudentTimetable(int $studentId, ?int $termId = null, ?UserContext $actor = null): array
    {
        if ($actor !== null && !TimetablePolicy::canViewStudentTimetable($actor, $studentId, $this->studentRepo, $this->parentRepo)) {
            throw new AuthorizationException('You are not authorized to view this student timetable.');
        }

        $student = $this->studentRepo->findById($studentId);
        if (!$student) {
            throw new ResourceNotFoundException("Student #{$studentId} not found.");
        }

        $term = $this->resolveTerm($termId);

        $slots = [];
        $class = null;

        if ($student->currentClassId !== null) {
            $class = $this->academicRepo->findClassById($student->currentClassId);

            // Check student subject-level enrollments for the active session
            $subjectEnrollments = $this->enrollmentRepo->getStudentSubjectEnrollments($studentId, (int)$term->sessionId);

            if (!empty($subjectEnrollments)) {
                $enrolledCsIds = array_map(fn($se) => (int)$se->classSubjectId, $subjectEnrollments);
                $slots = $this->timetableRepo->findByClassSubjects($enrolledCsIds, $term->id);
            } else {
                // Fallback to whole class schedule if no granular subject enrollments exist
                $slots = $this->timetableRepo->findByClass($student->currentClassId, $term->id);
            }
        }

        return [
            'student' => $student,
            'class'   => $class,
            'term'    => $term,
            'slots'   => $slots,
            'grid'    => $this->organizeGrid($slots),
        ];
    }

    /**
     * Get parent child timetable schedule.
     */
    public function getParentChildTimetable(int $studentId, UserContext $actor, ?int $termId = null): array
    {
        if (!TimetablePolicy::canViewStudentTimetable($actor, $studentId, $this->studentRepo, $this->parentRepo)) {
            throw new AuthorizationException('You are not authorized to view this child timetable.');
        }

        return $this->getStudentTimetable($studentId, $termId, $actor);
    }

    /**
     * Create a new timetable slot after verifying authorization, time validity, cross-session invariant, and conflicts.
     *
     * @param array{term_id: int, class_id?: ?int, class_subject_id?: ?int, structure_period_id?: ?int, department?: ?string, custom_title?: ?string, slot_type?: ?string, day_of_week: string, start_time: string, end_time: string, room?: ?string} $data
     */
    public function createSlot(array $data, UserContext $actor): TimetableSlot
    {
        if (!TimetablePolicy::canManage($actor)) {
            throw new AuthorizationException('Only administrators can create timetable slots.');
        }

        $errors = $this->validateSlotData($data);
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $termId = (int)$data['term_id'];
        $classSubjectId = isset($data['class_subject_id']) && $data['class_subject_id'] !== '' && (int)$data['class_subject_id'] > 0
            ? (int)$data['class_subject_id']
            : null;
        $classId = isset($data['class_id']) && $data['class_id'] !== '' && (int)$data['class_id'] > 0
            ? (int)$data['class_id']
            : null;
        $structurePeriodId = isset($data['structure_period_id']) && $data['structure_period_id'] !== ''
            ? (int)$data['structure_period_id']
            : null;
        $department = isset($data['department']) && trim((string)$data['department']) !== ''
            ? trim((string)$data['department'])
            : null;
        $customTitle = isset($data['custom_title']) && trim((string)$data['custom_title']) !== ''
            ? trim((string)$data['custom_title'])
            : null;
        $slotType = (string)($data['slot_type'] ?? 'instructional');

        $dayOfWeek = strtolower(trim((string)$data['day_of_week']));
        $startTime = $this->normalizeTime((string)$data['start_time']);
        $endTime = $this->normalizeTime((string)$data['end_time']);
        $room = isset($data['room']) && trim((string)$data['room']) !== '' ? trim((string)$data['room']) : null;

        // Verify term exists
        $term = $this->academicRepo->findTermById($termId);
        if (!$term) {
            throw new ResourceNotFoundException("Term #{$termId} not found.");
        }

        // Verify class_subject if provided
        if ($classSubjectId !== null) {
            $classSubject = $this->academicRepo->findClassSubjectById($classSubjectId);
            if (!$classSubject) {
                throw new ResourceNotFoundException("Class Subject #{$classSubjectId} not found.");
            }

            // Cross-Session Invariant: term.session_id === class_subject.session_id
            if ((int)$term->sessionId !== (int)$classSubject->sessionId) {
                throw new DomainRuleException("Cross-Session Violation: Term session (#{$term->sessionId}) does not match Class Subject session (#{$classSubject->sessionId}).");
            }

            if ($classId === null) {
                $classId = (int)$classSubject->classId;
            }
        } elseif ($classId !== null) {
            $classObj = $this->academicRepo->findClassById($classId);
            if (!$classObj) {
                throw new ResourceNotFoundException("Class #{$classId} not found.");
            }
        }

        // Conflict check
        $conflicts = $this->validateSlotConflicts($termId, $classSubjectId, $dayOfWeek, $startTime, $endTime, $room, null, $classId, $department);
        if (!empty($conflicts)) {
            $conflictErrors = [];
            foreach ($conflicts as $c) {
                $conflictErrors['schedule'][] = $c['message'];
            }
            throw new ValidationException($conflictErrors, 'Schedule conflict detected: ' . implode(' ', $conflictErrors['schedule']));
        }

        $this->db->beginTransaction();
        try {
            $slot = $this->timetableRepo->create([
                'term_id' => $termId,
                'class_id' => $classId,
                'class_subject_id' => $classSubjectId,
                'structure_period_id' => $structurePeriodId,
                'department' => $department,
                'custom_title' => $customTitle,
                'slot_type' => $slotType,
                'day_of_week' => $dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'room' => $room,
            ]);

            $this->auditService?->log(
                actorUserId: $actor->id,
                action: 'TIMETABLE_SLOT_CREATED',
                entityType: 'timetable_slots',
                entityId: $slot->id,
                before: null,
                after: $slot->toArray(),
                metadata: ['class_id' => $classId, 'class_subject_id' => $classSubjectId, 'term_id' => $termId]
            );

            $this->db->commit();
            return $slot;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Update an existing timetable slot.
     *
     * @param array{term_id?: int, class_id?: ?int, class_subject_id?: ?int, structure_period_id?: ?int, department?: ?string, custom_title?: ?string, slot_type?: ?string, day_of_week?: string, start_time?: string, end_time?: string, room?: ?string} $data
     */
    public function updateSlot(int $slotId, array $data, UserContext $actor): TimetableSlot
    {
        if (!TimetablePolicy::canManage($actor)) {
            throw new AuthorizationException('Only administrators can update timetable slots.');
        }

        $existing = $this->timetableRepo->findById($slotId);
        if (!$existing) {
            throw new ResourceNotFoundException("Timetable slot #{$slotId} not found.");
        }

        $mergedData = [
            'term_id' => isset($data['term_id']) ? (int)$data['term_id'] : $existing->termId,
            'class_id' => array_key_exists('class_id', $data) ? ($data['class_id'] !== '' && $data['class_id'] !== null ? (int)$data['class_id'] : null) : $existing->classId,
            'class_subject_id' => array_key_exists('class_subject_id', $data) ? ($data['class_subject_id'] !== '' && $data['class_subject_id'] !== null ? (int)$data['class_subject_id'] : null) : $existing->classSubjectId,
            'structure_period_id' => array_key_exists('structure_period_id', $data) ? ($data['structure_period_id'] !== '' && $data['structure_period_id'] !== null ? (int)$data['structure_period_id'] : null) : $existing->structurePeriodId,
            'department' => array_key_exists('department', $data) ? (trim((string)$data['department']) !== '' ? trim((string)$data['department']) : null) : $existing->department,
            'custom_title' => array_key_exists('custom_title', $data) ? (trim((string)$data['custom_title']) !== '' ? trim((string)$data['custom_title']) : null) : $existing->customTitle,
            'slot_type' => array_key_exists('slot_type', $data) ? (string)$data['slot_type'] : $existing->slotType,
            'day_of_week' => isset($data['day_of_week']) ? (string)$data['day_of_week'] : $existing->dayOfWeek,
            'start_time' => isset($data['start_time']) ? (string)$data['start_time'] : $existing->startTime,
            'end_time' => isset($data['end_time']) ? (string)$data['end_time'] : $existing->endTime,
            'room' => array_key_exists('room', $data) ? $data['room'] : $existing->room,
        ];

        $errors = $this->validateSlotData($mergedData);
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $termId = (int)$mergedData['term_id'];
        $classSubjectId = $mergedData['class_subject_id'];
        $classId = $mergedData['class_id'];
        $dayOfWeek = strtolower(trim((string)$mergedData['day_of_week']));
        $startTime = $this->normalizeTime((string)$mergedData['start_time']);
        $endTime = $this->normalizeTime((string)$mergedData['end_time']);
        $room = isset($mergedData['room']) && trim((string)$mergedData['room']) !== '' ? trim((string)$mergedData['room']) : null;

        $term = $this->academicRepo->findTermById($termId);
        if (!$term) {
            throw new ResourceNotFoundException("Term #{$termId} not found.");
        }

        if ($classSubjectId !== null) {
            $classSubject = $this->academicRepo->findClassSubjectById($classSubjectId);
            if (!$classSubject) {
                throw new ResourceNotFoundException("Class Subject #{$classSubjectId} not found.");
            }

            // Cross-session invariant
            if ((int)$term->sessionId !== (int)$classSubject->sessionId) {
                throw new DomainRuleException("Cross-Session Violation: Term session (#{$term->sessionId}) does not match Class Subject session (#{$classSubject->sessionId}).");
            }

            if ($classId === null) {
                $classId = (int)$classSubject->classId;
            }
        }

        // Conflict check excluding current slot
        $conflicts = $this->validateSlotConflicts($termId, $classSubjectId, $dayOfWeek, $startTime, $endTime, $room, $slotId, $classId, $mergedData['department']);
        if (!empty($conflicts)) {
            $conflictErrors = [];
            foreach ($conflicts as $c) {
                $conflictErrors['schedule'][] = $c['message'];
            }
            throw new ValidationException($conflictErrors, 'Schedule conflict detected: ' . implode(' ', $conflictErrors['schedule']));
        }

        $this->db->beginTransaction();
        try {
            $updated = $this->timetableRepo->update($slotId, [
                'term_id' => $termId,
                'class_id' => $classId,
                'class_subject_id' => $classSubjectId,
                'structure_period_id' => $mergedData['structure_period_id'],
                'department' => $mergedData['department'],
                'custom_title' => $mergedData['custom_title'],
                'slot_type' => $mergedData['slot_type'],
                'day_of_week' => $dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'room' => $room,
            ]);

            $this->auditService?->log(
                actorUserId: $actor->id,
                action: 'TIMETABLE_SLOT_UPDATED',
                entityType: 'timetable_slots',
                entityId: $slotId,
                before: $existing->toArray(),
                after: $updated->toArray(),
                metadata: ['class_id' => $classId, 'class_subject_id' => $classSubjectId, 'term_id' => $termId]
            );

            $this->db->commit();
            return $updated;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Delete a timetable slot.
     */
    public function deleteSlot(int $slotId, UserContext $actor): bool
    {
        if (!TimetablePolicy::canManage($actor)) {
            throw new AuthorizationException('Only administrators can delete timetable slots.');
        }

        $existing = $this->timetableRepo->findById($slotId);
        if (!$existing) {
            throw new ResourceNotFoundException("Timetable slot #{$slotId} not found.");
        }

        $this->db->beginTransaction();
        try {
            $deleted = $this->timetableRepo->delete($slotId);

            if ($deleted) {
                $this->auditService?->log(
                    actorUserId: $actor->id,
                    action: 'TIMETABLE_SLOT_DELETED',
                    entityType: 'timetable_slots',
                    entityId: $slotId,
                    before: $existing->toArray(),
                    after: null,
                    metadata: ['class_subject_id' => $existing->classSubjectId, 'term_id' => $existing->termId]
                );
            }

            $this->db->commit();
            return $deleted;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Clone timetable slots from one term to another within the same academic session.
     *
     * @return array{cloned_count: int, skipped_count: int, conflicts: array<int, string>}
     */
    public function cloneTermTimetable(
        int $sourceTermId,
        int $targetTermId,
        ?int $classId = null,
        bool $overwrite = false,
        ?UserContext $actor = null
    ): array {
        if ($actor !== null && !TimetablePolicy::canManage($actor)) {
            throw new AuthorizationException('Only administrators can clone timetable schedules.');
        }

        if ($sourceTermId === $targetTermId) {
            throw new ValidationException(['target_term_id' => 'Source and target terms cannot be the same.']);
        }

        $sourceTerm = $this->academicRepo->findTermById($sourceTermId);
        if (!$sourceTerm) {
            throw new ResourceNotFoundException("Source Term #{$sourceTermId} not found.");
        }

        $targetTerm = $this->academicRepo->findTermById($targetTermId);
        if (!$targetTerm) {
            throw new ResourceNotFoundException("Target Term #{$targetTermId} not found.");
        }

        // Cross-Session Invariant: Term cloning is strictly within the same academic session
        // because class_subjects are allocated per academic session.
        if ((int)$sourceTerm->sessionId !== (int)$targetTerm->sessionId) {
            throw new DomainRuleException("Cross-Session Violation: Cannot clone timetable across different academic sessions (Session #{$sourceTerm->sessionId} vs Session #{$targetTerm->sessionId}). Subjects are allocated per session.");
        }

        // Fetch source slots
        $sourceSlots = $classId !== null
            ? $this->timetableRepo->findByClass($classId, $sourceTermId)
            : $this->timetableRepo->findByTerm($sourceTermId);

        if (empty($sourceSlots)) {
            throw new ValidationException(['source_term_id' => 'No timetable slots found in the selected source term to clone.']);
        }

        $this->db->beginTransaction();
        try {
            if ($overwrite) {
                if ($classId !== null) {
                    $this->timetableRepo->deleteByClassAndTerm($classId, $targetTermId);
                } else {
                    $stmt = $this->db->prepare("DELETE FROM timetable_slots WHERE term_id = :term_id");
                    $stmt->execute([':term_id' => $targetTermId]);
                }
            }

            $clonedCount = 0;
            $skippedCount = 0;
            $conflictErrors = [];
            $batchData = [];

            foreach ($sourceSlots as $slot) {
                // Check if slot collides in target term
                $conflicts = $this->validateSlotConflicts(
                    $targetTermId,
                    $slot->classSubjectId,
                    $slot->dayOfWeek,
                    $slot->startTime,
                    $slot->endTime,
                    $slot->room
                );

                if (!empty($conflicts)) {
                    $conflictErrors[] = "{$slot->getDayLabel()} {$slot->getFormattedTimeRange()} ({$slot->classSubject?->subjectName}): " . $conflicts[0]['message'];
                    $skippedCount++;
                    continue;
                }

                $batchData[] = [
                    'term_id' => $targetTermId,
                    'class_subject_id' => $slot->classSubjectId,
                    'day_of_week' => $slot->dayOfWeek,
                    'start_time' => $slot->startTime,
                    'end_time' => $slot->endTime,
                    'room' => $slot->room,
                ];
                $clonedCount++;
            }

            if (!empty($batchData)) {
                $this->timetableRepo->batchCreate($batchData);
            }

            if ($actor !== null) {
                $this->auditService?->log(
                    actorUserId: $actor->id,
                    action: 'TIMETABLE_TERM_CLONED',
                    entityType: 'timetable_slots',
                    entityId: $targetTermId,
                    before: null,
                    after: ['source_term_id' => $sourceTermId, 'target_term_id' => $targetTermId, 'cloned_count' => $clonedCount],
                    metadata: ['class_id' => $classId, 'overwrite' => $overwrite]
                );
            }

            $this->db->commit();

            return [
                'cloned_count' => $clonedCount,
                'skipped_count' => $skippedCount,
                'conflicts' => $conflictErrors,
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Import timetable schedule from CSV spreadsheet content.
     * Implements strict two-pass pre-flight validation:
     * - Pass 1: Resolves classes, subjects, days, periods, and syntax.
     * - Pass 2: In-memory self-conflict detection & cross-school collision checking.
     *
     * @param array<string, int> $columnMapping Optional explicit column index overrides from UI mapping.
     * @return array{imported_count: int, classes_count: int}
     * @throws ValidationException on any syntax, resolution, or scheduling conflict.
     */
    public function importFromCsv(
        string $csvContent,
        int $termId,
        UserContext $actor,
        ?int $defaultClassId = null,
        bool $overwrite = false,
        array $columnMapping = []
    ): array
    {
        if (!TimetablePolicy::canManage($actor)) {
            throw new AuthorizationException('Only administrators can import timetable schedules.');
        }

        $term = $this->academicRepo->findTermById($termId);
        if (!$term) {
            throw new ResourceNotFoundException("Academic Term #{$termId} not found.");
        }

        $defaultClass = $defaultClassId ? $this->academicRepo->findClassById($defaultClassId) : null;

        // 1. Clean UTF-8 BOM if present
        $bom = pack('H*', 'EFBBBF');
        $csvContent = preg_replace("/^{$bom}/", '', trim($csvContent));
        if (empty($csvContent)) {
            throw new ValidationException(['file' => 'The uploaded CSV file is empty.']);
        }

        // 2. Parse CSV rows
        $lines = preg_split("/\r\n|\n|\r/", $csvContent);
        if (empty($lines)) {
            throw new ValidationException(['file' => 'No readable lines found in the CSV file.']);
        }

        $headerRow = str_getcsv(array_shift($lines));
        if (empty($headerRow) || count(array_filter($headerRow)) === 0) {
            throw new ValidationException(['file' => 'CSV file is missing a valid header row.']);
        }

        // Normalize header names
        $headerMap = [];
        foreach ($headerRow as $idx => $colName) {
            $clean = strtolower(trim((string)$colName));
            $clean = str_replace([' ', '-', '_'], '', $clean);
            if (in_array($clean, ['class', 'classname', 'classarm', 'cohort'], true)) {
                $headerMap['class'] = $idx;
            } elseif (in_array($clean, ['day', 'dayofweek', 'weekday'], true)) {
                $headerMap['day'] = $idx;
            } elseif (in_array($clean, ['period', 'periodnumber', 'periodno', 'slot'], true)) {
                $headerMap['period'] = $idx;
            } elseif (in_array($clean, ['subject', 'subjectcode', 'course', 'code'], true)) {
                $headerMap['subject'] = $idx;
            } elseif (in_array($clean, ['dept', 'department', 'stream', 'electivegroup'], true)) {
                $headerMap['department'] = $idx;
            } elseif (in_array($clean, ['room', 'classroom', 'hall', 'lab', 'venue'], true)) {
                $headerMap['room'] = $idx;
            } elseif (in_array($clean, ['type', 'slottype', 'activitytype'], true)) {
                $headerMap['slot_type'] = $idx;
            } elseif (in_array($clean, ['title', 'customtitle', 'activity', 'eventname', 'customname'], true)) {
                $headerMap['custom_title'] = $idx;
            } elseif (in_array($clean, ['starttime', 'start'], true)) {
                $headerMap['start_time'] = $idx;
            } elseif (in_array($clean, ['endtime', 'end'], true)) {
                $headerMap['end_time'] = $idx;
            }
        }

        // Apply explicit column mapping overrides if provided
        foreach ($columnMapping as $mapTarget => $mapIdx) {
            if ($mapIdx !== null && $mapIdx !== '' && (int)$mapIdx >= 0) {
                $headerMap[$mapTarget] = (int)$mapIdx;
            }
        }

        if (!isset($headerMap['class']) && !$defaultClass) {
            throw new ValidationException([
                'file' => "Required CSV headers missing. Ensure your CSV contains: 'class_name', 'day_of_week', and 'subject_code'. Found: " . implode(', ', array_filter($headerRow))
            ]);
        }

        if (!isset($headerMap['day'])) {
            throw new ValidationException([
                'file' => "Required CSV headers missing. Ensure your CSV contains: 'day_of_week'. Found: " . implode(', ', array_filter($headerRow))
            ]);
        }

        if (!isset($headerMap['subject']) && !isset($headerMap['custom_title'])) {
            throw new ValidationException([
                'file' => "Required CSV headers missing. Ensure your CSV contains: 'subject_code' or 'custom_title'. Found: " . implode(', ', array_filter($headerRow))
            ]);
        }

        if (!isset($headerMap['period']) && (!isset($headerMap['start_time']) || !isset($headerMap['end_time']))) {
            throw new ValidationException([
                'file' => "Time headers missing. The CSV must have either 'period_number' or 'start_time' and 'end_time'."
            ]);
        }

        // Preload classes and class_subjects for target session
        $classes = $this->academicRepo->getAllClasses();
        $classesByName = [];
        foreach ($classes as $c) {
            $classesByName[strtolower(trim($c->name))] = $c;
        }

        $allClassSubjects = $this->academicRepo->getClassSubjectsBySession((int)$term->sessionId);
        $csLookup = [];
        foreach ($allClassSubjects as $cs) {
            $codeKey = strtolower(trim((string)$cs->subjectCode));
            $csLookup["{$cs->classId}_{$codeKey}"] = $cs;
        }

        $rowErrors = [];
        $preparedSlots = [];
        $classesAffected = [];
        $inMemorySlots = [];

        $rowNum = 1;
        foreach ($lines as $line) {
            $rowNum++;
            if (trim($line) === '') {
                continue;
            }

            $row = str_getcsv($line);
            if (empty($row) || count(array_filter($row)) === 0) {
                continue;
            }

            $className = '';
            if (isset($headerMap['class']) && isset($row[$headerMap['class']])) {
                $className = trim((string)$row[$headerMap['class']]);
            }
            if ($className === '' && $defaultClass) {
                $classObj = $defaultClass;
                $className = $defaultClass->name;
            } else {
                $classObj = $classesByName[strtolower($className)] ?? null;
            }

            $dayInput = isset($headerMap['day']) ? trim((string)($row[$headerMap['day']] ?? '')) : '';
            $subjectInput = isset($headerMap['subject']) ? trim((string)($row[$headerMap['subject']] ?? '')) : '';
            $deptInput = isset($headerMap['department']) && isset($row[$headerMap['department']]) && trim((string)$row[$headerMap['department']]) !== ''
                ? trim((string)$row[$headerMap['department']])
                : null;
            $slotTypeInput = isset($headerMap['slot_type']) && isset($row[$headerMap['slot_type']]) && trim((string)$row[$headerMap['slot_type']]) !== ''
                ? strtolower(trim((string)$row[$headerMap['slot_type']]))
                : 'instructional';
            $customTitleInput = isset($headerMap['custom_title']) && isset($row[$headerMap['custom_title']]) && trim((string)$row[$headerMap['custom_title']]) !== ''
                ? trim((string)$row[$headerMap['custom_title']])
                : null;
            $roomInput = isset($headerMap['room']) ? trim((string)($row[$headerMap['room']] ?? '')) : null;

            $isActivity = ($slotTypeInput !== 'instructional') || ($customTitleInput !== null && $customTitleInput !== '');

            if ($className === '' || $dayInput === '' || (!$isActivity && $subjectInput === '')) {
                $rowErrors[] = "Row {$rowNum}: Class, Day, and Subject Code (or Activity Title) cannot be blank.";
                continue;
            }

            // Resolve Class
            if (!$classObj) {
                $rowErrors[] = "Row {$rowNum}: Class '{$className}' does not exist in the school system.";
                continue;
            }

            // Resolve Subject if instructional
            $classSubject = null;
            if ($subjectInput !== '') {
                $lookupKey = "{$classObj->id}_" . strtolower($subjectInput);
                $classSubject = $csLookup[$lookupKey] ?? null;
                if (!$classSubject) {
                    $rowErrors[] = "Row {$rowNum}: Subject code '{$subjectInput}' is not allocated to class '{$classObj->name}' in the active session.";
                    continue;
                }
            }

            // Resolve Day
            $dayKey = PeriodSchedule::normalizeDay($dayInput);
            if (!$dayKey) {
                $rowErrors[] = "Row {$rowNum}: Invalid day of week '{$dayInput}'. Expected Monday to Friday.";
                continue;
            }

            // Resolve Structure and Period Times
            $startTime = null;
            $endTime = null;
            $structurePeriodId = null;

            if (isset($headerMap['period'])) {
                $periodNum = (int)($row[$headerMap['period']] ?? 0);

                // Find structure for this class
                $structure = $this->structureRepo->findByClassId($classObj->id)
                    ?? $this->structureRepo->findByStage($classObj->stage ?? 'junior_secondary')
                    ?? $this->structureRepo->findDefault();

                $periodObj = null;
                if ($structure) {
                    $p = $structure->getPeriodByNumber($periodNum);
                    if ($p) {
                        $periodObj = [
                            'period' => $p->periodNumber,
                            'name' => $p->label,
                            'start' => substr($p->startTime, 0, 5),
                            'end' => substr($p->endTime, 0, 5),
                            'is_break' => $p->isBreak(),
                            'structure_period_id' => $p->id,
                        ];
                    }
                } else {
                    $periodObj = PeriodSchedule::getPeriodByNumber($periodNum);
                }

                if (!$periodObj || !empty($periodObj['is_break'])) {
                    $maxP = $structure ? $structure->getMaxPeriodNumber() : 9;
                    $rowErrors[] = "Row {$rowNum}: Invalid period number '{$periodNum}'. Valid instructional periods are 1 through {$maxP}.";
                    continue;
                }

                $startTime = $periodObj['start'];
                $endTime = $periodObj['end'];
                $structurePeriodId = $periodObj['structure_period_id'] ?? null;
            } else {
                $startTime = trim((string)($row[$headerMap['start_time']] ?? ''));
                $endTime = trim((string)($row[$headerMap['end_time']] ?? ''));
                if (!$this->isValidTimeFormat($startTime) || !$this->isValidTimeFormat($endTime)) {
                    $rowErrors[] = "Row {$rowNum}: Invalid time format. Expected HH:MM or HH:MM:SS.";
                    continue;
                }
                $startTime = $this->normalizeTime($startTime);
                $endTime = $this->normalizeTime($endTime);
                if (strtotime($startTime) >= strtotime($endTime)) {
                    $rowErrors[] = "Row {$rowNum}: Start time must strictly precede end time.";
                    continue;
                }
            }

            // In-Memory collision check (Allow concurrent elective splits if departments differ)
            $startSec = strtotime($startTime);
            $endSec = strtotime($endTime);

            $selfCollision = false;
            foreach ($inMemorySlots as $mem) {
                if ($mem['day'] === $dayKey) {
                    $mStart = strtotime($mem['start']);
                    $mEnd = strtotime($mem['end']);

                    if ($startSec < $mEnd && $endSec > $mStart) {
                        if ($mem['class_id'] === $classObj->id) {
                            $memDept = $mem['department'] ?? null;
                            $isAllowedSplit = ($deptInput !== null && $memDept !== null && strcasecmp($deptInput, $memDept) !== 0);

                            if (!$isAllowedSplit) {
                                $rowErrors[] = "Row {$rowNum}: Collision with Row {$mem['row']} — Class '{$classObj->name}' scheduled twice on {$dayInput} ({$startTime}–{$endTime}).";
                                $selfCollision = true;
                                break;
                            }
                        }
                        if ($classSubject && $classSubject->teacherId && $mem['teacher_id'] === $classSubject->teacherId) {
                            $rowErrors[] = "Row {$rowNum}: Collision with Row {$mem['row']} — Teacher '{$classSubject->teacherName}' double-booked on {$dayInput} ({$startTime}–{$endTime}).";
                            $selfCollision = true;
                            break;
                        }
                        if ($roomInput && $mem['room'] && strcasecmp($roomInput, $mem['room']) === 0) {
                            $rowErrors[] = "Row {$rowNum}: Collision with Row {$mem['row']} — Room '{$roomInput}' allocated twice on {$dayInput} ({$startTime}–{$endTime}).";
                            $selfCollision = true;
                            break;
                        }
                    }
                }
            }

            if ($selfCollision) {
                continue;
            }

            // Database collision check
            $dbConflicts = $this->validateSlotConflicts(
                termId: $termId,
                classSubjectId: $classSubject?->id,
                dayOfWeek: $dayKey,
                startTime: $startTime,
                endTime: $endTime,
                room: $roomInput,
                excludeSlotId: null,
                classId: $classObj->id,
                department: $deptInput
            );

            if (!empty($dbConflicts)) {
                if ($overwrite) {
                    // When overwrite is requested, ignore collisions with the same class's existing slots
                    $dbConflicts = array_values(array_filter($dbConflicts, fn($c) => ($c['type'] ?? '') !== 'class'));
                }
                if (!empty($dbConflicts)) {
                    $rowErrors[] = "Row {$rowNum}: Database Conflict — " . $dbConflicts[0]['message'];
                    continue;
                }
            }

            $inMemorySlots[] = [
                'row' => $rowNum,
                'class_id' => $classObj->id,
                'teacher_id' => $classSubject?->teacherId,
                'department' => $deptInput,
                'day' => $dayKey,
                'start' => $startTime,
                'end' => $endTime,
                'room' => $roomInput,
            ];

            $preparedSlots[] = [
                'term_id' => $termId,
                'class_id' => $classObj->id,
                'class_subject_id' => $classSubject?->id,
                'structure_period_id' => $structurePeriodId,
                'department' => $deptInput,
                'custom_title' => $customTitleInput,
                'slot_type' => $slotTypeInput,
                'day_of_week' => $dayKey,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'room' => $roomInput !== '' ? $roomInput : null,
            ];

            $classesAffected[$classObj->id] = true;
        }

        if (!empty($rowErrors)) {
            throw new ValidationException(['import_errors' => $rowErrors], 'CSV Timetable Import Failed: ' . count($rowErrors) . ' error(s) detected. Please fix the file and re-upload.');
        }

        if (empty($preparedSlots)) {
            throw new ValidationException(['file' => 'No valid timetable rows found to import.']);
        }

        $this->db->beginTransaction();
        try {
            if ($overwrite) {
                foreach (array_keys($classesAffected) as $affectedClassId) {
                    $this->timetableRepo->deleteByClassAndTerm($affectedClassId, $termId);
                }
            }

            $inserted = $this->timetableRepo->batchCreate($preparedSlots);

            $this->auditService?->log(
                actorUserId: $actor->id,
                action: 'TIMETABLE_CSV_IMPORTED',
                entityType: 'timetable_slots',
                entityId: $termId,
                before: null,
                after: ['imported_count' => $inserted, 'classes_count' => count($classesAffected)],
                metadata: ['term_id' => $termId]
            );

            $this->db->commit();

            return [
                'imported_count' => $inserted,
                'classes_count' => count($classesAffected),
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Generate downloadable CSV sample template with live school classes and subjects.
     * Output 8-column format with UTF-8 BOM, including high school period 9 and elective splits.
     */
    /**
     * Generate an Excel/CSV sample template pre-populated with active class and subject codes.
     * Supports both High School (9 instructional periods, elective splits) and Grade School (7 instructional periods).
     */
    public function generateSampleCsv(?int $classId = null, ?int $sessionId = null, ?string $stage = null): string
    {
        $headers = ['class_name', 'day_of_week', 'period_number', 'subject_code', 'department', 'room', 'slot_type', 'custom_title'];
        $rows = [];

        $allClasses = $this->academicRepo->getAllClasses();
        $isGradeSchool = ($stage === 'primary' || $stage === 'grade_school');

        if ($classId !== null) {
            $targetClass = $this->academicRepo->findClassById($classId);
            $classes = $targetClass ? [$targetClass] : [];
            if ($targetClass && in_array($targetClass->stage, ['primary', 'eyfs', 'nursery', 'creche'], true)) {
                $isGradeSchool = true;
            }
        } elseif ($stage !== null) {
            if ($isGradeSchool) {
                $classes = array_values(array_filter($allClasses, fn($c) => in_array($c->stage, ['primary', 'eyfs', 'nursery', 'creche'], true)));
            } else {
                $classes = array_values(array_filter($allClasses, fn($c) => in_array($c->stage, ['junior_secondary', 'senior_secondary'], true)));
            }
            $classes = array_slice($classes, 0, 3);
        } else {
            $classes = array_slice($allClasses, 0, 3);
        }

        $session = $sessionId ? $this->academicRepo->findSessionById($sessionId) : $this->academicRepo->getCurrentSession();
        $sessId = $session ? (int)$session->id : 0;

        if (empty($classes)) {
            if ($isGradeSchool) {
                // Authentic Grade School Schedule Sample (7 periods, 40 mins each)
                $rows[] = ['Basic 4A', 'Monday', 1, 'MTH', '', 'Room 4A', 'instructional', ''];
                $rows[] = ['Basic 4A', 'Monday', 2, 'ENG', '', 'Room 4A', 'instructional', ''];
                $rows[] = ['Basic 4A', 'Monday', 3, 'FRE', '', 'Room 4A', 'instructional', ''];
                $rows[] = ['Basic 4A', 'Monday', 4, 'DIC', '', 'Room 4A', 'instructional', ''];
                $rows[] = ['Basic 4A', 'Monday', 5, 'SCI', '', 'Room 4A', 'instructional', ''];
                $rows[] = ['Basic 4A', 'Monday', 6, 'MUS', '', 'Room 4A', 'instructional', ''];
                $rows[] = ['Basic 4A', 'Monday', 7, 'HIS', '', 'Room 4A', 'instructional', ''];

                $rows[] = ['Basic 4A', 'Tuesday', 1, 'ENG', '', 'Room 4A', 'instructional', ''];
                $rows[] = ['Basic 4A', 'Tuesday', 2, 'FRE', '', 'Room 4A', 'instructional', ''];
                $rows[] = ['Basic 4A', 'Tuesday', 3, 'MTH', '', 'Room 4A', 'instructional', ''];
                $rows[] = ['Basic 4A', 'Tuesday', 4, 'PHE', '', 'Sports Ground', 'instructional', ''];
                $rows[] = ['Basic 4A', 'Tuesday', 5, 'SCE', '', 'Room 4A', 'instructional', ''];
                $rows[] = ['Basic 4A', 'Tuesday', 6, 'CMP', '', 'Computer Lab', 'instructional', ''];
                $rows[] = ['Basic 4A', 'Tuesday', 7, 'CMP', '', 'Computer Lab', 'instructional', ''];

                $rows[] = ['Basic 4A', 'Wednesday', 6, 'APT', '', 'Room 4A', 'instructional', 'Aptitude (V.R)'];
                $rows[] = ['Basic 4A', 'Wednesday', 7, 'LIB', '', 'Library', 'instructional', 'Reading & Library Time'];

                $rows[] = ['Basic 4A', 'Friday', 1, 'MTH', '', 'Room 4A', 'instructional', 'Mental Math'];
                $rows[] = ['Basic 4A', 'Friday', 2, 'ENG', '', 'Room 4A', 'instructional', 'Big Write & Spelling Test'];
                $rows[] = ['Basic 4A', 'Friday', 3, 'ART', '', 'Art Studio', 'instructional', ''];
                $rows[] = ['Basic 4A', 'Friday', 4, 'DIC', '', 'Room 4A', 'instructional', 'Diction'];
                $rows[] = ['Basic 4A', 'Friday', 5, 'APT', '', 'Room 4A', 'instructional', 'Aptitude (Q.R)'];
            } else {
                // High School Schedule Sample (9 periods, elective splits)
                $rows[] = ['Year 10 Science', 'Monday', 1, 'MTH101', '', 'Room 101', 'instructional', ''];
                $rows[] = ['Year 10 Science', 'Monday', 2, 'ENG101', '', 'Room 101', 'instructional', ''];
                $rows[] = ['Year 10 Science', 'Monday', 3, 'CHE101', 'Science', 'Chemistry Lab', 'instructional', ''];
                $rows[] = ['Year 10 Commercial', 'Monday', 3, 'COM101', 'Commercial', 'Room 102', 'instructional', ''];
                $rows[] = ['Year 10 Arts', 'Monday', 3, 'LIT101', 'Arts', 'Room 103', 'instructional', ''];
                $rows[] = ['Year 10 Science', 'Tuesday', 9, 'BIO101', '', 'Biology Lab', 'instructional', ''];
                $rows[] = ['Year 10 Science', 'Wednesday', 8, '', '', 'Sports Field', 'sport', 'Clubs & Societies'];
            }
        }

        foreach ($classes as $class) {
            $classStage = $class->stage ?? ($isGradeSchool ? 'primary' : 'junior_secondary');
            $structure = $this->structureRepo->findByClassId((int)$class->id)
                ?? $this->structureRepo->findByStage($classStage)
                ?? $this->structureRepo->findDefault();

            $csList = $this->academicRepo->getClassSubjectsByClassAndSession((int)$class->id, $sessId);
            $subCodes = [];
            foreach ($csList as $cs) {
                if (!empty($cs->subjectCode)) {
                    $subCodes[] = $cs->subjectCode;
                }
            }

            $isClassGradeSchool = in_array($classStage, ['primary', 'eyfs', 'nursery', 'creche'], true);

            if (empty($subCodes)) {
                $subCodes = $isClassGradeSchool
                    ? ['MTH', 'ENG', 'FRE', 'DIC', 'SCI', 'MUS', 'HIS', 'PHE', 'CMP', 'ART']
                    : ['ENG101', 'MAT101', 'BSC101', 'CHE101', 'COM101', 'LIT101', 'FRE101', 'AGR101'];
            }

            $roomName = $class->name ? "Room " . substr($class->name, 0, 8) : "Room 1";

            $instructionalPeriods = $structure ? $structure->getInstructionalPeriods() : ($isClassGradeSchool ? [1, 2, 3, 4, 5, 6, 7] : [1, 2, 3, 4, 5, 6, 7, 8, 9]);
            $periodNums = array_values(array_map(fn($p) => is_object($p) ? $p->periodNumber : (int)$p, $instructionalPeriods));

            if ($isClassGradeSchool) {
                // Grade school: Periods 1 through 7
                $rows[] = [$class->name, 'Monday', $periodNums[0] ?? 1, $subCodes[0] ?? 'MTH', '', $roomName, 'instructional', ''];
                $rows[] = [$class->name, 'Monday', $periodNums[1] ?? 2, $subCodes[1] ?? 'ENG', '', $roomName, 'instructional', ''];
                $rows[] = [$class->name, 'Monday', $periodNums[2] ?? 3, $subCodes[2] ?? 'FRE', '', $roomName, 'instructional', ''];
                $rows[] = [$class->name, 'Monday', $periodNums[3] ?? 4, $subCodes[3] ?? 'DIC', '', $roomName, 'instructional', ''];
                $rows[] = [$class->name, 'Monday', $periodNums[4] ?? 5, $subCodes[4] ?? 'SCI', '', $roomName, 'instructional', ''];
                $rows[] = [$class->name, 'Monday', $periodNums[5] ?? 6, $subCodes[5] ?? 'MUS', '', $roomName, 'instructional', ''];
                $rows[] = [$class->name, 'Monday', $periodNums[6] ?? 7, $subCodes[6] ?? 'HIS', '', $roomName, 'instructional', ''];

                // Special activities from the authentic timetable
                $rows[] = [$class->name, 'Wednesday', $periodNums[5] ?? 6, '', '', $roomName, 'instructional', 'Aptitude (V.R)'];
                $rows[] = [$class->name, 'Wednesday', $periodNums[6] ?? 7, '', '', 'Library', 'instructional', 'Reading & Library Time'];
                $rows[] = [$class->name, 'Friday', $periodNums[0] ?? 1, $subCodes[0] ?? 'MTH', '', $roomName, 'instructional', 'Mental Math'];
                $rows[] = [$class->name, 'Friday', $periodNums[1] ?? 2, $subCodes[1] ?? 'ENG', '', $roomName, 'instructional', 'Big Write & Spelling Test'];
            } else {
                // High school: Periods 1 and 2 standard core subjects
                $rows[] = [$class->name, 'Monday', $periodNums[0] ?? 1, $subCodes[0] ?? 'ENG101', '', $roomName, 'instructional', ''];
                $rows[] = [$class->name, 'Monday', $periodNums[1] ?? 2, $subCodes[1] ?? 'MAT101', '', $roomName, 'instructional', ''];

                // Period 3 Elective Split example (e.g. Science Chemistry, Commercial Commerce, Arts Literature)
                $p3 = $periodNums[2] ?? 3;
                $rows[] = [$class->name, 'Monday', $p3, $subCodes[3] ?? 'CHE101', 'Science', 'Chemistry Lab', 'instructional', ''];
                $rows[] = [$class->name, 'Monday', $p3, $subCodes[4] ?? 'COM101', 'Commercial', 'Room 12', 'instructional', ''];
                $rows[] = [$class->name, 'Monday', $p3, $subCodes[5] ?? 'LIT101', 'Arts', 'Room 14', 'instructional', ''];

                // Period 9 (Late period) for High School
                $maxP = end($periodNums);
                if ($maxP && $maxP >= 8) {
                    $rows[] = [$class->name, 'Tuesday', $maxP, $subCodes[6] ?? 'FRE101', '', $roomName, 'instructional', ''];
                }

                // Custom non-subject activity example (e.g. Wednesday Sports / Activities)
                $activityPeriod = $periodNums[count($periodNums) - 2] ?? 8;
                $rows[] = [$class->name, 'Wednesday', $activityPeriod, '', '', 'Sports Complex', 'sport', 'Games & Inter-House Sports'];
            }
        }

        $output = fopen('php://temp', 'r+');
        // Prepend UTF-8 BOM for Microsoft Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $headers);
        foreach ($rows as $r) {
            fputcsv($output, $r);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return (string)$csv;
    }

    /**
     * Get Master Timetable Matrix across multiple classes for a term and stage.
     * Generates a multi-class matrix matching institutional high school / grade school schedules.
     *
     * @return array{term: Term, structure: ?\App\Models\TimetableStructure, classes: array<int, SchoolClass>, days: array<string>, periods: array<\App\Models\TimetableStructurePeriod>, matrix: array<string, array<int, array<int, array<TimetableSlot>>>>}
     */
    public function getMasterTimetableMatrix(int $termId, ?string $stageKey = 'high_school', ?int $structureId = null, ?UserContext $actor = null): array
    {
        $term = $this->resolveTerm($termId);

        // Resolve Structure
        $structure = null;
        if ($structureId !== null && $structureId > 0) {
            $structure = $this->structureRepo->findById($structureId);
        }
        if (!$structure && $stageKey) {
            $structure = $this->structureRepo->findByStage($stageKey);
        }
        if (!$structure) {
            $structure = $this->structureRepo->findDefault();
        }

        // Fetch classes
        $allClasses = $this->academicRepo->getAllClasses();
        $classes = [];
        foreach ($allClasses as $c) {
            if ($stageKey) {
                if ($stageKey === 'high_school' && in_array($c->stage, ['junior_secondary', 'senior_secondary'], true)) {
                    $classes[] = $c;
                } elseif ($stageKey === 'primary' && in_array($c->stage, ['primary', 'eyfs'], true)) {
                    $classes[] = $c;
                } elseif ($c->stage === $stageKey) {
                    $classes[] = $c;
                }
            } else {
                $classes[] = $c;
            }
        }
        if (empty($classes)) {
            $classes = array_slice($allClasses, 0, 8);
        }

        // Fetch all term slots
        $allSlots = $this->timetableRepo->findByTerm((int)$term->id);

        $days = ['mon', 'tue', 'wed', 'thu', 'fri'];
        $matrix = [];

        foreach ($days as $day) {
            $matrix[$day] = [];
            foreach ($classes as $class) {
                $matrix[$day][$class->id] = [];
            }
        }

        // Populate matrix slots: [day][class_id][period_num][] = slot
        foreach ($allSlots as $slot) {
            $d = PeriodSchedule::normalizeDay($slot->dayOfWeek) ?? strtolower($slot->dayOfWeek);
            $cId = (int)$slot->classId;
            if (!isset($matrix[$d][$cId])) {
                continue;
            }

            // Determine matching period number from structure or time
            $matchedPeriodNum = null;
            if ($structure) {
                foreach ($structure->periods as $sp) {
                    if ($sp->isInstructional() && substr($sp->startTime, 0, 5) === substr($slot->startTime, 0, 5)) {
                        $matchedPeriodNum = $sp->periodNumber;
                        break;
                    }
                }
            }

            if ($matchedPeriodNum === null) {
                // Fallback period match by time
                $block = PeriodSchedule::getPeriodByTime($slot->startTime);
                $matchedPeriodNum = $block ? ($block['period_number'] ?? $block['number'] ?? $block['period'] ?? 1) : 1;
            }

            $matrix[$d][$cId][$matchedPeriodNum][] = $slot;
        }

        return [
            'term' => $term,
            'structure' => $structure,
            'classes' => $classes,
            'days' => $days,
            'periods' => $structure ? $structure->periods : [],
            'matrix' => $matrix,
        ];
    }

    /**
     * Validate timetable slot conflicts using the half-open interval rule.
     *
     * @return array<int, array{slot: TimetableSlot, type: string, message: string}>
     */
    public function validateSlotConflicts(
        int $termId,
        ?int $classSubjectId,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        ?string $room = null,
        ?int $excludeSlotId = null,
        ?int $classId = null,
        ?string $department = null
    ): array {
        $startTime = $this->normalizeTime($startTime);
        $endTime = $this->normalizeTime($endTime);

        if (strtotime($startTime) >= strtotime($endTime)) {
            return [
                [
                    'slot' => null,
                    'type' => 'time_sequence',
                    'message' => 'Start time must strictly precede end time.',
                ]
            ];
        }

        return $this->timetableRepo->findConflicts(
            $termId,
            $dayOfWeek,
            $startTime,
            $endTime,
            $classSubjectId,
            $room,
            $excludeSlotId,
            $classId,
            $department
        );
    }

    /**
     * Validate slot data integrity.
     *
     * @return array<string, string>
     */
    private function validateSlotData(array $data): array
    {
        $errors = [];

        if (empty($data['term_id']) || (int)$data['term_id'] <= 0) {
            $errors['term_id'] = 'Academic term selection is required.';
        }

        $isActivity = (!empty($data['slot_type']) && $data['slot_type'] !== 'instructional') || !empty($data['custom_title']);
        if (!$isActivity && (empty($data['class_subject_id']) || (int)$data['class_subject_id'] <= 0)) {
            $errors['class_subject_id'] = 'Subject allocation is required.';
        }
        if ($isActivity && empty($data['class_id']) && empty($data['class_subject_id'])) {
            $errors['class_id'] = 'Class is required for activity slots.';
        }

        $validDays = [
            TimetableSlot::DAY_MON,
            TimetableSlot::DAY_TUE,
            TimetableSlot::DAY_WED,
            TimetableSlot::DAY_THU,
            TimetableSlot::DAY_FRI,
            TimetableSlot::DAY_SAT,
            TimetableSlot::DAY_SUN,
        ];

        $day = strtolower(trim((string)($data['day_of_week'] ?? '')));
        if (!in_array($day, $validDays, true)) {
            $errors['day_of_week'] = 'Please select a valid day of the week.';
        }

        $startTime = trim((string)($data['start_time'] ?? ''));
        $endTime = trim((string)($data['end_time'] ?? ''));

        if ($startTime === '') {
            $errors['start_time'] = 'Start time is required.';
        } elseif (!$this->isValidTimeFormat($startTime)) {
            $errors['start_time'] = 'Start time must be a valid time format (HH:MM or HH:MM:SS).';
        }

        if ($endTime === '') {
            $errors['end_time'] = 'End time is required.';
        } elseif (!$this->isValidTimeFormat($endTime)) {
            $errors['end_time'] = 'End time must be a valid time format (HH:MM or HH:MM:SS).';
        }

        if (empty($errors['start_time']) && empty($errors['end_time'])) {
            $normStart = $this->normalizeTime($startTime);
            $normEnd = $this->normalizeTime($endTime);

            if (strtotime($normStart) >= strtotime($normEnd)) {
                $errors['time_sequence'] = 'Start time must be earlier than end time.';
            }
        }

        return $errors;
    }

    /**
     * Organize an array of TimetableSlot objects into a weekly matrix keyed by day.
     *
     * @param array<int, TimetableSlot> $slots
     * @return array<string, array<int, TimetableSlot>>
     */
    private function organizeGrid(array $slots): array
    {
        $grid = [
            TimetableSlot::DAY_MON => [],
            TimetableSlot::DAY_TUE => [],
            TimetableSlot::DAY_WED => [],
            TimetableSlot::DAY_THU => [],
            TimetableSlot::DAY_FRI => [],
            TimetableSlot::DAY_SAT => [],
            TimetableSlot::DAY_SUN => [],
        ];

        foreach ($slots as $slot) {
            $day = strtolower($slot->dayOfWeek);
            if (isset($grid[$day])) {
                $grid[$day][] = $slot;
            }
        }

        return $grid;
    }

    private function resolveTerm(?int $termId): Term
    {
        if ($termId !== null && $termId > 0) {
            $term = $this->academicRepo->findTermById($termId);
            if ($term) {
                return $term;
            }
        }

        $currentTerm = $this->academicRepo->findCurrentTerm();
        if ($currentTerm) {
            return $currentTerm;
        }

        // Fallback to first term found
        $allTerms = $this->academicRepo->findAllTerms();
        if (!empty($allTerms)) {
            return $allTerms[0];
        }

        throw new ResourceNotFoundException('No academic term found in the system.');
    }

    private function isValidTimeFormat(string $time): bool
    {
        $time = trim($time);
        return preg_match('/^(?:[01]?\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $time) === 1;
    }

    private function normalizeTime(string $time): string
    {
        $time = trim($time);
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
            return sprintf('%02d:%02d:00', (int)$matches[1], (int)$matches[2]);
        }
        if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $time, $matches)) {
            return sprintf('%02d:%02d:%02d', (int)$matches[1], (int)$matches[2], (int)$matches[3]);
        }

        $timestamp = strtotime($time);
        if ($timestamp !== false) {
            return date('H:i:s', $timestamp);
        }

        return $time;
    }
}
