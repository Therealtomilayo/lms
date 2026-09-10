<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

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
 * Controller for Admin Institutional Live Class Oversight & Auditing (SRS §31)
 */
class LiveClassController extends Controller
{
    private LiveClassService $liveClassService;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?LiveClassService $liveClassService = null,
        ?AcademicRepository $academicRepo = null
    ) {
        parent::__construct($authenticator);
        $this->academicRepo = $academicRepo ?? new AcademicRepository();

        $this->liveClassService = $liveClassService ?? new LiveClassService(
            liveClassRepository: new LiveClassRepository(),
            teacherRepository: new TeacherRepository(),
            studentRepository: new StudentRepository(),
            parentRepository: new ParentRepository(),
            academicRepository: $this->academicRepo,
            enrollmentRepository: new EnrollmentRepository()
        );
    }

    /**
     * Institutional directory of all live classes with filtering and auditing.
     * Route: GET /admin/live-classes
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);

        $sessions = $this->academicRepo->getAllSessions();
        $terms = $this->academicRepo->getAllTerms();

        $sessionId = $request->get('session_id') ? (int)$request->get('session_id') : null;
        $termId = $request->get('term_id') ? (int)$request->get('term_id') : null;
        $status = $request->get('status') ? (string)$request->get('status') : null;
        $page = max(1, (int)($request->get('page') ?? 1));

        $result = $this->liveClassService->getAdminLiveClasses(
            sessionId: $sessionId,
            termId: $termId,
            status: $status,
            page: $page,
            perPage: 25
        );

        $data = $result->isSuccess() ? $result->data : [
            'classes' => [],
            'total' => 0,
            'page' => 1,
            'per_page' => 25,
            'total_pages' => 1,
        ];

        return Response::html($this->render('admin/live_classes/index', [
            'title' => 'Live Online Classes Register — Claret Admin',
            'headerTitle' => 'Live Online Classes Register',
            'user' => $userContext,
            'sessions' => $sessions,
            'terms' => $terms,
            'selectedSessionId' => $sessionId,
            'selectedTermId' => $termId,
            'selectedStatus' => $status,
            'liveClasses' => $data['classes'],
            'total' => $data['total'],
            'page' => $data['page'],
            'totalPages' => $data['total_pages'],
        ], 'layouts/admin'));
    }

    /**
     * Cancel a live class.
     * Route: POST /admin/live-classes/{id}/cancel
     */
    public function cancel(Request $request, array|string|int $id = null): Response
    {
        $userContext = $this->requireAuthContext($request);
        $liveClassId = is_array($id) ? (int)($id['id'] ?? 0) : (int)($id ?: ($request->getAttribute('id') ?? 0));

        $result = $this->liveClassService->cancelLiveClass($userContext->id, $liveClassId, true);

        if (!$result->isSuccess()) {
            return $this->redirectWithError('/admin/live-classes', $result->message ?? 'Unable to cancel class.');
        }

        return $this->redirectWithSuccess('/admin/live-classes', 'Live class cancelled by administrative action.');
    }

    /**
     * Delete a live class.
     * Route: POST /admin/live-classes/{id}/delete
     */
    public function destroy(Request $request, array|string|int $id = null): Response
    {
        $userContext = $this->requireAuthContext($request);
        $liveClassId = is_array($id) ? (int)($id['id'] ?? 0) : (int)($id ?: ($request->getAttribute('id') ?? 0));

        $result = $this->liveClassService->deleteLiveClass($userContext->id, $liveClassId, true);

        if (!$result->isSuccess()) {
            return $this->redirectWithError('/admin/live-classes', $result->message ?? 'Unable to delete class.');
        }

        return $this->redirectWithSuccess('/admin/live-classes', 'Live class deleted successfully.');
    }

    /**
     * View attendee register.
     * Route: GET /admin/live-classes/{id}/attendees
     */
    public function attendees(Request $request, array|string|int $id = null): Response
    {
        $userContext = $this->requireAuthContext($request);
        $liveClassId = is_array($id) ? (int)($id['id'] ?? 0) : (int)($id ?: ($request->getAttribute('id') ?? 0));

        $result = $this->liveClassService->getLiveClassAttendees($liveClassId, $userContext->id, true);

        if (!$result->isSuccess()) {
            return $this->redirectWithError('/admin/live-classes', $result->message ?? 'Unable to load attendees.');
        }

        return Response::html($this->render('admin/live_classes/attendees', [
            'title' => 'Live Class Attendance Audit Register',
            'headerTitle' => 'Attendee Audit Log',
            'user' => $userContext,
            'liveClass' => $result->data['live_class'],
            'attendees' => $result->data['attendees'],
        ], 'layouts/admin'));
    }
}
