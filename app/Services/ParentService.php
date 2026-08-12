<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\UserContext;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Policies\ParentPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\AnnouncementRepository;
use App\Repositories\AssignmentRepository;
use App\Repositories\AttendanceRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\ParentRepository;
use App\Repositories\ResultPublicationRepository;
use App\Repositories\StudentRepository;

/**
 * Service Layer for Parent Portal orchestration, child validation, and dashboard metrics
 */
class ParentService
{
    private ParentRepository $parentRepo;
    private StudentRepository $studentRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;
    private AttendanceRepository $attendanceRepo;
    private AnnouncementRepository $announcementRepo;
    private AnnouncementService $announcementService;
    private GradebookRepository $gradebookRepo;
    private ResultPublicationRepository $publicationRepo;
    private AssignmentRepository $assignmentRepo;
    private AssignmentService $assignmentService;

    public function __construct(
        ?ParentRepository $parentRepo = null,
        ?StudentRepository $studentRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?EnrollmentRepository $enrollmentRepo = null,
        ?AttendanceRepository $attendanceRepo = null,
        ?AnnouncementRepository $announcementRepo = null,
        ?AnnouncementService $announcementService = null,
        ?GradebookRepository $gradebookRepo = null,
        ?ResultPublicationRepository $publicationRepo = null,
        ?AssignmentRepository $assignmentRepo = null,
        ?AssignmentService $assignmentService = null
    ) {
        $this->parentRepo = $parentRepo ?? new ParentRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->enrollmentRepo = $enrollmentRepo ?? new EnrollmentRepository();
        $this->attendanceRepo = $attendanceRepo ?? new AttendanceRepository();
        $this->announcementRepo = $announcementRepo ?? new AnnouncementRepository();
        $this->announcementService = $announcementService ?? new AnnouncementService();
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository();
        $this->publicationRepo = $publicationRepo ?? new ResultPublicationRepository();
        $this->assignmentRepo = $assignmentRepo ?? new AssignmentRepository();
        $this->assignmentService = $assignmentService ?? new AssignmentService();
    }

    /**
     * Resolve the parent profile for the authenticated user.
     */
    public function getParentProfile(UserContext $actor): ?ParentProfile
    {
        return $this->parentRepo->findByUserId($actor->getUserId());
    }

    /**
     * Get all linked children for the authenticated parent.
     *
     * @return Student[]
     */
    public function getLinkedChildren(UserContext $actor): array
    {
        $parent = $this->getParentProfile($actor);
        if (!$parent) {
            return [];
        }

        return $this->parentRepo->getLinkedStudents($parent->id);
    }

    /**
     * Validate that the parent is actively linked to the student.
     * Throws AuthorizationException if unlinked or non-parent.
     */
    public function validateChildAccess(UserContext $actor, int $studentId): Student
    {
        if (!ParentPolicy::canViewStudent($actor, $studentId, $this->parentRepo)) {
            throw new AuthorizationException('You are not authorized to view information for this student.');
        }

        $student = $this->studentRepo->findById($studentId);
        if (!$student) {
            throw new ResourceNotFoundException('Student record not found.');
        }

        return $student;
    }

    /**
     * Resolve the active selected child ID safely.
     * Validates that the child belongs to the parent before returning.
     */
    public function resolveSelectedChildId(
        UserContext $actor,
        ?int $requestedStudentId = null,
        ?int $sessionSelectedChildId = null
    ): ?int {
        $children = $this->getLinkedChildren($actor);
        if (empty($children)) {
            return null;
        }

        $linkedIds = array_map(fn($c) => $c->id, $children);

        // 1. If explicit request parameter provided and valid
        if ($requestedStudentId !== null && in_array($requestedStudentId, $linkedIds, true)) {
            return $requestedStudentId;
        }

        // 2. If valid session-selected child ID exists and is linked
        if ($sessionSelectedChildId !== null && in_array($sessionSelectedChildId, $linkedIds, true)) {
            return $sessionSelectedChildId;
        }

        // 3. Default to the first linked child
        return $children[0]->id;
    }

