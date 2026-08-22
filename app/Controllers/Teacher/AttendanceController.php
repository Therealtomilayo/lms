<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\TeacherRepository;
use App\Services\AttendanceService;

/**
 * Controller for Teacher Daily & Subject Attendance Roll Call
 */
class AttendanceController extends Controller
{
    private AttendanceService $attendanceService;
    private AcademicRepository $academicRepo;
    private TeacherRepository $teacherRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?AttendanceService $attendanceService = null,
        ?AcademicRepository $academicRepo = null,
        ?TeacherRepository $teacherRepo = null
    ) {
        parent::__construct($authenticator);
        $this->attendanceService = $attendanceService ?? new AttendanceService();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository();
    }

    /**
     * Attendance Overview / Directory of allocated classes and subjects.
     * Route: GET /teacher/attendance
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $teacher = $this->teacherRepo->findTeacherByUserId($userContext->id);

        if (!$teacher && !$userContext->isAdmin()) {
            return Response::forbidden('Teacher profile not found.');
        }

        $teacherId = $teacher ? $teacher->id : 0;
        $currentSession = $this->academicRepo->findCurrentSession();
        $allocations = ($currentSession && $teacherId > 0)
            ? $this->teacherRepo->getTeachingAllocations($teacherId, $currentSession->id)
            : [];

        // Collect distinct classes
        $classes = [];
        foreach ($allocations as $alloc) {
            $classes[$alloc['class_id']] = [
                'id' => $alloc['class_id'],
                'name' => $alloc['class_name'],
                'level_name' => $alloc['academic_level_name'] ?? '',
            ];
        }

        return Response::html($this->render('teacher/attendance/index', [
            'title' => 'Attendance Management — Faculty Portal',
            'headerTitle' => 'Daily & Subject Attendance Register',
            'user' => $userContext,
            'classes' => array_values($classes),
            'allocations' => $allocations,
            'currentSession' => $currentSession,
            'today' => date('Y-m-d'),
        ], 'layouts/teacher'));
    }

    /**
     * Show attendance marking roll-call sheet.
     * Route: GET /teacher/attendance/{classId}/{date}
     */
    public function form(Request $request, array|string|int|null $classId = null, array|string|null $date = null): Response
    {
        $userContext = $this->requireAuthContext($request);
        
        $cId = is_array($classId) ? (int)($classId['classId'] ?? $classId['id'] ?? 0) : (int)($classId ?? $request->getRouteParam('classId', 0));
        $today = date('Y-m-d');
        // Teachers are strictly restricted to today's date only
        $markingDate = $today;

        $classSubjectId = (int)$request->get('class_subject_id', 0) ?: null;
        $periodNumber = (int)$request->get('period_number', 0) ?: null;

        $class = $this->academicRepo->findClassById($cId);
        if (!$class) {
            return Response::notFound('Class cohort not found.');
        }

        try {
            $roster = $this->attendanceService->getRoster(
                classId: $cId,
                date: $markingDate,
                classSubjectId: $classSubjectId,
                periodNumber: $periodNumber,
                user: $userContext
            );
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        }

        $classSubject = $classSubjectId ? $this->academicRepo->findClassSubjectById($classSubjectId) : null;

        return Response::html($this->render('teacher/attendance/form', [
            'title' => "Mark Attendance — {$class->name} (" . date('M d, Y', strtotime($markingDate)) . ")",
            'headerTitle' => 'Student Attendance Register',
            'user' => $userContext,
            'class' => $class,
            'date' => $markingDate,
            'classSubjectId' => $classSubjectId,
            'classSubject' => $classSubject,
            'periodNumber' => $periodNumber,
            'roster' => $roster,
        ], 'layouts/teacher'));
    }

    /**
     * Persist attendance roll-call records.
     * Route: POST /teacher/attendance/{classId}/{date}
     */
    public function store(Request $request, array|string|int|null $classId = null, array|string|null $date = null): Response
    {
        $userContext = $this->requireAuthContext($request);
        
        $cId = is_array($classId) ? (int)($classId['classId'] ?? $classId['id'] ?? 0) : (int)($classId ?? $request->getRouteParam('classId', 0));
        $today = date('Y-m-d');
        $markingDate = $today;

        $data = $request->all();
        $classSubjectId = (int)($data['class_subject_id'] ?? 0) ?: null;
        $periodNumber = (int)($data['period_number'] ?? 0) ?: null;
        $statuses = (array)($data['status'] ?? []);
        $correctionReason = isset($data['correction_reason']) && trim((string)$data['correction_reason']) !== '' ? trim((string)$data['correction_reason']) : null;

        $records = [];
        foreach ($statuses as $studentId => $status) {
            $records[] = [
                'student_id' => (int)$studentId,
                'status' => (string)$status,
            ];
        }

        try {
            $this->attendanceService->recordRoster(
                classId: $cId,
                date: $markingDate,
                classSubjectId: $classSubjectId,
                periodNumber: $periodNumber,
                records: $records,
                user: $userContext,
                correctionReason: $correctionReason
            );

            $redirectUrl = "/teacher/attendance/{$cId}/{$today}";
            if ($classSubjectId) {
                $redirectUrl .= "?class_subject_id={$classSubjectId}";
                if ($periodNumber) {
                    $redirectUrl .= "&period_number={$periodNumber}";
                }
            }

            return $this->redirectWithSuccess($redirectUrl, 'Attendance register saved successfully.');
        } catch (ValidationException $e) {
            return $this->redirectWithError(
                "/teacher/attendance/{$cId}/{$today}",
                implode(' ', $e->getErrors())
            );
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        }
    }
}
