<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\Models\AttendanceRecord;
use App\Policies\AttendancePolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\AttendanceRepository;
use PDO;

/**
 * Attendance Application Service
 */
class AttendanceService
{
    private AttendanceRepository $attendanceRepo;
    private AttendancePolicy $policy;
    private AuditService $auditService;
    private AcademicRepository $academicRepo;
    private ?PDO $db;

    public function __construct(
        ?AttendanceRepository $attendanceRepo = null,
        ?AttendancePolicy $policy = null,
        ?AuditService $auditService = null,
        ?AcademicRepository $academicRepo = null,
        ?PDO $db = null
    ) {
        $this->attendanceRepo = $attendanceRepo ?? new AttendanceRepository($db);
        $this->policy = $policy ?? new AttendancePolicy($db);
        $this->auditService = $auditService ?? new AuditService($db);
        $this->academicRepo = $academicRepo ?? new AcademicRepository($db);
        $this->db = $db;
    }

    /**
     * Get attendance roster for class/date with default 'present' state for unrecorded students.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRoster(
        int $classId,
        string $date,
        ?int $classSubjectId = null,
        ?int $periodNumber = null,
        ?UserContext $user = null
    ): array {
        if ($user !== null && !$this->policy->canMark($user, $classId, $classSubjectId)) {
            throw new AuthorizationException('You are not authorized to access attendance for this class.');
        }

        $rawRoster = $this->attendanceRepo->getRosterForDate($classId, $date, $classSubjectId, $periodNumber);

        // Normalize roster items with default status
        $roster = [];
        foreach ($rawRoster as $row) {
            $status = $row['status'] ?? 'present';
            $roster[] = [
                'student_id' => (int)$row['student_id'],
                'student_name' => (string)$row['student_name'],
                'admission_number' => (string)$row['admission_number'],
                'attendance_id' => isset($row['attendance_id']) ? (int)$row['attendance_id'] : null,
                'status' => $status,
                'is_recorded' => isset($row['attendance_id']) && $row['attendance_id'] !== null,
                'correction_reason' => $row['correction_reason'] ?? null,
            ];
        }

        return $roster;
    }

    /**
     * Record or update an entire class roster batch.
     *
     * @param array<int, array{student_id: int, status: string}> $records
     */
    public function recordRoster(
        int $classId,
        string $date,
        ?int $classSubjectId,
        ?int $periodNumber,
        array $records,
        UserContext $user,
        ?string $correctionReason = null
    ): void {
        if (!$this->policy->canMark($user, $classId, $classSubjectId)) {
            throw new AuthorizationException('You are not authorized to mark attendance for this class.');
        }

        if (empty($records)) {
            throw new ValidationException(['records' => 'Attendance records roster cannot be empty.']);
        }

        $isPastGrace = $this->policy->isPastGracePeriod($date);
        if ($isPastGrace) {
            if (!$user->isAdmin() && !$user->isSuperAdmin()) {
                throw new AuthorizationException('Only administrators can modify attendance records outside the 24-hour edit window.');
            }
            if (empty(trim((string)$correctionReason))) {
                throw new ValidationException(['correction_reason' => 'A correction reason is mandatory for historical attendance modifications.']);
            }
        }

        // Validate statuses
        $validStatuses = ['present', 'absent', 'late', 'excused'];
        foreach ($records as $r) {
            if (!in_array($r['status'] ?? '', $validStatuses, true)) {
                throw new ValidationException(['status' => 'Invalid attendance status provided: ' . ($r['status'] ?? '')]);
            }
        }

        // Resolve active session and term
        $activeSession = $this->academicRepo->findActiveSession();
        if (!$activeSession) {
            $stmt = $this->academicRepo->getPdo()->query("SELECT * FROM sessions WHERE is_current = 1 OR status = 'active' LIMIT 1");
            $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
            if ($row) {
                $activeSession = \App\Models\AcademicSession::fromArray([
                    'id' => (int)$row['id'],
                    'name' => (string)$row['name'],
                    'start_date' => $row['start_date'] ?? date('Y-m-d'),
                    'end_date' => $row['end_date'] ?? date('Y-m-d'),
                    'status' => $row['status'] ?? 'active'
                ]);
            }
        }

        $activeTerm = null;
        if ($activeSession) {
            $activeTerm = $this->academicRepo->findActiveTermForSession($activeSession->id);
            if (!$activeTerm) {
                $stmt = $this->academicRepo->getPdo()->prepare("SELECT * FROM terms WHERE session_id = :sid AND (is_current = 1 OR status = 'active') LIMIT 1");
                $stmt->execute([':sid' => $activeSession->id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $activeTerm = \App\Models\Term::fromArray([
                        'id' => (int)$row['id'],
                        'session_id' => (int)$row['session_id'],
                        'name' => (string)$row['name'],
                        'start_date' => $row['start_date'] ?? date('Y-m-d'),
                        'end_date' => $row['end_date'] ?? date('Y-m-d'),
                        'status' => $row['status'] ?? 'active'
                    ]);
                }
            }
        }

        if (!$activeTerm || !$activeSession) {
            throw new ValidationException(['term' => 'No active academic term or session found.']);
        }

        $sessionId = $activeSession->id;
        $termId = $activeTerm->id;
        $userId = $user->getUserId();

        $executeSave = function () use ($sessionId, $termId, $classId, $classSubjectId, $date, $periodNumber, $userId, $records, $correctionReason, $isPastGrace) {
            $this->attendanceRepo->saveRosterBatch(
                sessionId: $sessionId,
                termId: $termId,
                classId: $classId,
                classSubjectId: $classSubjectId,
                date: $date,
                periodNumber: $periodNumber,
                markedBy: $userId,
                records: $records,
                correctionReason: $correctionReason
            );

            // Audit log
            $this->auditService->log(
                action: $isPastGrace ? 'attendance.corrected' : 'attendance.recorded',
                entityType: 'attendance_roster',
                entityId: $classId,
                actorUserId: $userId,
                before: null,
                after: [
                    'class_id' => $classId,
                    'date' => $date,
                    'class_subject_id' => $classSubjectId,
                    'period_number' => $periodNumber,
                    'records_count' => count($records),
                ],
                metadata: [
                    'correction_reason' => $correctionReason,
                    'is_past_grace' => $isPastGrace,
                ]
            );

            // Sync student term summaries
            foreach ($records as $r) {
                $this->attendanceRepo->syncStudentTermSummaryAttendance((int)$r['student_id'], $termId);
            }
        };

        if ($this->db !== null) {
            $this->db->beginTransaction();
            try {
                $executeSave();
                $this->db->commit();
            } catch (\Throwable $e) {
                $this->db->rollBack();
                throw $e;
            }
        } else {
            Database::transaction($executeSave);
        }
    }

    /**
     * Update an individual attendance record with correction metadata.
     */
    public function updateRecord(
        int $id,
        string $status,
        UserContext $user,
        string $correctionReason
    ): AttendanceRecord {
        $record = $this->attendanceRepo->findRecordById($id);
        if (!$record) {
            throw new ResourceNotFoundException("Attendance record #{$id} not found.");
        }

        $validStatuses = ['present', 'absent', 'late', 'excused'];
        if (!in_array($status, $validStatuses, true)) {
            throw new ValidationException(['status' => 'Invalid attendance status.']);
        }

        $isPastGrace = $this->policy->isPastGracePeriod($record->date);
        if ($isPastGrace && !$user->isAdmin() && !$user->isSuperAdmin()) {
            throw new AuthorizationException('Only administrators can modify historical attendance records.');
        }

        if (empty(trim($correctionReason))) {
            throw new ValidationException(['correction_reason' => 'A correction reason is required.']);
        }

        $this->attendanceRepo->updateRecord($id, $status, $user->getUserId(), $correctionReason);

        $this->auditService->log(
            action: 'attendance.record_corrected',
            entityType: 'attendance_record',
            entityId: $id,
            actorUserId: $user->getUserId(),
            before: ['status' => $record->status],
            after: ['status' => $status],
            metadata: ['correction_reason' => $correctionReason]
        );

        $this->attendanceRepo->syncStudentTermSummaryAttendance($record->studentId, $record->termId);

        return $this->attendanceRepo->findRecordById($id);
    }

    /**
     * Calculate student attendance metrics.
     */
    public function getStudentSummary(int $studentId, int $termId, UserContext $user): array
    {
        return $this->attendanceRepo->getStudentAttendanceSummary($studentId, $termId);
    }

    /**
     * Get student attendance history.
     *
     * @return array<int, AttendanceRecord>
     */
    public function getStudentHistory(int $studentId, int $termId, ?int $classSubjectId, UserContext $user): array
    {
        return $this->attendanceRepo->getStudentAttendanceHistory($studentId, $termId, $classSubjectId);
    }
}
