<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\Database;
use App\Core\UserContext;
use App\Models\AttendanceRecord;
use PDO;

/**
 * Attendance Authorization Policy
 */
class AttendancePolicy
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Check if user can mark attendance for a given class / class_subject.
     */
    public function canMark(UserContext $user, int $classId, ?int $classSubjectId = null): bool
    {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        if (!$user->isTeacher()) {
            return false;
        }

        $userId = $user->getUserId();

        // Get teacher id
        $stmt = $this->db->prepare("SELECT id FROM teachers WHERE user_id = :uid LIMIT 1");
        $stmt->execute([':uid' => $userId]);
        $teacherId = (int)$stmt->fetchColumn();

        if ($teacherId <= 0) {
            return false;
        }

        if ($classSubjectId !== null && $classSubjectId > 0) {
            // Period attendance: Teacher must be assigned to this class_subject
            $csStmt = $this->db->prepare("SELECT COUNT(*) FROM class_subjects WHERE id = :csid AND teacher_id = :tid");
            $csStmt->execute([':csid' => $classSubjectId, ':tid' => $teacherId]);
            return (int)$csStmt->fetchColumn() > 0;
        }

        // Daily roll call: Teacher must teach at least one subject in this class
        $cStmt = $this->db->prepare("SELECT COUNT(*) FROM class_subjects WHERE class_id = :cid AND teacher_id = :tid");
        $cStmt->execute([':cid' => $classId, ':tid' => $teacherId]);
        return (int)$cStmt->fetchColumn() > 0;
    }

    /**
     * Check if user can edit an existing attendance record.
     */
    public function canEdit(UserContext $user, AttendanceRecord $record): bool
    {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        if (!$user->isTeacher()) {
            return false;
        }

        // Teacher can only edit within 24-hour edit grace window
        if ($this->isPastGracePeriod($record->date)) {
            return false;
        }

        return $this->canMark($user, $record->classId, $record->classSubjectId);
    }

    /**
     * Determine if a date is past the 24-hour edit grace period.
     */
    public function isPastGracePeriod(string $date): bool
    {
        $recordTimestamp = strtotime($date . ' 23:59:59');
        $graceExpiry = $recordTimestamp + 86400; // 24 hours after the end of attendance day
        return time() > $graceExpiry;
    }

    /**
     * Check if user can view attendance records for a student.
     */
    public function canView(UserContext $user, int $studentId, int $classId): bool
    {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        $userId = $user->getUserId();

        if ($user->isTeacher()) {
            return $this->canMark($user, $classId, null);
        }

        if ($user->isStudent()) {
            $stmt = $this->db->prepare("SELECT id FROM students WHERE user_id = :uid LIMIT 1");
            $stmt->execute([':uid' => $userId]);
            $currentStudentId = (int)$stmt->fetchColumn();
            return $currentStudentId === $studentId;
        }

        if ($user->isParent()) {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM parent_student ps
                JOIN parents p ON p.id = ps.parent_id
                WHERE p.user_id = :uid AND ps.student_id = :sid
            ");
            $stmt->execute([':uid' => $userId, ':sid' => $studentId]);
            return (int)$stmt->fetchColumn() > 0;
        }

        return false;
    }
}
