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
    private PDO $db;

    public function __construct(
        ?TimetableRepository $timetableRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?EnrollmentRepository $enrollmentRepo = null,
        ?TeacherRepository $teacherRepo = null,
        ?StudentRepository $studentRepo = null,
        ?ParentRepository $parentRepo = null,
        ?AuditService $auditService = null,
        ?PDO $db = null
    ) {
        $this->db = $db ?? Database::getConnection();
        $this->timetableRepo = $timetableRepo ?? new TimetableRepository($this->db);
        $this->academicRepo = $academicRepo ?? new AcademicRepository($this->db);
        $this->enrollmentRepo = $enrollmentRepo ?? new EnrollmentRepository($this->db);
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository($this->db);
        $this->studentRepo = $studentRepo ?? new StudentRepository($this->db);
        $this->parentRepo = $parentRepo ?? new ParentRepository($this->db);
        $this->auditService = $auditService ?? new AuditService($this->db);
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
     * @param array{term_id: int, class_subject_id: int, day_of_week: string, start_time: string, end_time: string, room?: ?string} $data
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
        $classSubjectId = (int)$data['class_subject_id'];
        $dayOfWeek = strtolower(trim((string)$data['day_of_week']));
        $startTime = $this->normalizeTime((string)$data['start_time']);
        $endTime = $this->normalizeTime((string)$data['end_time']);
        $room = isset($data['room']) && trim((string)$data['room']) !== '' ? trim((string)$data['room']) : null;

        // Verify term exists
        $term = $this->academicRepo->findTermById($termId);
        if (!$term) {
            throw new ResourceNotFoundException("Term #{$termId} not found.");
        }

        // Verify class_subject exists
        $classSubject = $this->academicRepo->findClassSubjectById($classSubjectId);
        if (!$classSubject) {
            throw new ResourceNotFoundException("Class Subject #{$classSubjectId} not found.");
        }

        // Cross-Session Invariant: term.session_id === class_subject.session_id
        if ((int)$term->sessionId !== (int)$classSubject->sessionId) {
            throw new DomainRuleException("Cross-Session Violation: Term session (#{$term->sessionId}) does not match Class Subject session (#{$classSubject->sessionId}).");
        }

        // Conflict check
        $conflicts = $this->validateSlotConflicts($termId, $classSubjectId, $dayOfWeek, $startTime, $endTime, $room);
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
                'class_subject_id' => $classSubjectId,
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
                metadata: ['class_subject_id' => $classSubjectId, 'term_id' => $termId]
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
     * @param array{term_id?: int, class_subject_id?: int, day_of_week?: string, start_time?: string, end_time?: string, room?: ?string} $data
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
            'class_subject_id' => isset($data['class_subject_id']) ? (int)$data['class_subject_id'] : $existing->classSubjectId,
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
        $classSubjectId = (int)$mergedData['class_subject_id'];
        $dayOfWeek = strtolower(trim((string)$mergedData['day_of_week']));
        $startTime = $this->normalizeTime((string)$mergedData['start_time']);
        $endTime = $this->normalizeTime((string)$mergedData['end_time']);
        $room = isset($mergedData['room']) && trim((string)$mergedData['room']) !== '' ? trim((string)$mergedData['room']) : null;

        $term = $this->academicRepo->findTermById($termId);
        if (!$term) {
            throw new ResourceNotFoundException("Term #{$termId} not found.");
        }

        $classSubject = $this->academicRepo->findClassSubjectById($classSubjectId);
        if (!$classSubject) {
            throw new ResourceNotFoundException("Class Subject #{$classSubjectId} not found.");
        }

        // Cross-session invariant
        if ((int)$term->sessionId !== (int)$classSubject->sessionId) {
            throw new DomainRuleException("Cross-Session Violation: Term session (#{$term->sessionId}) does not match Class Subject session (#{$classSubject->sessionId}).");
        }

        // Conflict check excluding current slot
        $conflicts = $this->validateSlotConflicts($termId, $classSubjectId, $dayOfWeek, $startTime, $endTime, $room, $slotId);
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
                'class_subject_id' => $classSubjectId,
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
                metadata: ['class_subject_id' => $classSubjectId, 'term_id' => $termId]
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
     * Validate timetable slot conflicts using the half-open interval rule.
     *
     * @return array<int, array{slot: TimetableSlot, type: string, message: string}>
     */
    public function validateSlotConflicts(
        int $termId,
        int $classSubjectId,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        ?string $room = null,
        ?int $excludeSlotId = null
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
            $excludeSlotId
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

        if (empty($data['class_subject_id']) || (int)$data['class_subject_id'] <= 0) {
            $errors['class_subject_id'] = 'Subject allocation is required.';
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
