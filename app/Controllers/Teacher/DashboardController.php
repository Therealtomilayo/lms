<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Repositories\AcademicRepository;
use App\Repositories\AnnouncementRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\TimetableRepository;
use App\Services\AssignmentService;
use App\Services\QuizService;

/**
 * Controller for Teacher Portal Instructional Overview & Command Center
 */
class DashboardController extends Controller
{
    private TeacherRepository $teacherRepository;
    private AcademicRepository $academicRepository;
    private AssignmentService $assignmentService;
    private QuizService $quizService;
    private TimetableRepository $timetableRepository;
    private AnnouncementRepository $announcementRepository;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?TeacherRepository $teacherRepository = null,
        ?AcademicRepository $academicRepository = null,
        ?AssignmentService $assignmentService = null,
        ?QuizService $quizService = null,
        ?TimetableRepository $timetableRepository = null,
        ?AnnouncementRepository $announcementRepository = null
    ) {
        parent::__construct($authenticator);
        $this->teacherRepository = $teacherRepository ?? new TeacherRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->assignmentService = $assignmentService ?? new AssignmentService();
        $this->quizService = $quizService ?? new QuizService();
        $this->timetableRepository = $timetableRepository ?? new TimetableRepository();
        $this->announcementRepository = $announcementRepository ?? new AnnouncementRepository();
    }

    public function index(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $teacher = $this->teacherRepository->findTeacherByUserId($userContext->id);
        $teacherId = $teacher ? $teacher->id : ($userContext->getTeacherId() ?: 0);

        // Academic state
        $currentSession = $this->academicRepository->getCurrentSession();
        $currentTerm = $this->academicRepository->getCurrentTerm();

        // Assigned class-subjects
        $classSubjects = [];
        if ($teacherId > 0) {
            $classSubjects = $this->academicRepository->getClassSubjectsByTeacher($teacherId, $currentSession?->id);
        } elseif ($userContext->hasAnyRole(['super_admin', 'admin'])) {
            $classSubjects = $this->academicRepository->getAllClassSubjects($currentSession?->id);
        }

        // Teacher Coursework & Assessments
        $assignments = [];
        try {
            $assignments = $this->assignmentService->getTeacherAssignments($userContext, $currentSession?->id);
        } catch (\Throwable) {
            $assignments = [];
        }

        $quizzes = [];
        try {
            $quizzes = $this->quizService->getTeacherQuizzes($userContext);
        } catch (\Throwable) {
            $quizzes = [];
        }

        // Today's Timetable Schedule
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

        $allSlots = $teacherId > 0 ? $this->timetableRepository->findByTeacher($teacherId, $currentTerm?->id) : [];
        $todaySlots = array_values(array_filter($allSlots, function ($slot) use ($currentDayKey) {
            return strtolower($slot->dayOfWeek) === $currentDayKey;
        }));

        // Announcements feed
        $announcements = $this->announcementRepository->getFeedForUser($userContext, null, 4);

        return Response::html($this->render('teacher/dashboard/index', [
            'title' => 'Teacher Dashboard — Claret Faculty Portal',
            'headerTitle' => 'Teacher Command Center',
            'userContext' => $userContext,
            'teacher' => $teacher,
            'currentSession' => $currentSession,
            'currentTerm' => $currentTerm,
            'classSubjects' => $classSubjects,
            'assignments' => $assignments,
            'quizzes' => $quizzes,
            'todaySlots' => $todaySlots,
            'todayDayName' => date('l'),
            'announcements' => $announcements,
        ], 'layouts/teacher'));
    }
}
