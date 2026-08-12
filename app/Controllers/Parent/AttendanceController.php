<?php

declare(strict_types=1);

namespace App\Controllers\Parent;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Policies\ParentPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\AttendanceRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;

class AttendanceController extends Controller
{
    private AttendanceRepository $attendanceRepo;
    private StudentRepository $studentRepo;
    private ParentRepository $parentRepo;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?AttendanceRepository $attendanceRepo = null,
        ?StudentRepository $studentRepo = null,
        ?ParentRepository $parentRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        parent::__construct($authenticator);
        $this->attendanceRepo = $attendanceRepo ?? new AttendanceRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->parentRepo = $parentRepo ?? new ParentRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

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
            return Response::html($this->render('parent/attendance/index', [
                'title' => 'Child Attendance — Guardian Portal',
                'children' => [],
                'selectedChild' => null,
                'summary' => null,
                'history' => [],
                'terms' => [],
                'user' => $user,
            ], 'layouts/parent'));
        }

        Session::start();
        $sessionSelectedId = Session::get('_selected_child_id');

        $routeParamId = is_array($params) ? (int)($params['studentId'] ?? 0) : (int)$params;
        $studentId = $routeParamId > 0 
            ? $routeParamId 
            : (int)($request->getAttribute('studentId') ?? $request->query('student_id', $sessionSelectedId ?: $children[0]->id));
        
        // Verify child link via policy
        if (!ParentPolicy::canViewAttendance($user, $studentId, $this->parentRepo)) {
            return Response::html('<h1>403 Forbidden</h1><p>You are not authorized to view attendance for this student.</p>', 403);
        }

        $selectedChild = null;
        foreach ($children as $c) {
            if ($c->id === $studentId) {
                $selectedChild = $c;
                break;
            }
        }

        Session::set('_selected_child_id', $studentId);

        $currentTerm = $this->academicRepo->getCurrentTerm();
        $termId = (int)$request->query('term_id', $currentTerm ? $currentTerm->id : 0);
        $terms = $this->academicRepo->getAllTerms();

        $history = $termId > 0 
            ? $this->attendanceRepo->getStudentAttendanceForTerm($studentId, $termId)
            : $this->attendanceRepo->getStudentAttendanceHistory($studentId, 50);

        $summary = $termId > 0
            ? $this->attendanceRepo->getStudentTermSummary($studentId, $termId)
            : null;

        return Response::html($this->render('parent/attendance/index', [
            'title' => 'Child Attendance — Guardian Portal',
            'children' => $children,
            'selectedChild' => $selectedChild ?? $children[0],
            'selectedTermId' => $termId,
            'summary' => $summary,
            'history' => $history,
            'terms' => $terms,
            'user' => $user,
        ], 'layouts/parent'));
    }
}