    /**
     * Aggregate data for the Parent Portal Dashboard.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(UserContext $actor, ?int $selectedStudentId = null): array
    {
        $parent = $this->getParentProfile($actor);
        if (!$parent) {
            return [
                'parent' => null,
                'children' => [],
                'selectedChild' => null,
                'childrenSummaries' => [],
                'recentAnnouncements' => [],
            ];
        }

        $children = $this->parentRepo->getLinkedStudents($parent->id);
        if (empty($children)) {
            return [
                'parent' => $parent,
                'children' => [],
                'selectedChild' => null,
                'childrenSummaries' => [],
                'recentAnnouncements' => [],
            ];
        }

        $currentSession = $this->academicRepo->getCurrentSession();
        $currentTerm = $this->academicRepo->getCurrentTerm();
        $termId = $currentTerm ? $currentTerm->id : 0;
        $sessionId = $currentSession ? $currentSession->id : 0;

        $resolvedSelectedId = $this->resolveSelectedChildId($actor, $selectedStudentId);
        $selectedChild = null;

        $childrenSummaries = [];
        foreach ($children as $child) {
            if ($child->id === $resolvedSelectedId) {
                $selectedChild = $child;
            }

            // Attendance Summary for Current Term
            $attSummary = $termId > 0 ? $this->attendanceRepo->getStudentAttendanceSummary($child->id, $termId) : null;

            // Published Term Summary
            $isPublished = $termId > 0 && $this->publicationRepo->isPublished($termId);
            $termSummary = null;
            if ($isPublished && $termId > 0) {
                $termSummary = $this->gradebookRepo->findStudentTermSummary($child->id, $termId);
            }

            // Recent Coursework Assignments
            $recentAssignments = [];
            try {
                $assignmentData = $this->assignmentService->getParentChildAssignments($child->id, $actor, $termId ?: null);
                $assignments = $assignmentData['assignments'] ?? [];
                $submissions = $assignmentData['submissions'] ?? [];
                
                // Take top 3 most recent
                $sliced = array_slice($assignments, 0, 3);
                foreach ($sliced as $asgn) {
                    $recentAssignments[] = [
                        'assignment' => $asgn,
                        'submission' => $submissions[$asgn->id] ?? null,
                    ];
                }
            } catch (\Throwable) {
                $recentAssignments = [];
            }

            $childrenSummaries[$child->id] = [
                'student' => $child,
                'currentClass' => $child->className ?? 'Unassigned',
                'activeSession' => $currentSession,
                'activeTerm' => $currentTerm,
                'attendanceSummary' => $attSummary,
                'termSummary' => $termSummary,
                'isResultPublished' => $isPublished,
                'recentAssignments' => $recentAssignments,
            ];
        }

        // Consolidated Announcement Feed for parent
        $recentAnnouncements = $this->announcementService->getUserFeed($actor, $resolvedSelectedId);

        return [
            'parent' => $parent,
            'children' => $children,
            'selectedChild' => $selectedChild ?? $children[0],
            'childrenSummaries' => $childrenSummaries,
            'recentAnnouncements' => array_slice($recentAnnouncements, 0, 5),
            'currentSession' => $currentSession,
            'currentTerm' => $currentTerm,
        ];
    }

    /**
     * Aggregate detailed overview data for a single child.
     *
     * @return array<string, mixed>
     */
    public function getChildOverview(UserContext $actor, int $studentId): array
    {
        $student = $this->validateChildAccess($actor, $studentId);
        $parent = $this->getParentProfile($actor);

        $currentSession = $this->academicRepo->getCurrentSession();
        $currentTerm = $this->academicRepo->getCurrentTerm();
        $termId = $currentTerm ? $currentTerm->id : 0;
        $sessionId = $currentSession ? $currentSession->id : 0;

        // Attendance metrics
        $attendanceSummary = $termId > 0 ? $this->attendanceRepo->getStudentAttendanceSummary($student->id, $termId) : null;
        $recentAttendanceHistory = $termId > 0 ? array_slice($this->attendanceRepo->getStudentAttendanceHistory($student->id, $termId), 0, 10) : [];

        // Grade publication & term summary
        $isResultPublished = $termId > 0 && $this->publicationRepo->isPublished($termId);
        $termSummary = null;
        $subjectResults = [];
        if ($isResultPublished && $termId > 0) {
            $termSummary = $this->gradebookRepo->findStudentTermSummary($student->id, $termId);
            $subjectResults = $this->gradebookRepo->getTermResultsByStudent($student->id, $termId);
        }

        // Enrolled Subjects
        $subjectEnrollments = $sessionId > 0 ? $this->enrollmentRepo->getStudentSubjectEnrollments($student->id, $sessionId) : [];

        // Coursework
        $assignmentData = $this->assignmentService->getParentChildAssignments($student->id, $actor, $termId ?: null);

        // Announcements
        $announcements = $this->announcementService->getUserFeed($actor, $student->id);

        return [
            'student' => $student,
            'parent' => $parent,
            'activeSession' => $currentSession,
            'activeTerm' => $currentTerm,
            'attendanceSummary' => $attendanceSummary,
            'recentAttendanceHistory' => $recentAttendanceHistory,
            'isResultPublished' => $isResultPublished,
            'termSummary' => $termSummary,
            'subjectResults' => $subjectResults,
            'subjectEnrollments' => $subjectEnrollments,
            'assignments' => $assignmentData['assignments'] ?? [],
            'submissions' => $assignmentData['submissions'] ?? [],
            'announcements' => array_slice($announcements, 0, 5),
        ];
    }
}
