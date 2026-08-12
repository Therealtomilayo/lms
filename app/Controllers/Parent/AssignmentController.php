<?php

declare(strict_types=1);

namespace App\Controllers\Parent;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\AcademicRepository;
use App\Repositories\ParentRepository;
use App\Services\AssignmentService;

/**
 * Controller for Parent Read-Only Coursework & Outcome Tracking
 */
class AssignmentController extends Controller
{
    private AssignmentService $assignmentService;
    private ParentRepository $parentRepository;
    private AcademicRepository $academicRepository;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?AssignmentService $assignmentService = null,
        ?ParentRepository $parentRepository = null,
        ?AcademicRepository $academicRepository = null
    ) {
        parent::__construct($authenticator);
        $this->assignmentService = $assignmentService ?? new AssignmentService();
        $this->parentRepository = $parentRepository ?? new ParentRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
    }

    /**
     * View coursework and grading outcomes for a linked child.
     * Route: GET /parent/children/{studentId}/assignments
     */
    public function index(Request $request, array|string|int $params = []): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $studentId = is_array($params) ? (int)($params['studentId'] ?? 0) : (int)$params;
        if ($studentId <= 0) {
            $studentId = (int)($request->getAttribute('studentId') ?? $request->query('student_id', 0));
        }

        try {
            $termId = $request->query('term_id') !== null ? (int)$request->query('term_id') : null;
            $data = $this->assignmentService->getParentChildAssignments($studentId, $userContext, $termId);
            $activeSession = $this->academicRepository->getCurrentSession();

            $parent = $this->parentRepository->findByUserId($userContext->getUserId());
            $children = $parent ? $this->parentRepository->getLinkedStudents($parent->id) : [];

            Session::start();
            Session::set('_selected_child_id', $studentId);

            return Response::html($this->render('parent/assignments/index', [
                'title' => "{$data['student']->name}'s Coursework — Guardian Portal",
                'student' => $data['student'],
                'selectedChild' => $data['student'],
                'children' => $children,
                'assignments' => $data['assignments'],
                'submissions' => $data['submissions'],
                'activeSession' => $activeSession,
                'user' => $userContext,
            ], 'layouts/parent'));
        } catch (AuthorizationException $e) {
            return Response::html('<h1>403 Forbidden</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 403);
        } catch (ResourceNotFoundException $e) {
            return Response::html('<h1>404 Not Found</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 404);
        }
    }
}
