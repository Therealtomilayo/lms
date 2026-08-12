<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\Controller;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\TeacherRepository;
use App\Services\AttendanceService;

class AttendanceController extends Controller
{
    private AttendanceService $attendanceService;
    private AcademicRepository $academicRepo;
    private TeacherRepository $teacherRepo;

    public function __construct(
        ?AttendanceService $attendanceService = null,
        ?AcademicRepository $academicRepo = null,
        ?TeacherRepository $teacherRepo = null
    ) {
        $this->attendanceService = $attendanceService ?? new AttendanceService();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository();
    }

    public function index(Request $request): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $teacher = $this->teacherRepo->findByUserId($user->getUserId());
        if (!$teacher) {
            return Response::forbidden('Teacher profile not found.');
        }

        $currentSession = $this->academicRepo->getCurrentSession();
        $allocations = $currentSession ? $this->teacherRepo->getTeachingAllocations($teacher->id, $currentSession->id) : [];

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
            'title' => 'Attendance Management — Teacher Portal',
            'classes' => array_values($classes),
            'allocations' => $allocations,
            'today' => date('Y-m-d'),
        ], 'layouts/teacher'));
    }

    public function form(Request $request): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $classId = (int)$request->getRouteParam('classId', 0);
        $date = (string)$request->getRouteParam('date', date('Y-m-d'));
        $classSubjectId = $request->getQuery('class_subject_id') ? (int)$request->getQuery('class_subject_id') : null;
        $periodNumber = $request->getQuery('period_number') ? (int)$request->getQuery('period_number') : null;

        $class = $this->academicRepo->findClassById($classId);
        if (!$class) {
            return Response::notFound('Class not found.');
        }

        try {
            $roster = $this->attendanceService->getRoster(
                classId: $classId,
                date: $date,
                classSubjectId: $classSubjectId,
                periodNumber: $periodNumber,
                user: $user
            );
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        }

        $classSubject = $classSubjectId ? $this->academicRepo->findClassSubjectById($classSubjectId) : null;

        return Response::html($this->render('teacher/attendance/form', [
            'title' => "Mark Attendance - {$class->name} ({$date})",
            'class' => $class,
            'date' => $date,
            'classSubjectId' => $classSubjectId,
            'classSubject' => $classSubject,
            'periodNumber' => $periodNumber,
            'roster' => $roster,
            'csrf_token' => $request->getSession()->get('_csrf_token', ''),
        ], 'layouts/teacher'));
    }

    public function store(Request $request): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $classId = (int)$request->getRouteParam('classId', 0);
        $date = (string)$request->getRouteParam('date', date('Y-m-d'));
        $classSubjectId = $request->getBodyParam('class_subject_id') ? (int)$request->getBodyParam('class_subject_id') : null;
        $periodNumber = $request->getBodyParam('period_number') ? (int)$request->getBodyParam('period_number') : null;
        $statuses = (array)$request->getBodyParam('status', []);
        $correctionReason = $request->getBodyParam('correction_reason') ? (string)$request->getBodyParam('correction_reason') : null;

        $records = [];
        foreach ($statuses as $studentId => $status) {
            $records[] = [
                'student_id' => (int)$studentId,
                'status' => (string)$status,
            ];
        }

        try {
            $this->attendanceService->recordRoster(
                classId: $classId,
                date: $date,
                classSubjectId: $classSubjectId,
                periodNumber: $periodNumber,
                records: $records,
                user: $user,
                correctionReason: $correctionReason
            );

            $this->setFlash($request, 'success', 'Attendance saved successfully.');
            $redirectUrl = "/teacher/attendance/{$classId}/{$date}";
            if ($classSubjectId) {
                $redirectUrl .= "?class_subject_id={$classSubjectId}";
                if ($periodNumber) {
                    $redirectUrl .= "&period_number={$periodNumber}";
                }
            }
            return Response::redirect($redirectUrl);
        } catch (ValidationException $e) {
            $this->setFlash($request, 'error', implode(' ', $e->getErrors()));
            return Response::redirect("/teacher/attendance/{$classId}/{$date}");
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        }
    }
}
