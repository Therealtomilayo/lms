<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Repositories\AcademicRepository;
use App\Repositories\AnnouncementRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\UserRepository;
use App\Services\HealthService;

/**
 * Controller for Admin Overview Command Center Dashboard
 */
class DashboardController extends Controller
{
    private UserRepository $userRepository;
    private AcademicRepository $academicRepository;
    private AuditLogRepository $auditLogRepository;
    private AnnouncementRepository $announcementRepository;
    private HealthService $healthService;

    public function __construct(
        ?UserRepository $userRepository = null,
        ?AcademicRepository $academicRepository = null,
        ?AuditLogRepository $auditLogRepository = null,
        ?AnnouncementRepository $announcementRepository = null,
        ?HealthService $healthService = null
    ) {
        $this->userRepository = $userRepository ?? new UserRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->auditLogRepository = $auditLogRepository ?? new AuditLogRepository();
        $this->announcementRepository = $announcementRepository ?? new AnnouncementRepository();
        $this->healthService = $healthService ?? new HealthService();
    }

    public function index(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('You are not authorized to view the administrative overview dashboard.');
        }

        // Academic state
        $currentSession = $this->academicRepository->getCurrentSession();
        $currentTerm = $this->academicRepository->getCurrentTerm();
        $classes = $this->academicRepository->getAllClasses();
        $subjects = $this->academicRepository->getAllSubjects();

        // User statistics
        $studentCount = $this->userRepository->countUsers('student');
        $teacherCount = $this->userRepository->countUsers('teacher');
        $parentCount = $this->userRepository->countUsers('parent');
        $adminCount = $this->userRepository->countUsers('admin');
        $totalUsers = $this->userRepository->countUsers();

        // Recent audit events
        $recentAudit = $this->auditLogRepository->paginate(1, 6);

        // Recent announcements
        $recentAnnouncements = $this->announcementRepository->getFeedForUser($userContext, null, 4);

        // Health quick status
        $health = $this->healthService->checkDeepHealth();

        return Response::html($this->render('admin/dashboard/index', [
            'title' => 'Admin Overview Dashboard — Claret Portal',
            'headerTitle' => 'School Overview & Command Center',
            'userContext' => $userContext,
            'currentSession' => $currentSession,
            'currentTerm' => $currentTerm,
            'classCount' => count($classes),
            'subjectCount' => count($subjects),
            'studentCount' => $studentCount,
            'teacherCount' => $teacherCount,
            'parentCount' => $parentCount,
            'adminCount' => $adminCount,
            'totalUsers' => $totalUsers,
            'recentAudit' => $recentAudit['data'] ?? [],
            'recentAnnouncements' => $recentAnnouncements,
            'health' => $health,
        ], 'layouts/admin'));
    }
}
