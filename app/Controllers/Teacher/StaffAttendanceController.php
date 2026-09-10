<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\StaffAttendanceRepository;
use App\Services\StaffAttendanceService;

/**
 * Controller for Teacher/Staff Geofenced Attendance Clock-In & Time Tracking (SRS §22, §23, §24)
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
     * Staff attendance clock-in terminal and personal history.
     * Route: GET /teacher/staff-attendance
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $userId = $userContext->id;

        $statusBundle = $this->attendanceService->getTodayStatus($userId);
        $history = $this->attendanceRepository->getAttendanceHistoryForUser($userId, 30);

        $session = $request->getSession();
        $flashSuccess = $session->getFlash('success');
        $flashError = $session->getFlash('error');

        return Response::html($this->render('teacher/staff_attendance/index', [
            'title' => 'Staff Geofenced Attendance — Claret Faculty Portal',
            'headerTitle' => 'Staff Geofenced Attendance',
            'userContext' => $userContext,
            'role' => 'teacher',
            'roleLabel' => 'Teacher Portal',
            'statusBundle' => $statusBundle,
            'history' => $history,
            'csrf_token' => \App\Core\Csrf::getToken(),
            'flash_success' => is_string($flashSuccess) ? $flashSuccess : null,
            'flash_error' => is_string($flashError) ? $flashError : null,
        ], 'layouts/teacher'));
    }

    /**
     * Submit Geofenced Clock-In.
     * Route: POST /teacher/staff-attendance/clock-in
     */
    public function clockIn(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $userId = $userContext->id;

        $lat = (float)($request->input('latitude') ?? 0.0);
        $lng = (float)($request->input('longitude') ?? 0.0);
        $fingerprint = $request->input('device_fingerprint');
        $notes = $request->input('notes');

        $ip = $request->clientIp();
        $userAgent = $request->userAgent();

        $result = $this->attendanceService->clockIn(
            $userId,
            $lat,
            $lng,
            $fingerprint,
            $ip,
            $userAgent,
            $notes
        );

        $isJson = $request->isAjax();

        if ($result->isSuccess()) {
            if ($isJson) {
                return $this->json([
                    'success' => true,
                    'message' => $result->getMessage(),
                    'data' => $result->getData(),
                ]);
            }
            return $this->redirectWithSuccess('/teacher/staff-attendance', $result->getMessage());
        }

        if ($isJson) {
            return $this->json([
                'success' => false,
                'message' => $result->getMessage(),
            ], 422);
        }

        return $this->redirectWithError('/teacher/staff-attendance', $result->getMessage());
    }

    /**
     * Submit Clock-Out.
     * Route: POST /teacher/staff-attendance/clock-out
     */
    public function clockOut(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $userId = $userContext->id;

        $lat = (float)($request->input('latitude') ?? 0.0);
        $lng = (float)($request->input('longitude') ?? 0.0);
        $fingerprint = $request->input('device_fingerprint');
        $notes = $request->input('notes');

        $ip = $request->clientIp();
        $userAgent = $request->userAgent();

        $result = $this->attendanceService->clockOut(
            $userId,
            $lat,
            $lng,
            $fingerprint,
            $ip,
            $userAgent,
            $notes
        );

        $isJson = $request->isAjax();

        if ($result->isSuccess()) {
            if ($isJson) {
                return $this->json([
                    'success' => true,
                    'message' => $result->getMessage(),
                    'data' => $result->getData(),
                ]);
            }
            return $this->redirectWithSuccess('/teacher/staff-attendance', $result->getMessage());
        }

        if ($isJson) {
            return $this->json([
                'success' => false,
                'message' => $result->getMessage(),
            ], 422);
        }

        return $this->redirectWithError('/teacher/staff-attendance', $result->getMessage());
    }

    /**
     * Batch sync offline captured attendance records.
     * Route: POST /teacher/staff-attendance/sync
     */
    public function syncOffline(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $userId = $userContext->id;

        $records = $request->input('records') ?? [];
        if (is_string($records)) {
            $records = json_decode($records, true) ?: [];
        }

        $ip = $request->clientIp();
        $userAgent = $request->userAgent();

        $res = $this->attendanceService->syncOfflineRecords($userId, $records, $ip, $userAgent);

        return $this->json([
            'success' => true,
            'synced_count' => count($res['synced']),
            'failed_count' => count($res['failed']),
            'details' => $res,
        ]);
    }

    /**
     * Live Status Check API.
     * Route: GET /api/staff-attendance/status
     */
    public function status(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $status = $this->attendanceService->getTodayStatus($userContext->id);

        return $this->json([
            'success' => true,
            'status' => $status,
        ]);
    }
}
