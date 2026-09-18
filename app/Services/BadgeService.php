<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\Models\Badge;
use App\Models\StudentBadge;
use App\Repositories\AcademicRepository;
use App\Repositories\BadgeRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;

class BadgeService
{
    private BadgeRepository $badgeRepo;
    private StudentRepository $studentRepo;
    private TeacherRepository $teacherRepo;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?BadgeRepository $badgeRepo = null,
        ?StudentRepository $studentRepo = null,
        ?TeacherRepository $teacherRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        $this->badgeRepo = $badgeRepo ?? new BadgeRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    /**
     * Get all badges available in the system catalog.
     * @return Badge[]
     */
    public function getAllBadges(): array
    {
        return $this->badgeRepo->findAll();
    }

    /**
     * Get all badges earned by a student.
     * @return StudentBadge[]
     */
    public function getStudentBadges(int $studentId): array
    {
        return $this->badgeRepo->getStudentBadges($studentId);
    }

    /**
     * Award a badge manually to a student (Teacher or Admin).
     */
    public function awardBadge(
        int $studentId,
        int $badgeId,
        string $reason,
        UserContext $actor,
        ?int $classSubjectId = null
    ): StudentBadge {
        if (!$actor->hasAnyRole(['super_admin', 'admin', 'teacher'])) {
            throw new AuthorizationException('Only authorized teachers and administrators can award student badges.');
        }

        $student = $this->studentRepo->findById($studentId);
        if (!$student) {
            throw new ResourceNotFoundException('Student not found.');
        }

        $badge = $this->badgeRepo->findById($badgeId);
        if (!$badge) {
            throw new ResourceNotFoundException('Badge definition not found.');
        }

        $trimmedReason = trim($reason);
        if (empty($trimmedReason)) {
            throw new ValidationException(['reason' => 'A commendation reason is required when awarding a badge.']);
        }

        if ($this->badgeRepo->hasBadge($studentId, $badgeId, $classSubjectId)) {
            throw new ValidationException(['badge_id' => 'This student has already been awarded this badge for this subject.']);
        }

        // If teacher, verify they teach the student
        if ($actor->hasRole('teacher') && !$actor->hasAnyRole(['super_admin', 'admin'])) {
            $userId = (int)($actor->id ?? $actor->userId ?? 0);
            $teacher = $this->teacherRepo->findTeacherByUserId($userId);
            if (!$teacher) {
                throw new AuthorizationException('Teacher profile not found.');
            }

            if ($classSubjectId !== null && $classSubjectId > 0) {
                $cs = $this->academicRepo->findClassSubjectById($classSubjectId);
                if (!$cs || (int)$cs->teacherId !== (int)$teacher->id) {
                    throw new AuthorizationException('You are not authorized to award badges for this subject.');
                }
            } else {
                // Verify teacher teaches at least one subject taken by the student
                $teacherClassSubjects = $this->academicRepo->getClassSubjectsByTeacher($teacher->id);
                $teacherCsIds = array_map(fn($cs) => (int)$cs->id, $teacherClassSubjects);

                $enrolledStudents = $this->studentRepo->getStudentsByClassSubjectIds($teacherCsIds);
                $isEnrolled = false;
                foreach ($enrolledStudents as $enrolled) {
                    if ((int)$enrolled->id === $studentId) {
                        $isEnrolled = true;
                        break;
                    }
                }

                if (!$isEnrolled) {
                    throw new AuthorizationException('You can only award badges to students enrolled in your classes.');
                }
            }
        }

        $activeSession = $this->academicRepo->getActiveSession();
        $activeTerm = $this->academicRepo->getActiveTerm();

        $userId = (int)($actor->id ?? $actor->userId ?? 0);

        return $this->badgeRepo->awardBadge(
            studentId: $studentId,
            badgeId: $badgeId,
            awardedBy: $userId,
            reason: $trimmedReason,
            classSubjectId: $classSubjectId,
            sessionId: $activeSession?->id,
            termId: $activeTerm?->id
        );
    }

    /**
     * Automatically award Course Completer badge when student reaches 100% course progression.
     */
    public function evaluateCourseCompletionBadge(int $studentId, int $classSubjectId, ?UserContext $actor = null): ?StudentBadge
    {
        $badge = $this->badgeRepo->findBySlug('course-completer');
        if (!$badge) {
            return null;
        }

        $activeSession = $this->academicRepo->getActiveSession();
        $sessionId = $activeSession?->id;

        // Check if student already has this badge for this subject
        if ($this->badgeRepo->hasBadge($studentId, $badge->id, $classSubjectId, $sessionId)) {
            return null;
        }

        $userId = (int)($actor?->id ?? $actor?->userId ?? 0);
        if ($userId <= 0) {
            // System actor fallback
            $userId = 1;
        }

        $cs = $this->academicRepo->findClassSubjectById($classSubjectId);
        $subjectName = $cs?->subject?->name ?? 'Course';

        return $this->badgeRepo->awardBadge(
            studentId: $studentId,
            badgeId: $badge->id,
            awardedBy: $userId,
            reason: "Completed 100% of all instructional modules and required learning activities in {$subjectName}.",
            classSubjectId: $classSubjectId,
            sessionId: $sessionId,
            termId: $this->academicRepo->getActiveTerm()?->id
        );
    }

    /**
     * Automatically award Academic Excellence badge when student scores >= 90% on a CBT quiz.
     */
    public function evaluateQuizExcellenceBadge(int $studentId, int $classSubjectId, float $scorePercent, UserContext $actor): ?StudentBadge
    {
        if ($scorePercent < 90.0) {
            return null;
        }

        $badge = $this->badgeRepo->findBySlug('academic-excellence');
        if (!$badge) {
            return null;
        }

        $activeSession = $this->academicRepo->getActiveSession();
        $sessionId = $activeSession?->id;

        if ($this->badgeRepo->hasBadge($studentId, $badge->id, $classSubjectId, $sessionId)) {
            return null;
        }

        $userId = (int)($actor->id ?? $actor->userId ?? 0);
        if ($userId <= 0) {
            $userId = 1;
        }

        return $this->badgeRepo->awardBadge(
            studentId: $studentId,
            badgeId: $badge->id,
            awardedBy: $userId,
            reason: sprintf("Achieved an exceptional score of %.1f%% on course evaluation.", $scorePercent),
            classSubjectId: $classSubjectId,
            sessionId: $sessionId,
            termId: $this->academicRepo->getActiveTerm()?->id
        );
    }

    /**
     * Get school-wide awarded badges for Admin oversight (ADMIN-34).
     * @return StudentBadge[]
     */
    public function getAllAwardedBadges(?int $badgeId = null, ?int $classId = null, int $limit = 50, int $offset = 0): array
    {
        return $this->badgeRepo->getAllAwardedBadges($badgeId, $classId, $limit, $offset);
    }

    /**
     * Revoke an awarded badge (Super Admin or Admin only).
     */
    public function revokeBadge(int $studentBadgeId, UserContext $actor): bool
    {
        if (!$actor->isAdmin()) {
            throw new AuthorizationException('Only administrators can revoke awarded badges.');
        }

        $studentBadge = $this->badgeRepo->findStudentBadgeById($studentBadgeId);
        if (!$studentBadge) {
            throw new ResourceNotFoundException('Award record not found.');
        }

        return $this->badgeRepo->deleteStudentBadge($studentBadgeId);
    }
}
