<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\AnnouncementRepository;
use App\Repositories\AttendanceRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\BadgeRepository;
use App\Repositories\LiveClassRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TimetableRepository;
use App\Services\AssignmentService;
use App\Services\QuizService;

/**
 * Controller for Student Portal Learning Hub & Dashboard
 */
class DashboardController extends Controller
{
    private StudentRepository $studentRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;
    private AssignmentService $assignmentService;
    private QuizService $quizService;
    private AttendanceRepository $attendanceRepo;
    private TimetableRepository $timetableRepo;
    private AnnouncementRepository $announcementRepo;
    private LiveClassRepository $liveClassRepo;
    private BadgeRepository $badgeRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?StudentRepository $studentRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?EnrollmentRepository $enrollmentRepo = null,
        ?AssignmentService $assignmentService = null,
        ?QuizService $quizService = null,
        ?AttendanceRepository $attendanceRepo = null,
        ?TimetableRepository $timetableRepo = null,
        ?AnnouncementRepository $announcementRepo = null,
        ?LiveClassRepository $liveClassRepo = null,
        ?BadgeRepository $badgeRepo = null
    ) {
        parent::__construct($authenticator);
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->enrollmentRepo = $enrollmentRepo ?? new EnrollmentRepository();
        $this->assignmentService = $assignmentService ?? new AssignmentService();
        $this->quizService = $quizService ?? new QuizService();
        $this->attendanceRepo = $attendanceRepo ?? new AttendanceRepository();
        $this->timetableRepo = $timetableRepo ?? new TimetableRepository();
        $this->announcementRepo = $announcementRepo ?? new AnnouncementRepository();
        $this->liveClassRepo = $liveClassRepo ?? new LiveClassRepository();
        $this->badgeRepo = $badgeRepo ?? new BadgeRepository();
    }

    /**
     * Display student portal dashboard overview.
     * Route: GET /student/dashboard
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $student = $this->studentRepo->findByUserId($userContext->id);

        if (!$student && !$userContext->isAdmin()) {
            return Response::forbidden('Student profile not found.');
        }

        // Academic state
        $activeSession = $this->academicRepo->findCurrentSession();
        $activeTerm = $this->academicRepo->findCurrentTerm();
        $sessionId = $activeSession ? $activeSession->id : 0;
        $termId = $activeTerm ? $activeTerm->id : 0;

        // Enrolled subject courses
        $enrolledSubjects = ($student && $sessionId > 0)
            ? $this->enrollmentRepo->getStudentSubjectEnrollments($student->id, $sessionId)
            : [];

        // Assignments
        $assignmentsData = [];
        try {
            $assignmentsData = $this->assignmentService->getStudentAssignments($userContext, $termId > 0 ? $termId : null);
        } catch (\Throwable) {
            $assignmentsData = ['active' => [], 'past_due' => [], 'submissions' => []];
        }

        $activeAssignments = $assignmentsData['active'] ?? [];
        $pastDueAssignments = $assignmentsData['past_due'] ?? [];

        // CBT Quizzes
        $quizzesData = [];
        try {
            $quizzesData = $this->quizService->getStudentQuizzes($userContext);
        } catch (\Throwable) {
            $quizzesData = ['active' => [], 'completed' => []];
        }

        $activeQuizzes = $quizzesData['active'] ?? [];

        // Attendance summary
        $attendanceSummary = ($student && $termId > 0)
            ? $this->attendanceRepo->getStudentAttendanceSummary($student->id, $termId)
            : ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'total' => 0, 'percentage' => 100.0];

        // Today's Timetable slots
        $dayMap = [
            'monday' => 'mon',
            'tuesday' => 'tue',
            'wednesday' => 'wed',
            'thursday' => 'thu',
            'friday' => 'fri',
            'saturday' => 'sat',
            'sunday' => 'sun',
        ];
        $currentDayKey = $dayMap[strtolower(date('l'))] ?? 'mon';

        $todaySlots = [];
        if ($student && $student->currentClassId && $termId > 0) {
            $allSlots = $this->timetableRepo->findByClass($student->currentClassId, $termId);
            $todaySlots = array_values(array_filter($allSlots, function ($slot) use ($currentDayKey) {
                return strtolower($slot->dayOfWeek) === $currentDayKey;
            }));
        }

        // Announcements feed
        $announcements = $this->announcementRepo->getFeedForUser($userContext, null, 4);

        // Upcoming and Live Online Classes (SRS §31)
        $upcomingLiveClasses = [];
        $earnedBadges = [];
        if ($student) {
            try {
                $upcomingLiveClasses = $this->liveClassRepo->getUpcomingForStudent($student->id, 3);
            } catch (\Throwable) {
                $upcomingLiveClasses = [];
            }

            try {
                $earnedBadges = $this->badgeRepo->getStudentBadges($student->id);
            } catch (\Throwable) {
                $earnedBadges = [];
            }
        }

        return Response::html($this->render('student/dashboard/index', [
            'title' => 'Student Dashboard — Claret Learning Portal',
            'headerTitle' => 'Student Learning Portal',
            'user' => $userContext,
            'student' => $student,
            'activeSession' => $activeSession,
            'activeTerm' => $activeTerm,
            'enrolledSubjects' => $enrolledSubjects,
            'activeAssignments' => $activeAssignments,
            'pastDueAssignments' => $pastDueAssignments,
            'activeQuizzes' => $activeQuizzes,
            'attendanceSummary' => $attendanceSummary,
            'todaySlots' => $todaySlots,
            'todayDayName' => date('l'),
            'announcements' => $announcements,
            'upcomingLiveClasses' => $upcomingLiveClasses,
            'earnedBadges' => $earnedBadges,
        ], 'layouts/student'));
    }
}
