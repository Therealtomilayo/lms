<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

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
 * Controller for Teacher Live Online Class Scheduling (SRS §31)
 */
class LiveClassController extends Controller
{
    private LiveClassService $liveClassService;
    private TeacherRepository $teacherRepo;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?LiveClassService $liveClassService = null,
        ?TeacherRepository $teacherRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        parent::__construct($authenticator);
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();

        $this->liveClassService = $liveClassService ?? new LiveClassService(
            liveClassRepository: new LiveClassRepository(),
            teacherRepository: $this->teacherRepo,
            studentRepository: new StudentRepository(),
            parentRepository: new ParentRepository(),
            academicRepository: $this->academicRepo,
            enrollmentRepository: new EnrollmentRepository()
        );
    }

    /**
     * Display live classes hub for teacher.
     * Route: GET /teacher/live-classes
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $teacher = $this->teacherRepo->findTeacherByUserId($userContext->id);

        if (!$teacher) {
            return $this->redirectWithError('/teacher/dashboard', 'Teacher profile not found.');
        }

        $currentSession = $this->academicRepo->getCurrentSession();
        $currentTerm = $this->academicRepo->getCurrentTerm();

        $classSubjects = $this->academicRepo->findClassSubjectsByTeacherId($teacher->id);
        $result = $this->liveClassService->getTeacherLiveClasses(
            teacherUserId: $userContext->id,
            sessionId: $currentSession?->id,
            termId: $currentTerm?->id
        );

        $liveClasses = $result->isSuccess() ? $result->data : [];

        return Response::html($this->render('teacher/live_classes/index', [
            'title' => 'Live Online Classes — Faculty Scheduling Hub',
            'headerTitle' => 'Live Online Classes',
            'user' => $userContext,
            'teacher' => $teacher,
            'currentSession' => $currentSession,
            'currentTerm' => $currentTerm,
            'classSubjects' => $classSubjects,
            'liveClasses' => $liveClasses,
        ], 'layouts/teacher'));
    }

    /**
     * Schedule a new live class.
     * Route: POST /teacher/live-classes
     */
    public function store(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $data = $request->all();

        $result = $this->liveClassService->createLiveClass($userContext->id, $data);

        if (!$result->isSuccess()) {
            return $this->redirectWithErrors('/teacher/live-classes', $result->errors, $data);
        }

        return $this->redirectWithSuccess('/teacher/live-classes', $result->message ?? 'Live class scheduled successfully.');
    }

    /**
     * Update an existing live class.
     * Route: POST /teacher/live-classes/{id}/update
     */
    public function update(Request $request, array|string|int|null $id = null): Response
    {
        $userContext = $this->requireAuthContext($request);
        $liveClassId = is_array($id) ? (int)($id['id'] ?? 0) : (int)($id ?: ($request->getAttribute('id') ?? 0));
        $data = $request->all();

        $result = $this->liveClassService->updateLiveClass($userContext->id, $liveClassId, $data, false);

        if (!$result->isSuccess()) {
            return $this->redirectWithErrors('/teacher/live-classes', $result->errors, $data);
        }

        return $this->redirectWithSuccess('/teacher/live-classes', $result->message ?? 'Live class updated successfully.');
    }

    /**
     * Start live class (mark in_progress).
     * Route: POST /teacher/live-classes/{id}/start
     */
    public function start(Request $request, array|string|int|null $id = null): Response
    {
        $userContext = $this->requireAuthContext($request);
        $liveClassId = is_array($id) ? (int)($id['id'] ?? 0) : (int)($id ?: ($request->getAttribute('id') ?? 0));

        $result = $this->liveClassService->startLiveClass($userContext->id, $liveClassId, false);

        if (!$result->isSuccess()) {
            return $this->redirectWithError('/teacher/live-classes', $result->message ?? 'Unable to start class.');
        }

        return $this->redirectWithSuccess('/teacher/live-classes', $result->message ?? 'Live class is now active.');
    }

    /**
     * End live class (mark completed).
     * Route: POST /teacher/live-classes/{id}/end
     */
    public function end(Request $request, array|string|int|null $id = null): Response
    {
        $userContext = $this->requireAuthContext($request);
        $liveClassId = is_array($id) ? (int)($id['id'] ?? 0) : (int)($id ?: ($request->getAttribute('id') ?? 0));

        $result = $this->liveClassService->endLiveClass($userContext->id, $liveClassId, false);

        if (!$result->isSuccess()) {
            return $this->redirectWithError('/teacher/live-classes', $result->message ?? 'Unable to conclude class.');
        }

        return $this->redirectWithSuccess('/teacher/live-classes', $result->message ?? 'Live class concluded.');
    }

    /**
     * Cancel live class.
     * Route: POST /teacher/live-classes/{id}/cancel
     */
    public function cancel(Request $request, array|string|int|null $id = null): Response
    {
        $userContext = $this->requireAuthContext($request);
        $liveClassId = is_array($id) ? (int)($id['id'] ?? 0) : (int)($id ?: ($request->getAttribute('id') ?? 0));

        $result = $this->liveClassService->cancelLiveClass($userContext->id, $liveClassId, false);

        if (!$result->isSuccess()) {
            return $this->redirectWithError('/teacher/live-classes', $result->message ?? 'Unable to cancel class.');
        }

        return $this->redirectWithSuccess('/teacher/live-classes', $result->message ?? 'Live class cancelled.');
    }

    /**
     * Delete live class.
     * Route: POST /teacher/live-classes/{id}/delete
     */
    public function destroy(Request $request, array|string|int|null $id = null): Response
    {
        $userContext = $this->requireAuthContext($request);
        $liveClassId = is_array($id) ? (int)($id['id'] ?? 0) : (int)($id ?: ($request->getAttribute('id') ?? 0));

        $result = $this->liveClassService->deleteLiveClass($userContext->id, $liveClassId, false);

        if (!$result->isSuccess()) {
            return $this->redirectWithError('/teacher/live-classes', $result->message ?? 'Unable to delete class.');
        }

        return $this->redirectWithSuccess('/teacher/live-classes', $result->message ?? 'Live class deleted.');
    }

    /**
     * View attendee register.
     * Route: GET /teacher/live-classes/{id}/attendees
     */
    public function attendees(Request $request, array|string|int|null $id = null): Response
    {
        $userContext = $this->requireAuthContext($request);
        $liveClassId = is_array($id) ? (int)($id['id'] ?? 0) : (int)($id ?: ($request->getAttribute('id') ?? 0));

        $result = $this->liveClassService->getLiveClassAttendees($liveClassId, $userContext->id, false);

        if (!$result->isSuccess()) {
            return $this->redirectWithError('/teacher/live-classes', $result->message ?? 'Unable to load attendees.');
        }

        return Response::html($this->render('teacher/live_classes/attendees', [
            'title' => 'Live Class Attendance Register',
            'headerTitle' => 'Class Attendees',
            'user' => $userContext,
            'liveClass' => $result->data['live_class'],
            'attendees' => $result->data['attendees'],
        ], 'layouts/teacher'));
    }
}
