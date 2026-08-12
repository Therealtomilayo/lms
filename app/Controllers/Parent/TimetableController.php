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
use App\Policies\TimetablePolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Services\TimetableService;

/**
 * Parent Timetable Controller
 * Provides read-only view of a linked child's weekly schedule with child context switching and RBAC enforcement.
 */
class TimetableController extends Controller
{
    private TimetableService $timetableService;
    private ParentRepository $parentRepo;
    private StudentRepository $studentRepo;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?TimetableService $timetableService = null,
        ?ParentRepository $parentRepo = null,
        ?StudentRepository $studentRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        parent::__construct($authenticator);
        $this->timetableService = $timetableService ?? new TimetableService();
        $this->parentRepo = $parentRepo ?? new ParentRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    /**
     * View linked child's weekly timetable.
     */
    public function index(Request $request, array|string|int $params = []): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $parent = $this->parentRepo->findByUserId($user->getUserId());
        if (!$parent) {
            return Response::html('<h1>403 Forbidden</h1><p>Parent profile not found.</p>', 403);
        }

        $children = $this->parentRepo->getLinkedStudents($parent->id);
        if (empty($children)) {
            return Response::html($this->render('parent/timetable/index', [
                'title' => 'Child Timetable — Guardian Portal',
                'children' => [],
                'selectedChild' => null,
                'scheduleData' => null,
                'terms' => [],
                'selectedTerm' => null,
                'user' => $user,
            ], 'layouts/parent'));
        }

        Session::start();
        $sessionSelectedId = Session::get('_selected_child_id');

        $routeParamId = is_array($params) ? (int)($params['studentId'] ?? 0) : (int)$params;
        $studentId = $routeParamId > 0
            ? $routeParamId
            : (int)($request->getAttribute('studentId') ?? $request->getRouteParam('studentId', $request->query('student_id', $sessionSelectedId ?: $children[0]->id)));

        if ($studentId <= 0) {
            $studentId = (int)$children[0]->id;
        }

        // Verify child link via policy
        if (!TimetablePolicy::canViewStudentTimetable($user, $studentId, $this->studentRepo, $this->parentRepo)) {
            return Response::html('<h1>403 Forbidden</h1><p>You are not authorized to view the timetable for this student.</p>', 403);
        }

        $selectedChild = null;
        foreach ($children as $c) {
            if ((int)$c->id === $studentId) {
                $selectedChild = $c;
                break;
            }
        }

        Session::set('_selected_child_id', $studentId);

        $termId = $request->query('term_id') ? (int)$request->query('term_id') : null;
        $terms = $this->academicRepo->getAllTerms();
        $selectedTerm = null;

        if ($termId) {
            $selectedTerm = $this->academicRepo->findTermById($termId);
        }
        if (!$selectedTerm) {
            $selectedTerm = $this->academicRepo->findCurrentTerm() ?? $this->academicRepo->findActiveTerm() ?? (!empty($terms) ? $terms[0] : null);
        }

        try {
            $scheduleData = $this->timetableService->getStudentTimetable($studentId, $selectedTerm?->id, $user);
        } catch (AuthorizationException | ResourceNotFoundException $e) {
            return Response::html($this->render('parent/timetable/index', [
                'title' => 'Child Timetable — Guardian Portal',
                'children' => $children,
                'selectedChild' => $selectedChild ?? $children[0],
                'error' => $e->getMessage(),
                'scheduleData' => null,
                'terms' => $terms,
                'selectedTerm' => $selectedTerm,
                'user' => $user,
            ], 'layouts/parent'));
        }

        return Response::html($this->render('parent/timetable/index', [
            'title' => 'Child Timetable — Guardian Portal',
            'children' => $children,
            'selectedChild' => $selectedChild ?? $children[0],
            'scheduleData' => $scheduleData,
            'terms' => $terms,
            'selectedTerm' => $selectedTerm,
            'user' => $user,
        ], 'layouts/parent'));
    }
}
