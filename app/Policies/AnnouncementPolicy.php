<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\Database;
use App\Core\UserContext;
use App\Models\Announcement;
use PDO;

/**
 * Announcement Authorization Policy
 */
class AnnouncementPolicy
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Check if user can create an announcement for a scope.
     */
    public function canCreate(UserContext $user, string $scope, ?int $scopeId = null): bool
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

        // Teachers cannot post school-wide announcements
        if ($scope === 'school') {
            return false;
        }

        if ($scope === 'class_subject' && $scopeId !== null) {
            $csStmt = $this->db->prepare("SELECT COUNT(*) FROM class_subjects WHERE id = :csid AND teacher_id = :tid");
            $csStmt->execute([':csid' => $scopeId, ':tid' => $teacherId]);
            return (int)$csStmt->fetchColumn() > 0;
        }

        if ($scope === 'class' && $scopeId !== null) {
            $cStmt = $this->db->prepare("SELECT COUNT(*) FROM class_subjects WHERE class_id = :cid AND teacher_id = :tid");
            $cStmt->execute([':cid' => $scopeId, ':tid' => $teacherId]);
            return (int)$cStmt->fetchColumn() > 0;
        }

        return false;
    }

    /**
     * Check if user can manage (edit/delete) an announcement.
     */
    public function canManage(UserContext $user, Announcement $announcement): bool
    {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        if ($user->isTeacher() && $announcement->authorId === $user->getUserId()) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can view an announcement.
     */
    public function canView(UserContext $user, Announcement $announcement, ?int $studentId = null): bool
    {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        if ($announcement->isSchoolWide()) {
            return true;
        }

        $userId = $user->getUserId();

        if ($user->isTeacher()) {
            if ($announcement->authorId === $userId) {
                return true;
            }
            $tStmt = $this->db->prepare("SELECT id FROM teachers WHERE user_id = :uid LIMIT 1");
            $tStmt->execute([':uid' => $userId]);
            $teacherId = (int)$tStmt->fetchColumn();

            if ($announcement->isSubjectScoped() && $announcement->scopeId !== null) {
                $csStmt = $this->db->prepare("SELECT COUNT(*) FROM class_subjects WHERE id = :csid AND teacher_id = :tid");
                $csStmt->execute([':csid' => $announcement->scopeId, ':tid' => $teacherId]);
                return (int)$csStmt->fetchColumn() > 0;
            }

            if ($announcement->isClassScoped() && $announcement->scopeId !== null) {
                $cStmt = $this->db->prepare("SELECT COUNT(*) FROM class_subjects WHERE class_id = :cid AND teacher_id = :tid");
                $cStmt->execute([':cid' => $announcement->scopeId, ':tid' => $teacherId]);
                return (int)$cStmt->fetchColumn() > 0;
            }
        }

        $evalStudentId = $studentId;
        if ($evalStudentId === null && $user->isStudent()) {
            $sStmt = $this->db->prepare("SELECT id FROM students WHERE user_id = :uid LIMIT 1");
            $sStmt->execute([':uid' => $userId]);
            $evalStudentId = (int)$sStmt->fetchColumn();
        }

        if ($evalStudentId !== null && $evalStudentId > 0) {
            if ($announcement->isClassScoped() && $announcement->scopeId !== null) {
                $eStmt = $this->db->prepare("SELECT COUNT(*) FROM class_enrollments WHERE student_id = :sid AND class_id = :cid AND status = 'active'");
                $eStmt->execute([':sid' => $evalStudentId, ':cid' => $announcement->scopeId]);
                return (int)$eStmt->fetchColumn() > 0;
            }

            if ($announcement->isSubjectScoped() && $announcement->scopeId !== null) {
                $seStmt = $this->db->prepare("SELECT COUNT(*) FROM student_subject_enrollments WHERE student_id = :sid AND class_subject_id = :csid AND status = 'active'");
                $seStmt->execute([':sid' => $evalStudentId, ':csid' => $announcement->scopeId]);
                return (int)$seStmt->fetchColumn() > 0;
            }
        }

        return false;
    }
}
