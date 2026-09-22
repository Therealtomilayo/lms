<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Services\AttendanceService;

class AttendanceController extends Controller
{
    private AttendanceService $attendanceService;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AttendanceService $attendanceService = null,
        ?AcademicRepository $academicRepo = null
    ) {
        $this->attendanceService = $attendanceService ?? new AttendanceService();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    public function index(Request $request): Response
    {
        $classes = $this->academicRepo->getAllClasses();
        $today = date('Y-m-d');
        $policy = $this->attendanceService->getAttendancePolicySettings();

        return Response::html($this->render('admin/attendance/index', [
            'title' => 'Attendance Management — Admin Overview',
            'headerTitle' => 'Attendance Oversight',
            'classes' => $classes,
            'today' => $today,
            'policy' => $policy,
            'csrf_token' => $request->getSession()->get('_csrf_token', ''),
        ], 'layouts/admin'));
    }

    public function edit(Request $request): Response
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

        return Response::html($this->render('admin/attendance/edit', [
            'title' => "Edit Attendance - {$class->name} ({$date})",
            'headerTitle' => 'Edit Class Attendance',
            'class' => $class,
            'date' => $date,
            'classSubjectId' => $classSubjectId,
            'periodNumber' => $periodNumber,
            'roster' => $roster,
            'csrf_token' => $request->getSession()->get('_csrf_token', ''),
        ], 'layouts/admin'));
    }

    public function update(Request $request, array|string|int|null $classId = null, array|string|null $date = null): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $cId = is_array($classId) ? (int)($classId['classId'] ?? $classId['id'] ?? 0) : (int)($classId ?? $request->getRouteParam('classId', 0));
        $markingDate = is_string($date) && !empty($date) ? $date : (string)$request->getRouteParam('date', date('Y-m-d'));
        $classSubjectId = $request->getBodyParam('class_subject_id') ? (int)$request->getBodyParam('class_subject_id') : null;
        $periodNumber = $request->getBodyParam('period_number') ? (int)$request->getBodyParam('period_number') : null;
        $statuses = (array)$request->getBodyParam('status', []);
        $correctionReason = trim((string)$request->getBodyParam('correction_reason', ''));

        if (empty($correctionReason)) {
            $this->setFlash($request, 'error', 'Audit justification is strictly mandatory for administrative attendance modifications.');
            return Response::redirect("/admin/attendance/{$cId}/{$markingDate}/edit");
        }

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
                user: $user,
                correctionReason: $correctionReason
            );

            $this->setFlash($request, 'success', 'Attendance records updated successfully with permanent audit trail.');
            return Response::redirect("/admin/attendance/{$cId}/{$markingDate}/edit");
        } catch (ValidationException $e) {
            $this->setFlash($request, 'error', implode(' ', $e->getErrors()));
            return Response::redirect("/admin/attendance/{$cId}/{$markingDate}/edit");
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        }
    }

    /**
     * Update school attendance policy configuration (SRS §26).
     */
    public function updatePolicy(Request $request): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $weightInput = $request->getBodyParam('late_weight');
        if ($weightInput === null || !is_numeric($weightInput)) {
            $this->setFlash($request, 'error', 'Please provide a valid numeric late attendance weight.');
            return Response::redirect('/admin/attendance');
        }

        $weight = (float)$weightInput;
        // If input as percentage (e.g. 60), convert to decimal
        if ($weight > 1.0) {
            $weight = $weight / 100.0;
        }

        try {
            $this->attendanceService->updateAttendancePolicy($weight, $user);
            $pct = round($weight * 100, 1);
            $this->setFlash($request, 'success', "Attendance calculation policy updated successfully. Late arrival credit is now set to {$pct}%.");
        } catch (\Throwable $e) {
            $this->setFlash($request, 'error', $e->getMessage());
        }

        return Response::redirect('/admin/attendance');
    }
}
