<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\LiveClassRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Services\LiveClassService;

/**
 * Controller for Student Live Online Classes Hub (SRS §31)
 */
class LiveClassController extends Controller
{
    private LiveClassService $liveClassService;
    private StudentRepository $studentRepo;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?LiveClassService $liveClassService = null,
        ?StudentRepository $studentRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        parent::__construct($authenticator);
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();

        $this->liveClassService = $liveClassService ?? new LiveClassService(
            liveClassRepository: new LiveClassRepository(),
            teacherRepository: new TeacherRepository(),
            studentRepository: $this->studentRepo,
            parentRepository: new ParentRepository(),
            academicRepository: $this->academicRepo,
            enrollmentRepository: new EnrollmentRepository()
        );
    }

    /**
     * View all scheduled and past live classes for student.
     * Route: GET /student/live-classes
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $student = $this->studentRepo->findByUserId($userContext->id);

        if (!$student) {
            return $this->redirectWithError('/student/dashboard', 'Student profile not found.');
        }

        $currentSession = $this->academicRepo->getCurrentSession();
        $currentTerm = $this->academicRepo->getCurrentTerm();

        $result = $this->liveClassService->getStudentLiveClasses(
            studentUserId: $userContext->id,
            sessionId: $currentSession?->id,
            termId: $currentTerm?->id
        );

        $classes = $result->isSuccess() ? $result->data : [];

        // Partition into upcoming and past
        $upcoming = [];
        $past = [];

        foreach ($classes as $lc) {
            if ($lc->isJoinable() || ($lc->isScheduled() && $lc->scheduledDate >= date('Y-m-d'))) {
                $upcoming[] = $lc;
            } else {
                $past[] = $lc;
            }
        }

        return Response::html($this->render('student/live_classes/index', [
            'title' => 'Live Online Classes Hub — Claret Student Portal',
            'headerTitle' => 'Live Online Classes',
            'user' => $userContext,
            'student' => $student,
            'currentSession' => $currentSession,
            'currentTerm' => $currentTerm,
            'upcomingClasses' => $upcoming,
            'pastClasses' => $past,
        ], 'layouts/student'));
    }

    /**
     * Join a live class session, log attendance, and redirect to external video meeting.
     * Route: GET /student/live-classes/{id}/join
     */
    public function join(Request $request, array|string|int|null $id = null): Response
    {
        $userContext = $this->requireAuthContext($request);
        $liveClassId = is_array($id) ? (int)($id['id'] ?? 0) : (int)($id ?: ($request->getAttribute('id') ?? 0));

        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        $result = $this->liveClassService->joinLiveClass(
            studentUserId: $userContext->id,
            liveClassId: $liveClassId,
            ipAddress: $ipAddress,
            userAgent: $userAgent
        );

        if (!$result->isSuccess()) {
            return $this->redirectWithError('/student/live-classes', $result->message ?? 'Unable to join live class.');
        }

        $meetingLink = $result->data['meeting_link'];
        return Response::redirect($meetingLink);
    }
}
