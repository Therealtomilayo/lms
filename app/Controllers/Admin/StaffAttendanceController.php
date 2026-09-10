<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\StaffAttendanceRepository;
use App\Services\StaffAttendanceService;

/**
 * Controller for Administrative Staff Geofenced Attendance Oversight & Calibration (SRS §22, §23)
 */
class StaffAttendanceController extends Controller
{
    private StaffAttendanceRepository $attendanceRepository;
    private StaffAttendanceService $attendanceService;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?StaffAttendanceRepository $attendanceRepository = null,
        ?StaffAttendanceService $attendanceService = null
    ) {
        parent::__construct($authenticator);
        $pdo = Database::getInstance();
        $this->attendanceRepository = $attendanceRepository ?? new StaffAttendanceRepository($pdo);
        $academicRepo = new AcademicRepository($pdo);
        $this->attendanceService = $attendanceService ?? new StaffAttendanceService($this->attendanceRepository, $academicRepo, $pdo);
    }

    /**
     * Institutional Daily Staff Attendance Register & KPI Dashboard.
     * Route: GET /admin/staff-attendance
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('Only administrators can view the institutional staff attendance register.');
        }

        $date = $request->query('date') ?: date('Y-m-d');
        $status = $request->query('status');
        $status = !empty($status) ? (string)$status : null;
        $search = $request->query('q');
        $search = !empty($search) ? (string)$search : null;

        $summary = $this->attendanceRepository->getDailySummary($date);
        $register = $this->attendanceRepository->getDailyRegister($date, $status, $search);
        $geofenceConfig = $this->attendanceService->getGeofenceConfig();

        $session = $request->getSession();
        $flashSuccess = $session->getFlash('success');
        $flashError = $session->getFlash('error');

        return Response::html($this->render('admin/staff_attendance/index', [
            'title' => 'Staff Geofenced Attendance — Claret Admin Portal',
            'headerTitle' => 'Staff Attendance Register',
            'role' => 'admin',
            'roleLabel' => $userContext->getPrimaryRoleLabel(),
            'date' => $date,
            'status' => $status,
            'search' => $search,
            'summary' => $summary,
            'register' => $register,
            'config' => $geofenceConfig,
            'isSuperAdmin' => $userContext->hasRole('super_admin'),
            'csrf_token' => \App\Core\Csrf::getToken(),
            'flash_success' => is_string($flashSuccess) ? $flashSuccess : null,
            'flash_error' => is_string($flashError) ? $flashError : null,
        ], 'layouts/admin'));
    }

    /**
     * Geofence Violations & Security Fraud Audit Log.
     * Route: GET /admin/staff-attendance/breaches
     */
    public function breaches(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('Only administrators can inspect attendance security logs.');
        }

        $date = $request->query('date');
        $breaches = $this->attendanceRepository->getBreaches($date, 100);
        $config = $this->attendanceService->getGeofenceConfig();

        return Response::html($this->render('admin/staff_attendance/breaches', [
            'title' => 'Geofence Security Breaches — Claret Admin Portal',
            'headerTitle' => 'Attendance Security Logs',
            'role' => 'admin',
            'roleLabel' => $userContext->getPrimaryRoleLabel(),
            'date' => $date,
            'breaches' => $breaches,
            'config' => $config,
        ], 'layouts/admin'));
    }

    /**
     * Calibrate Campus Geofence Perimeter & Working Hours.
     * Route: POST /admin/staff-attendance/settings
     */
    public function updateSettings(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('Permission denied to calibrate institutional geofence perimeter.');
        }

        $config = [
            'latitude' => (float)$request->input('latitude', 9.0882),
            'longitude' => (float)$request->input('longitude', 7.4641),
            'radius_meters' => max(50, (int)$request->input('radius_meters', 250)),
            'workday_start_time' => $request->input('workday_start_time', '08:00'),
            'workday_late_threshold' => $request->input('workday_late_threshold', '08:15'),
            'workday_end_time' => $request->input('workday_end_time', '15:30'),
            'enforced' => (bool)$request->input('enforced', true),
        ];

        $this->attendanceService->updateGeofenceConfig($config, $userContext->id);
        return $this->redirectWithSuccess('/admin/staff-attendance', 'Campus geofence perimeter and workday hours updated successfully.');
    }

    /**
     * Export daily or monthly staff attendance register as CSV.
     * Route: GET /admin/staff-attendance/export
     */
    public function export(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('Only administrators can export attendance reports.');
        }

        $date = $request->query('date') ?: date('Y-m-d');
        $register = $this->attendanceRepository->getDailyRegister($date);

        $filename = "staff_attendance_{$date}.csv";

        $output = fopen('php://temp', 'r+');
        fputcsv($output, [
            'Date',
            'Staff Name',
            'Email',
            'Clock In Time',
            'Distance from Center (m)',
            'Clock Out Time',
            'Duration',
            'Status',
            'Late Arrival',
            'Early Departure',
            'IP Address',
            'Notes'
        ]);

        foreach ($register as $row) {
            fputcsv($output, [
                $row->attendanceDate,
                $row->staffName,
                $row->staffEmail,
                $row->getFormattedClockInTime(),
                $row->clockInDistanceMeters,
                $row->getFormattedClockOutTime() ?? 'Not Clocked Out',
                $row->getFormattedWorkDuration(),
                strtoupper($row->status),
                $row->isLate ? 'YES' : 'NO',
                $row->isEarlyDeparture ? 'YES' : 'NO',
                $row->ipAddress ?? 'N/A',
                $row->notes ?? ''
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return new Response(
            content: (string)$csvContent,
            statusCode: 200,
            headers: [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]
        );
    }

    /**
     * Manual Administrative Override/Correction.
     * Route: POST /admin/staff-attendance/override
     */
    public function manualCorrection(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('Only administrators can record attendance overrides.');
        }

        $userId = (int)$request->input('user_id');
        $date = (string)$request->input('date', date('Y-m-d'));
        $status = (string)$request->input('status', 'present');
        $notes = (string)$request->input('notes', 'Administrative Override by ' . $userContext->name);

        if (!$userId) {
            \App\Core\Session::flash('error', 'Invalid staff member selected.');
            return $this->redirect('/admin/staff-attendance?date=' . $date);
        }

        $this->attendanceRepository->manualCorrection($userId, $date, [
            'status' => $status,
            'is_late' => ($status === 'late'),
            'notes' => $notes,
        ]);

        \App\Core\Session::flash('success', 'Staff attendance updated manually.');
        return $this->redirect('/admin/staff-attendance?date=' . $date);
    }
}
