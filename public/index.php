<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/config.php';

// Set execution timezone to institutional default (Africa/Lagos / WAT)
date_default_timezone_set((string)\App\Core\Config::get('app.timezone', 'Africa/Lagos'));

use App\Controllers\AuthController;
use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\WebAuthenticator;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RoleMiddleware;

// Configure error reporting
$debug = (bool)Config::get('app.debug', false);
if ($debug) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

try {
    // Start session with secure parameters
    Session::start();

    // Create request representation
    $request = Request::createFromGlobals();

    // Initialize Router
    $router = new Router();

    // Global Middleware Pipeline
    $router->use(\App\Middleware\SecurityHeadersMiddleware::class);

    // Rate Limiting Middlewares
    $loginThrottle = \App\Middleware\RateLimitMiddleware::throttle('auth:login', 10, 60);
    $passwordResetThrottle = \App\Middleware\RateLimitMiddleware::throttle('auth:password-reset', 5, 60);
    $quizStartThrottle = \App\Middleware\RateLimitMiddleware::throttle('cbt:quiz-start', 20, 60);

    // Minimal Public Health Check Endpoint
    $router->get('/health', function (Request $req): Response {
        $healthService = new \App\Services\HealthService();
        return Response::json($healthService->ping());
    });

    // Root route: Redirect based on authentication status
    $router->get('/', function (Request $req): Response {
        $authenticator = new WebAuthenticator();
        $user = $authenticator->authenticate($req);

        if (!$user) {
            return Response::redirect('/login');
        }

        if ($user->isAdmin()) {
            return Response::redirect('/admin/dashboard');
        }
        if ($user->isTeacher()) {
            return Response::redirect('/teacher/dashboard');
        }
        if ($user->isStudent()) {
            return Response::redirect('/student/dashboard');
        }
        if ($user->isParent()) {
            return Response::redirect('/parent/dashboard');
        }
        if ($user->isApplicant()) {
            return Response::redirect('/applicant/dashboard');
        }

        return Response::redirect('/dashboard');
    });

    // Public / Guest Authentication Routes
    $router->get('/login', [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login'], [$loginThrottle, CsrfMiddleware::class]);

    $router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
    $router->post('/forgot-password', [AuthController::class, 'forgotPassword'], [$passwordResetThrottle, CsrfMiddleware::class]);
    $router->get('/password/forgot', [AuthController::class, 'showForgotPassword']);
    $router->post('/password/forgot', [AuthController::class, 'forgotPassword'], [$passwordResetThrottle, CsrfMiddleware::class]);

    $router->get('/reset-password/{token}', [AuthController::class, 'showResetPassword']);
    $router->post('/reset-password', [AuthController::class, 'resetPassword'], [$passwordResetThrottle, CsrfMiddleware::class]);
    $router->get('/password/reset/{token}', [AuthController::class, 'showResetPassword']);
    $router->post('/password/reset', [AuthController::class, 'resetPassword'], [$passwordResetThrottle, CsrfMiddleware::class]);

    // Public Self-Service Result Checker Gateway (SRS §38, §39, §40, §58.5)
    $router->get('/results/check', [\App\Controllers\PublicResultCheckerController::class, 'show']);
    $router->post('/results/check', [\App\Controllers\PublicResultCheckerController::class, 'verify'], [CsrfMiddleware::class]);
    $router->get('/results/view', [\App\Controllers\PublicResultCheckerController::class, 'viewReport']);

    // Online Admission & Prospective Student Gateway (SRS §10, §57 Phase 4)
    $router->get('/apply', [\App\Controllers\AdmissionController::class, 'showApply']);
    $router->get('/apply/register', [\App\Controllers\AdmissionController::class, 'showRegister']);
    $router->post('/apply/register', [\App\Controllers\AdmissionController::class, 'register'], [$loginThrottle, CsrfMiddleware::class]);

    // Developer Showcase Route (Environment-restricted inside the controller)
    $router->get('/dev/showcase', [\App\Controllers\Dev\ShowcaseController::class, 'index']);

    // Authenticated Routes
    $router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class, CsrfMiddleware::class]);

    $router->get('/profile', [\App\Controllers\ProfileController::class, 'show'], [AuthMiddleware::class]);
    $router->get('/profile/password', [AuthController::class, 'showChangePassword'], [AuthMiddleware::class]);
    $router->post('/profile/password', [AuthController::class, 'changePassword'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->get('/password/change', [AuthController::class, 'showChangePassword'], [AuthMiddleware::class]);
    $router->post('/password/change', [AuthController::class, 'changePassword'], [AuthMiddleware::class, CsrfMiddleware::class]);

    $router->get('/notifications', [\App\Controllers\NotificationController::class, 'index'], [AuthMiddleware::class]);
    $router->post('/notifications/{id}/read', [\App\Controllers\NotificationController::class, 'markAsRead'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->post('/notifications/read-all', [\App\Controllers\NotificationController::class, 'markAllAsRead'], [AuthMiddleware::class, CsrfMiddleware::class]);

    // Academic Setup & Structure Routes (Admin)
    $adminAuth = [AuthMiddleware::class, RoleMiddleware::allow(['admin', 'super_admin'])];
    $adminFormAuth = [AuthMiddleware::class, RoleMiddleware::allow(['admin', 'super_admin']), CsrfMiddleware::class];

    // Sessions
    $router->get('/admin/sessions', [\App\Controllers\Admin\SessionController::class, 'index'], $adminAuth);
    $router->post('/admin/sessions', [\App\Controllers\Admin\SessionController::class, 'store'], $adminFormAuth);
    $router->post('/admin/sessions/{id}', [\App\Controllers\Admin\SessionController::class, 'update'], $adminFormAuth);
    $router->post('/admin/sessions/{id}/make-current', [\App\Controllers\Admin\SessionController::class, 'makeCurrent'], $adminFormAuth);
    $router->post('/admin/sessions/{id}/archive', [\App\Controllers\Admin\SessionController::class, 'archive'], $adminFormAuth);

    // Admissions & Prospective Student Application Management
    $router->get('/admin/admissions/sessions', [\App\Controllers\Admin\AdmissionController::class, 'sessions'], $adminAuth);
    $router->post('/admin/admissions/sessions', [\App\Controllers\Admin\AdmissionController::class, 'storeSession'], $adminFormAuth);
    $router->post('/admin/admissions/sessions/{id}', [\App\Controllers\Admin\AdmissionController::class, 'updateSession'], $adminFormAuth);
    $router->get('/admin/admissions/applications', [\App\Controllers\Admin\AdmissionController::class, 'index'], $adminAuth);
    $router->get('/admin/admissions/applications/{id}', [\App\Controllers\Admin\AdmissionController::class, 'show'], $adminAuth);
    $router->post('/admin/admissions/applications/{id}/review', [\App\Controllers\Admin\AdmissionController::class, 'review'], $adminFormAuth);
    $router->post('/admin/admissions/applications/{id}/approve', [\App\Controllers\Admin\AdmissionController::class, 'approve'], $adminFormAuth);
    $router->post('/admin/admissions/applications/{id}/reject', [\App\Controllers\Admin\AdmissionController::class, 'reject'], $adminFormAuth);

    // Terms
    $router->get('/admin/terms', [\App\Controllers\Admin\TermController::class, 'index'], $adminAuth);
    $router->post('/admin/terms', [\App\Controllers\Admin\TermController::class, 'store'], $adminFormAuth);
    $router->post('/admin/terms/{id}', [\App\Controllers\Admin\TermController::class, 'update'], $adminFormAuth);
    $router->post('/admin/terms/{id}/make-current', [\App\Controllers\Admin\TermController::class, 'makeCurrent'], $adminFormAuth);
    $router->post('/admin/terms/{id}/status', [\App\Controllers\Admin\TermController::class, 'status'], $adminFormAuth);

    // Academic Levels
    $router->get('/admin/academic-levels', [\App\Controllers\Admin\AcademicLevelController::class, 'index'], $adminAuth);
    $router->post('/admin/academic-levels', [\App\Controllers\Admin\AcademicLevelController::class, 'store'], $adminFormAuth);
    $router->post('/admin/academic-levels/{id}', [\App\Controllers\Admin\AcademicLevelController::class, 'update'], $adminFormAuth);

    // Classes
    $router->get('/admin/classes', [\App\Controllers\Admin\ClassController::class, 'index'], $adminAuth);
    $router->post('/admin/classes', [\App\Controllers\Admin\ClassController::class, 'store'], $adminFormAuth);
    $router->post('/admin/classes/{id}', [\App\Controllers\Admin\ClassController::class, 'update'], $adminFormAuth);
    $router->post('/admin/classes/{id}/status', [\App\Controllers\Admin\ClassController::class, 'status'], $adminFormAuth);

    // Subjects
    $router->get('/admin/subjects', [\App\Controllers\Admin\SubjectController::class, 'index'], $adminAuth);
    $router->post('/admin/subjects', [\App\Controllers\Admin\SubjectController::class, 'store'], $adminFormAuth);
    $router->post('/admin/subjects/{id}', [\App\Controllers\Admin\SubjectController::class, 'update'], $adminFormAuth);
    $router->post('/admin/subjects/{id}/status', [\App\Controllers\Admin\SubjectController::class, 'status'], $adminFormAuth);

    // Class Subjects & Teacher Mappings
    $router->get('/admin/class-subjects', [\App\Controllers\Admin\ClassSubjectController::class, 'index'], $adminAuth);
    $router->post('/admin/class-subjects', [\App\Controllers\Admin\ClassSubjectController::class, 'store'], $adminFormAuth);
    $router->post('/admin/class-subjects/{id}', [\App\Controllers\Admin\ClassSubjectController::class, 'update'], $adminFormAuth);
    $router->post('/admin/class-subjects/{id}/status', [\App\Controllers\Admin\ClassSubjectController::class, 'status'], $adminFormAuth);

    // User Management
    $router->get('/admin/users', [\App\Controllers\Admin\UserController::class, 'index'], $adminAuth);
    $router->get('/admin/users/create', [\App\Controllers\Admin\UserController::class, 'create'], $adminAuth);
    $router->post('/admin/users', [\App\Controllers\Admin\UserController::class, 'store'], $adminFormAuth);
    $router->get('/admin/users/{id}/edit', [\App\Controllers\Admin\UserController::class, 'edit'], $adminAuth);
    $router->post('/admin/users/{id}', [\App\Controllers\Admin\UserController::class, 'update'], $adminFormAuth);
    $router->post('/admin/users/{id}/status', [\App\Controllers\Admin\UserController::class, 'status'], $adminFormAuth);
    $router->post('/admin/users/{id}/reset-password', [\App\Controllers\Admin\UserController::class, 'resetPassword'], $adminFormAuth);
    $router->post('/admin/users/{id}/delete', [\App\Controllers\Admin\UserController::class, 'delete'], $adminFormAuth);

    // Class & Subject Enrollments
    $router->get('/admin/enrollments', [\App\Controllers\Admin\EnrollmentController::class, 'index'], $adminAuth);
    $router->post('/admin/enrollments', [\App\Controllers\Admin\EnrollmentController::class, 'store'], $adminFormAuth);
    $router->post('/admin/enrollments/bulk', [\App\Controllers\Admin\EnrollmentController::class, 'bulk'], $adminFormAuth);
    $router->post('/admin/enrollments/{id}/status', [\App\Controllers\Admin\EnrollmentController::class, 'status'], $adminFormAuth);

    // Guardian Links
    $router->get('/admin/guardians', [\App\Controllers\Admin\GuardianController::class, 'index'], $adminAuth);
    $router->post('/admin/guardians/link', [\App\Controllers\Admin\GuardianController::class, 'link'], $adminFormAuth);
    $router->post('/admin/guardians/unlink', [\App\Controllers\Admin\GuardianController::class, 'unlink'], $adminFormAuth);

    // CSV Bulk Imports
    $router->get('/admin/imports/users', [\App\Controllers\Admin\ImportController::class, 'show'], $adminAuth);
    $router->post('/admin/imports/users/validate', [\App\Controllers\Admin\ImportController::class, 'validateCsv'], $adminFormAuth);
    $router->get('/admin/imports/{id}/review', [\App\Controllers\Admin\ImportController::class, 'review'], $adminAuth);
    $router->post('/admin/imports/{id}/commit', [\App\Controllers\Admin\ImportController::class, 'commit'], $adminFormAuth);
    $router->get('/admin/imports/{id}/errors.csv', [\App\Controllers\Admin\ImportController::class, 'downloadErrors'], $adminAuth);

    // Admin Grading Scales, Assessment Categories & Result Publication Routes
    $router->get('/admin/grading-scales', [\App\Controllers\Admin\GradingScaleController::class, 'index'], $adminAuth);
    $router->post('/admin/grading-scales', [\App\Controllers\Admin\GradingScaleController::class, 'store'], $adminFormAuth);
    $router->get('/admin/assessment-categories', [\App\Controllers\Admin\AssessmentCategoryController::class, 'index'], $adminAuth);
    $router->post('/admin/assessment-categories', [\App\Controllers\Admin\AssessmentCategoryController::class, 'store'], $adminFormAuth);
    $router->post('/admin/assessment-categories/{id}/delete', [\App\Controllers\Admin\AssessmentCategoryController::class, 'delete'], $adminFormAuth);
    $router->get('/admin/results/review', [\App\Controllers\Admin\ResultReviewController::class, 'index'], $adminAuth);
    $router->get('/admin/results/broadsheet', [\App\Controllers\Admin\ResultReviewController::class, 'broadsheet'], $adminAuth);
    $router->get('/admin/results/broadsheet/export', [\App\Controllers\Admin\ResultReviewController::class, 'exportBroadsheet'], $adminAuth);
    $router->post('/admin/results/compute', [\App\Controllers\Admin\ResultReviewController::class, 'compute'], $adminFormAuth);
    $router->post('/admin/results/publish', [\App\Controllers\Admin\ResultPublicationController::class, 'publish'], $adminFormAuth);
    $router->post('/admin/results/unpublish', [\App\Controllers\Admin\ResultPublicationController::class, 'unpublish'], $adminFormAuth);
    $router->get('/admin/reports/student/{studentId}/{termId}.pdf', [\App\Controllers\Admin\ReportController::class, 'pdf'], $adminAuth);
    $router->get('/admin/gradebook', [\App\Controllers\Admin\GradebookController::class, 'index'], $adminAuth);
    $router->get('/admin/gradebook/class/{classId}', [\App\Controllers\Admin\GradebookController::class, 'showClass'], $adminAuth);
    $router->post('/admin/gradebook/{id}/lock', [\App\Controllers\Admin\GradebookController::class, 'lock'], $adminFormAuth);
    $router->post('/admin/gradebook/{id}/unlock', [\App\Controllers\Admin\GradebookController::class, 'unlock'], $adminFormAuth);

    // Admin Behavioral Skills & Batch Remarks Management Routes (ADMIN-32, ADMIN-33)
    $router->get('/admin/skills', [\App\Controllers\Admin\SkillController::class, 'index'], $adminAuth);
    $router->post('/admin/skills', [\App\Controllers\Admin\SkillController::class, 'storeSkill'], $adminFormAuth);
    $router->post('/admin/skills/presets', [\App\Controllers\Admin\SkillController::class, 'storePreset'], $adminFormAuth);
    $router->post('/admin/skills/presets/{id}/update', [\App\Controllers\Admin\SkillController::class, 'updatePreset'], $adminFormAuth);
    $router->post('/admin/skills/presets/{id}/delete', [\App\Controllers\Admin\SkillController::class, 'deletePreset'], $adminFormAuth);
    $router->post('/admin/skills/{id}/update', [\App\Controllers\Admin\SkillController::class, 'updateSkill'], $adminFormAuth);
    $router->post('/admin/skills/{id}/delete', [\App\Controllers\Admin\SkillController::class, 'deleteSkill'], $adminFormAuth);
    $router->get('/admin/results/comments', [\App\Controllers\Admin\BatchRemarkController::class, 'index'], $adminAuth);
    $router->post('/admin/results/comments', [\App\Controllers\Admin\BatchRemarkController::class, 'save'], $adminFormAuth);

    // Admin Scratch-Card PIN Generation & Printing (SRS §38, §39, §40, §58.5)
    $router->get('/admin/results/pins', [\App\Controllers\Admin\ResultPinController::class, 'index'], $adminAuth);
    $router->post('/admin/results/pins/generate', [\App\Controllers\Admin\ResultPinController::class, 'generate'], $adminFormAuth);
    $router->get('/admin/results/pins/print', [\App\Controllers\Admin\ResultPinController::class, 'print'], $adminAuth);
    $router->get('/admin/results/pins/export', [\App\Controllers\Admin\ResultPinController::class, 'export'], $adminAuth);
    $router->post('/admin/results/pins/{id}/revoke', [\App\Controllers\Admin\ResultPinController::class, 'revoke'], $adminFormAuth);

    // Student Promotions, Cohort Advancement & Terminal Graduation (SRS §17, §18, §58.3)
    $router->get('/admin/promotions', [\App\Controllers\Admin\PromotionController::class, 'index'], $adminAuth);
    $router->get('/admin/promotions/class/{classId}', [\App\Controllers\Admin\PromotionController::class, 'classCohort'], $adminAuth);
    $router->get('/admin/promotions/class/{classId}/export', [\App\Controllers\Admin\PromotionController::class, 'export'], $adminAuth);
    $router->post('/admin/promotions/stage-repetition', [\App\Controllers\Admin\PromotionController::class, 'stageRepetition'], $adminFormAuth);
    $router->post('/admin/promotions/execute', [\App\Controllers\Admin\PromotionController::class, 'executeBatch'], $adminFormAuth);

    // Admin Payments & Financial Ledger
    $router->get('/admin/payments', [\App\Controllers\PaymentController::class, 'adminLedger'], $adminAuth);

    // Super Admin Two-Tier Approval Queue Routes (ADMIN-30, §6, §58.1-§58.3)
    $superAdminAuth = [AuthMiddleware::class, RoleMiddleware::allow(['super_admin'])];
    $superAdminFormAuth = [AuthMiddleware::class, RoleMiddleware::allow(['super_admin']), CsrfMiddleware::class];
    $router->get('/admin/approvals', [\App\Controllers\Admin\ApprovalController::class, 'index'], $superAdminAuth);
    $router->post('/admin/approvals/{id}/approve', [\App\Controllers\Admin\ApprovalController::class, 'approve'], $superAdminFormAuth);
    $router->post('/admin/approvals/{id}/reject', [\App\Controllers\Admin\ApprovalController::class, 'reject'], $superAdminFormAuth);

    // Admin Attendance Oversight & Reporting Routes
    $router->get('/admin/attendance', [\App\Controllers\Admin\AttendanceController::class, 'index'], $adminAuth);
    $router->get('/admin/attendance/{classId}/{date}/edit', [\App\Controllers\Admin\AttendanceController::class, 'edit'], $adminAuth);
    $router->post('/admin/attendance/{classId}/{date}/edit', [\App\Controllers\Admin\AttendanceController::class, 'update'], $adminFormAuth);
    $router->post('/admin/attendance/settings', [\App\Controllers\Admin\AttendanceController::class, 'updatePolicy'], $adminFormAuth);
    $router->get('/admin/attendance/report', [\App\Controllers\Admin\AttendanceReportController::class, 'report'], $adminAuth);

    // Admin Badges & Honors Management Routes (ADMIN-34, SRS §33, §57 Phase 3)
    $router->get('/admin/badges', [\App\Controllers\Admin\BadgeController::class, 'index'], $adminAuth);
    $router->post('/admin/badges/award', [\App\Controllers\Admin\BadgeController::class, 'award'], $adminFormAuth);
    $router->post('/admin/badges/{id}/revoke', [\App\Controllers\Admin\BadgeController::class, 'revoke'], $adminFormAuth);

    // Admin Class Group Discussions Oversight Routes (ADMIN-35, SRS §47, §57 Phase 3)
    $router->get('/admin/discussions', [\App\Controllers\Admin\DiscussionController::class, 'index'], $adminAuth);
    $router->get('/admin/discussions/{classSubjectId}/{discussionId}', [\App\Controllers\Admin\DiscussionController::class, 'show'], $adminAuth);
    $router->post('/admin/discussions/{classSubjectId}/{discussionId}/replies', [\App\Controllers\Admin\DiscussionController::class, 'reply'], $adminFormAuth);
    $router->post('/admin/discussions/{classSubjectId}/{discussionId}/pin', [\App\Controllers\Admin\DiscussionController::class, 'togglePin'], $adminFormAuth);
    $router->post('/admin/discussions/{classSubjectId}/{discussionId}/lock', [\App\Controllers\Admin\DiscussionController::class, 'toggleLock'], $adminFormAuth);
    $router->post('/admin/discussions/{classSubjectId}/{discussionId}/delete', [\App\Controllers\Admin\DiscussionController::class, 'delete'], $adminFormAuth);
    $router->post('/admin/discussions/{classSubjectId}/{discussionId}/replies/{replyId}/delete', [\App\Controllers\Admin\DiscussionController::class, 'deleteReply'], $adminFormAuth);

    // Admin Staff Geofenced Attendance Routes (SRS §22, §23)
    $router->get('/admin/staff-attendance', [\App\Controllers\Admin\StaffAttendanceController::class, 'index'], $adminAuth);
    $router->get('/admin/staff-attendance/breaches', [\App\Controllers\Admin\StaffAttendanceController::class, 'breaches'], $adminAuth);
    $router->post('/admin/staff-attendance/settings', [\App\Controllers\Admin\StaffAttendanceController::class, 'updateSettings'], $adminFormAuth);
    $router->get('/admin/staff-attendance/export', [\App\Controllers\Admin\StaffAttendanceController::class, 'export'], $adminAuth);
    $router->post('/admin/staff-attendance/override', [\App\Controllers\Admin\StaffAttendanceController::class, 'manualCorrection'], $adminFormAuth);

    // Admin Live Online Classes Routes (SRS §31)
    $router->get('/admin/live-classes', [\App\Controllers\Admin\LiveClassController::class, 'index'], $adminAuth);
    $router->post('/admin/live-classes/{id}/cancel', [\App\Controllers\Admin\LiveClassController::class, 'cancel'], $adminFormAuth);
    $router->post('/admin/live-classes/{id}/delete', [\App\Controllers\Admin\LiveClassController::class, 'destroy'], $adminFormAuth);
    $router->get('/admin/live-classes/{id}/attendees', [\App\Controllers\Admin\LiveClassController::class, 'attendees'], $adminAuth);

    // Admin Announcement Broadcast Management Routes
    $router->get('/admin/announcements', [\App\Controllers\Admin\AnnouncementController::class, 'index'], $adminAuth);
    $router->get('/admin/announcements/create', [\App\Controllers\Admin\AnnouncementController::class, 'create'], $adminAuth);
    $router->post('/admin/announcements', [\App\Controllers\Admin\AnnouncementController::class, 'store'], $adminFormAuth);
    $router->get('/admin/announcements/{id}/edit', [\App\Controllers\Admin\AnnouncementController::class, 'edit'], $adminAuth);
    $router->post('/admin/announcements/{id}/edit', [\App\Controllers\Admin\AnnouncementController::class, 'update'], $adminFormAuth);
    $router->post('/admin/announcements/{id}/delete', [\App\Controllers\Admin\AnnouncementController::class, 'delete'], $adminFormAuth);

    // Admin Timetable Builder & Management Routes
    $router->get('/admin/timetable', [\App\Controllers\Admin\TimetableController::class, 'index'], $adminAuth);
    $router->get('/admin/timetable/{classId}/edit', [\App\Controllers\Admin\TimetableController::class, 'edit'], $adminAuth);
    $router->post('/admin/timetable/{classId}/slots', [\App\Controllers\Admin\TimetableController::class, 'store'], $adminFormAuth);
    $router->post('/admin/timetable/{classId}/slots/{slotId}', [\App\Controllers\Admin\TimetableController::class, 'update'], $adminFormAuth);
    $router->post('/admin/timetable/{classId}/slots/{slotId}/delete', [\App\Controllers\Admin\TimetableController::class, 'delete'], $adminFormAuth);

    // Admin System Health, Backups & Audit Log Explorer Routes
    $router->get('/admin/health', [\App\Controllers\Admin\HealthController::class, 'index'], $adminAuth);
    $router->get('/admin/backups', [\App\Controllers\Admin\BackupController::class, 'index'], $adminAuth);
    $router->post('/admin/backups/create', [\App\Controllers\Admin\BackupController::class, 'create'], $adminFormAuth);
    $router->get('/admin/backups/{filename}/download', [\App\Controllers\Admin\BackupController::class, 'download'], $adminAuth);
    $router->get('/admin/audit-logs', [\App\Controllers\Admin\AuditLogController::class, 'index'], $adminAuth);

    // Protected File System Delivery
    $router->get('/files/{id}/download', [\App\Controllers\FileController::class, 'download'], [AuthMiddleware::class]);
    $router->get('/files/{id}/stream', [\App\Controllers\FileController::class, 'stream'], [AuthMiddleware::class]);

    // Teacher Content & Coursework Management Routes
    $teacherAuth = [AuthMiddleware::class, RoleMiddleware::allow(['teacher', 'admin', 'super_admin'])];
    $teacherFormAuth = [AuthMiddleware::class, RoleMiddleware::allow(['teacher', 'admin', 'super_admin']), CsrfMiddleware::class];

    // Teacher Class Workspace & Student Rosters Routes (TEACHER-25)
    $router->get('/teacher/classes', [\App\Controllers\Teacher\ClassController::class, 'index'], $teacherAuth);
    $router->get('/teacher/classes/{classSubjectId}', [\App\Controllers\Teacher\ClassController::class, 'show'], $teacherAuth);

    $router->get('/teacher/content', [\App\Controllers\Teacher\ContentController::class, 'index'], $teacherAuth);
    $router->get('/teacher/content/create', [\App\Controllers\Teacher\ContentController::class, 'create'], $teacherAuth);
    $router->post('/teacher/content/create', [\App\Controllers\Teacher\ContentController::class, 'store'], $teacherFormAuth);
    $router->get('/teacher/content/{id}/edit', [\App\Controllers\Teacher\ContentController::class, 'edit'], $teacherAuth);
    $router->post('/teacher/content/{id}/edit', [\App\Controllers\Teacher\ContentController::class, 'update'], $teacherFormAuth);
    $router->post('/teacher/content/{id}/publish', [\App\Controllers\Teacher\ContentController::class, 'togglePublish'], $teacherFormAuth);
    $router->post('/teacher/content/{id}/delete', [\App\Controllers\Teacher\ContentController::class, 'delete'], $teacherFormAuth);
    $router->post('/teacher/content/{id}/sections', [\App\Controllers\Teacher\ContentController::class, 'storeSection'], $teacherFormAuth);
    $router->post('/teacher/content/{id}/sections/{sectionId}/delete', [\App\Controllers\Teacher\ContentController::class, 'deleteSection'], $teacherFormAuth);
    $router->post('/teacher/content/{id}/sections/delete', [\App\Controllers\Teacher\ContentController::class, 'deleteSection'], $teacherFormAuth);
    $router->post('/teacher/content/{id}/prerequisites', [\App\Controllers\Teacher\ContentController::class, 'addPrerequisite'], $teacherFormAuth);
    $router->post('/teacher/content/{id}/prerequisites/{prerequisiteId}/delete', [\App\Controllers\Teacher\ContentController::class, 'deletePrerequisite'], $teacherFormAuth);
    $router->post('/teacher/content/{id}/prerequisites/delete', [\App\Controllers\Teacher\ContentController::class, 'deletePrerequisite'], $teacherFormAuth);

    // Teacher Course Modules & Learning Progression Routes (PHASE-6 & PHASE-7)
    $router->get('/teacher/modules', [\App\Controllers\Teacher\ModuleController::class, 'index'], $teacherAuth);
    $router->post('/teacher/modules', [\App\Controllers\Teacher\ModuleController::class, 'store'], $teacherFormAuth);
    $router->post('/teacher/modules/{id}/edit', [\App\Controllers\Teacher\ModuleController::class, 'update'], $teacherFormAuth);
    $router->post('/teacher/modules/{id}/delete', [\App\Controllers\Teacher\ModuleController::class, 'delete'], $teacherFormAuth);
    $router->post('/teacher/modules/reorder', [\App\Controllers\Teacher\ModuleController::class, 'reorder'], $teacherFormAuth);
    $router->post('/teacher/modules/{id}/items', [\App\Controllers\Teacher\ModuleController::class, 'addItem'], $teacherFormAuth);
    $router->post('/teacher/modules/{id}/items/reorder', [\App\Controllers\Teacher\ModuleController::class, 'reorderItems'], $teacherFormAuth);
    $router->post('/teacher/modules/items/{itemId}/delete', [\App\Controllers\Teacher\ModuleController::class, 'removeItem'], $teacherFormAuth);
    $router->get('/teacher/subjects/{classSubjectId}/progress', [\App\Controllers\Teacher\ModuleController::class, 'progress'], $teacherAuth);
    $router->get('/teacher/subjects/{classSubjectId}/students/{studentId}/progress', [\App\Controllers\Teacher\ModuleController::class, 'studentProgress'], $teacherAuth);

    // Teacher Assignment & Grading Routes
    $router->get('/teacher/assignments', [\App\Controllers\Teacher\AssignmentController::class, 'index'], $teacherAuth);
    $router->get('/teacher/assignments/create', [\App\Controllers\Teacher\AssignmentController::class, 'create'], $teacherAuth);
    $router->post('/teacher/assignments/create', [\App\Controllers\Teacher\AssignmentController::class, 'store'], $teacherFormAuth);
    $router->get('/teacher/assignments/{id}/edit', [\App\Controllers\Teacher\AssignmentController::class, 'edit'], $teacherAuth);
    $router->post('/teacher/assignments/{id}/edit', [\App\Controllers\Teacher\AssignmentController::class, 'update'], $teacherFormAuth);
    $router->post('/teacher/assignments/{id}/delete', [\App\Controllers\Teacher\AssignmentController::class, 'delete'], $teacherFormAuth);
    $router->get('/teacher/assignments/{id}/submissions', [\App\Controllers\Teacher\SubmissionController::class, 'index'], $teacherAuth);
    $router->post('/teacher/submissions/{id}/grade', [\App\Controllers\Teacher\SubmissionController::class, 'grade'], $teacherFormAuth);

    // Teacher Question Bank Routes
    $router->get('/teacher/question-bank', [\App\Controllers\Teacher\QuestionBankController::class, 'index'], $teacherAuth);
    $router->get('/teacher/question-bank/create', [\App\Controllers\Teacher\QuestionBankController::class, 'create'], $teacherAuth);
    $router->post('/teacher/question-bank/create', [\App\Controllers\Teacher\QuestionBankController::class, 'store'], $teacherFormAuth);
    $router->get('/teacher/question-bank/bulk', [\App\Controllers\Teacher\QuestionBankController::class, 'bulkCreate'], $teacherAuth);
    $router->post('/teacher/question-bank/bulk', [\App\Controllers\Teacher\QuestionBankController::class, 'bulkStore'], $teacherFormAuth);
    $router->get('/teacher/question-bank/{id}/edit', [\App\Controllers\Teacher\QuestionBankController::class, 'edit'], $teacherAuth);
    $router->post('/teacher/question-bank/{id}/edit', [\App\Controllers\Teacher\QuestionBankController::class, 'update'], $teacherFormAuth);
    $router->post('/teacher/question-bank/{id}/delete', [\App\Controllers\Teacher\QuestionBankController::class, 'delete'], $teacherFormAuth);

    // Teacher Quiz & Assessment Management Routes
    $router->get('/teacher/quizzes', [\App\Controllers\Teacher\QuizController::class, 'index'], $teacherAuth);
    $router->get('/teacher/quizzes/create', [\App\Controllers\Teacher\QuizController::class, 'create'], $teacherAuth);
    $router->post('/teacher/quizzes/create', [\App\Controllers\Teacher\QuizController::class, 'store'], $teacherFormAuth);
    $router->get('/teacher/quizzes/{id}/edit', [\App\Controllers\Teacher\QuizController::class, 'edit'], $teacherAuth);
    $router->post('/teacher/quizzes/{id}/edit', [\App\Controllers\Teacher\QuizController::class, 'update'], $teacherFormAuth);
    $router->post('/teacher/quizzes/{id}/prerequisites', [\App\Controllers\Teacher\QuizController::class, 'addPrerequisite'], $teacherFormAuth);
    $router->post('/teacher/quizzes/{id}/prerequisites/{prerequisiteId}/delete', [\App\Controllers\Teacher\QuizController::class, 'deletePrerequisite'], $teacherFormAuth);
    $router->post('/teacher/quizzes/{id}/prerequisites/delete', [\App\Controllers\Teacher\QuizController::class, 'deletePrerequisite'], $teacherFormAuth);
    $router->get('/teacher/quizzes/{id}/questions', [\App\Controllers\Teacher\QuizController::class, 'questions'], $teacherAuth);
    $router->post('/teacher/quizzes/{id}/questions', [\App\Controllers\Teacher\QuizController::class, 'saveQuestions'], $teacherFormAuth);
    $router->post('/teacher/quizzes/{id}/publish', [\App\Controllers\Teacher\QuizController::class, 'publish'], $teacherFormAuth);
    $router->post('/teacher/quizzes/{id}/delete', [\App\Controllers\Teacher\QuizController::class, 'delete'], $teacherFormAuth);
    $router->get('/teacher/quizzes/{id}/attempts', [\App\Controllers\Teacher\QuizAttemptController::class, 'index'], $teacherAuth);
    $router->get('/teacher/quizzes/{quizId}/attempts/{attemptId}/grade', [\App\Controllers\Teacher\QuizAttemptController::class, 'showGradeForm'], $teacherAuth);
    $router->post('/teacher/quizzes/{quizId}/attempts/{attemptId}/grade', [\App\Controllers\Teacher\QuizAttemptController::class, 'gradeShortAnswers'], $teacherFormAuth);
    $router->post('/teacher/quizzes/{quizId}/attempts/{attemptId}/reset', [\App\Controllers\Teacher\QuizAttemptController::class, 'reset'], $teacherFormAuth);

    // Teacher Gradebook Routes
    $router->get('/teacher/gradebook', [\App\Controllers\Teacher\GradebookController::class, 'index'], $teacherAuth);
    $router->get('/teacher/gradebook/{classSubjectId}', [\App\Controllers\Teacher\GradebookController::class, 'show'], $teacherAuth);
    $router->post('/teacher/gradebook/{classSubjectId}/save', [\App\Controllers\Teacher\GradebookController::class, 'save'], $teacherFormAuth);
    $router->get('/teacher/results/comments', [\App\Controllers\Teacher\BatchRemarkController::class, 'index'], $teacherAuth);
    $router->post('/teacher/results/comments', [\App\Controllers\Teacher\BatchRemarkController::class, 'save'], $teacherFormAuth);

    // Teacher Attendance & Announcements Routes
    $router->get('/teacher/attendance', [\App\Controllers\Teacher\AttendanceController::class, 'index'], $teacherAuth);
    $router->get('/teacher/attendance/{classId}', [\App\Controllers\Teacher\AttendanceController::class, 'form'], $teacherAuth);
    $router->post('/teacher/attendance/{classId}', [\App\Controllers\Teacher\AttendanceController::class, 'store'], $teacherFormAuth);
    $router->get('/teacher/attendance/{classId}/{date}', [\App\Controllers\Teacher\AttendanceController::class, 'form'], $teacherAuth);
    $router->post('/teacher/attendance/{classId}/{date}', [\App\Controllers\Teacher\AttendanceController::class, 'store'], $teacherFormAuth);
    $router->get('/teacher/announcements', [\App\Controllers\Teacher\AnnouncementController::class, 'index'], $teacherAuth);
    $router->get('/teacher/announcements/create', [\App\Controllers\Teacher\AnnouncementController::class, 'create'], $teacherAuth);
    $router->post('/teacher/announcements/create', [\App\Controllers\Teacher\AnnouncementController::class, 'store'], $teacherFormAuth);
    $router->post('/teacher/announcements', [\App\Controllers\Teacher\AnnouncementController::class, 'store'], $teacherFormAuth);
    $router->post('/teacher/announcements/{id}/read', [\App\Controllers\Teacher\AnnouncementController::class, 'read'], $teacherFormAuth);

    // Teacher Timetable Route
    $router->get('/teacher/timetable', [\App\Controllers\Teacher\TimetableController::class, 'index'], $teacherAuth);

    // Teacher Staff Geofenced Attendance Routes (SRS §22, §23, §24)
    $router->get('/teacher/staff-attendance', [\App\Controllers\Teacher\StaffAttendanceController::class, 'index'], $teacherAuth);
    $router->post('/teacher/staff-attendance/clock-in', [\App\Controllers\Teacher\StaffAttendanceController::class, 'clockIn'], $teacherFormAuth);
    $router->post('/teacher/staff-attendance/clock-out', [\App\Controllers\Teacher\StaffAttendanceController::class, 'clockOut'], $teacherFormAuth);
    $router->post('/teacher/staff-attendance/sync', [\App\Controllers\Teacher\StaffAttendanceController::class, 'syncOffline'], $teacherAuth);
    $router->get('/api/staff-attendance/status', [\App\Controllers\Teacher\StaffAttendanceController::class, 'status'], $teacherAuth);

    // Teacher Live Online Classes Routes (SRS §31)
    $router->get('/teacher/live-classes', [\App\Controllers\Teacher\LiveClassController::class, 'index'], $teacherAuth);
    $router->post('/teacher/live-classes', [\App\Controllers\Teacher\LiveClassController::class, 'store'], $teacherFormAuth);
    $router->post('/teacher/live-classes/{id}/update', [\App\Controllers\Teacher\LiveClassController::class, 'update'], $teacherFormAuth);
    $router->post('/teacher/live-classes/{id}/start', [\App\Controllers\Teacher\LiveClassController::class, 'start'], $teacherFormAuth);
    $router->post('/teacher/live-classes/{id}/end', [\App\Controllers\Teacher\LiveClassController::class, 'end'], $teacherFormAuth);
    $router->post('/teacher/live-classes/{id}/cancel', [\App\Controllers\Teacher\LiveClassController::class, 'cancel'], $teacherFormAuth);
    $router->post('/teacher/live-classes/{id}/delete', [\App\Controllers\Teacher\LiveClassController::class, 'destroy'], $teacherFormAuth);
    $router->get('/teacher/live-classes/{id}/attendees', [\App\Controllers\Teacher\LiveClassController::class, 'attendees'], $teacherAuth);

    // Teacher Badges & Gamification Routes (SRS §33, §57 Phase 3)
    $router->get('/teacher/badges', [\App\Controllers\Teacher\BadgeController::class, 'index'], $teacherAuth);
    $router->post('/teacher/badges/award', [\App\Controllers\Teacher\BadgeController::class, 'award'], $teacherFormAuth);

    // Teacher Class Discussions Routes (SRS §47, §57 Phase 3)
    $router->get('/teacher/subjects/{classSubjectId}/discussions', [\App\Controllers\Teacher\DiscussionController::class, 'index'], $teacherAuth);
    $router->post('/teacher/subjects/{classSubjectId}/discussions', [\App\Controllers\Teacher\DiscussionController::class, 'store'], $teacherFormAuth);
    $router->get('/teacher/subjects/{classSubjectId}/discussions/{discussionId}', [\App\Controllers\Teacher\DiscussionController::class, 'show'], $teacherAuth);
    $router->post('/teacher/subjects/{classSubjectId}/discussions/{discussionId}/replies', [\App\Controllers\Teacher\DiscussionController::class, 'reply'], $teacherFormAuth);
    $router->post('/teacher/subjects/{classSubjectId}/discussions/{discussionId}/pin', [\App\Controllers\Teacher\DiscussionController::class, 'togglePin'], $teacherFormAuth);
    $router->post('/teacher/subjects/{classSubjectId}/discussions/{discussionId}/lock', [\App\Controllers\Teacher\DiscussionController::class, 'toggleLock'], $teacherFormAuth);
    $router->post('/teacher/subjects/{classSubjectId}/discussions/{discussionId}/delete', [\App\Controllers\Teacher\DiscussionController::class, 'delete'], $teacherFormAuth);

    // Student Content & Enrolled Subjects Routes
    $studentAuth = [AuthMiddleware::class, RoleMiddleware::allow(['student', 'admin', 'super_admin'])];
    $studentFormAuth = [AuthMiddleware::class, RoleMiddleware::allow(['student', 'admin', 'super_admin']), CsrfMiddleware::class];

    $router->get('/student/subjects', [\App\Controllers\Student\SubjectController::class, 'index'], $studentAuth);
    $router->get('/student/subjects/{classSubjectId}', [\App\Controllers\Student\SubjectController::class, 'show'], $studentAuth);
    $router->get('/student/content', [\App\Controllers\Student\ContentController::class, 'index'], $studentAuth);
    $router->get('/student/content/{id}', [\App\Controllers\Student\ContentController::class, 'show'], $studentAuth);
    $router->get('/student/content/{id}/read', [\App\Controllers\Student\ContentController::class, 'read'], $studentAuth);
    $router->get('/student/content/{id}/progress', [\App\Controllers\Student\ContentController::class, 'getProgress'], $studentAuth);
    $router->post('/student/content/{id}/progress', [\App\Controllers\Student\ContentController::class, 'saveProgress'], $studentAuth); // AJAX progress autosave (session auth)

    // Student Assignment Routes
    $router->get('/student/assignments', [\App\Controllers\Student\AssignmentController::class, 'index'], $studentAuth);
    $router->get('/student/assignments/{id}', [\App\Controllers\Student\AssignmentController::class, 'show'], $studentAuth);
    $router->post('/student/assignments/{id}/submit', [\App\Controllers\Student\SubmissionController::class, 'store'], $studentFormAuth);

    // Student CBT Quiz Routes
    $router->get('/student/quizzes', [\App\Controllers\Student\QuizController::class, 'index'], $studentAuth);
    $router->get('/student/quizzes/{id}', [\App\Controllers\Student\QuizController::class, 'show'], $studentAuth);
    $router->post('/student/quizzes/{id}/attempts', [\App\Controllers\Student\QuizAttemptController::class, 'start'], array_merge($studentFormAuth, [$quizStartThrottle]));
    $router->get('/student/quiz-attempts/{id}', [\App\Controllers\Student\QuizAttemptController::class, 'take'], $studentAuth);
    $router->post('/student/quiz-attempts/{id}/answers', [\App\Controllers\Student\QuizAttemptController::class, 'autosave'], $studentAuth); // AJAX autosave (session auth)
    $router->post('/student/quiz-attempts/{id}/submit', [\App\Controllers\Student\QuizAttemptController::class, 'submit'], $studentFormAuth);
    $router->get('/student/quiz-attempts/{id}/result', [\App\Controllers\Student\QuizAttemptController::class, 'result'], $studentAuth);

    // Student Grades & Report Card Routes
    $router->get('/student/grades', [\App\Controllers\Student\ReportCardController::class, 'index'], $studentAuth);
    $router->get('/student/grades/report-card', [\App\Controllers\Student\ReportCardController::class, 'show'], $studentAuth);
    $router->get('/student/grades/report-card.pdf', [\App\Controllers\Student\ReportCardController::class, 'pdf'], $studentAuth);
    $router->post('/student/grades/unlock', [\App\Controllers\Student\ReportCardController::class, 'unlock'], $studentFormAuth);

    // Student Attendance & Announcements Routes
    $router->get('/student/attendance', [\App\Controllers\Student\AttendanceController::class, 'index'], $studentAuth);
    $router->get('/student/announcements', [\App\Controllers\Student\AnnouncementController::class, 'index'], $studentAuth);
    $router->post('/student/announcements/{id}/read', [\App\Controllers\Student\AnnouncementController::class, 'read'], $studentAuth);

    // Student Timetable Route
    $router->get('/student/timetable', [\App\Controllers\Student\TimetableController::class, 'index'], $studentAuth);

    // Student Live Online Classes Hub Routes (SRS §31)
    $router->get('/student/live-classes', [\App\Controllers\Student\LiveClassController::class, 'index'], $studentAuth);
    $router->get('/student/live-classes/{id}/join', [\App\Controllers\Student\LiveClassController::class, 'join'], $studentAuth);

    // Student Badges & Gamification Routes (SRS §33, §57 Phase 3)
    $router->get('/student/badges', [\App\Controllers\Student\BadgeController::class, 'index'], $studentAuth);

    // Student Class Discussions Routes (SRS §47, §57 Phase 3)
    $router->get('/student/subjects/{classSubjectId}/discussions', [\App\Controllers\Student\DiscussionController::class, 'index'], $studentAuth);
    $router->post('/student/subjects/{classSubjectId}/discussions', [\App\Controllers\Student\DiscussionController::class, 'store'], $studentFormAuth);
    $router->get('/student/subjects/{classSubjectId}/discussions/{discussionId}', [\App\Controllers\Student\DiscussionController::class, 'show'], $studentAuth);
    $router->post('/student/subjects/{classSubjectId}/discussions/{discussionId}/replies', [\App\Controllers\Student\DiscussionController::class, 'reply'], $studentFormAuth);

    // Parent Portal Routes
    $parentAuth = [AuthMiddleware::class, RoleMiddleware::allow(['parent', 'admin', 'super_admin'])];
    $parentFormAuth = [AuthMiddleware::class, RoleMiddleware::allow(['parent', 'admin', 'super_admin']), CsrfMiddleware::class];

    $router->get('/parent/dashboard', [\App\Controllers\Parent\DashboardController::class, 'index'], $parentAuth);
    $router->post('/parent/children/{studentId}/select', [\App\Controllers\Parent\ChildController::class, 'select'], $parentFormAuth);
    $router->get('/parent/children/{studentId}', [\App\Controllers\Parent\ChildController::class, 'show'], $parentAuth);
    $router->get('/parent/children/{studentId}/assignments', [\App\Controllers\Parent\AssignmentController::class, 'index'], $parentAuth);
    $router->get('/parent/children/{studentId}/grades', [\App\Controllers\Parent\ReportCardController::class, 'index'], $parentAuth);
    $router->get('/parent/children/{studentId}/grades/report-card', [\App\Controllers\Parent\ReportCardController::class, 'show'], $parentAuth);
    $router->get('/parent/children/{studentId}/grades/report-card.pdf', [\App\Controllers\Parent\ReportCardController::class, 'pdf'], $parentAuth);
    $router->post('/parent/children/{studentId}/grades/unlock', [\App\Controllers\Parent\ReportCardController::class, 'unlock'], $parentFormAuth);

    // Parent Attendance & Announcements Routes
    $router->get('/parent/attendance', [\App\Controllers\Parent\AttendanceController::class, 'index'], $parentAuth);
    $router->get('/parent/children/{studentId}/attendance', [\App\Controllers\Parent\AttendanceController::class, 'index'], $parentAuth);
    $router->get('/parent/announcements', [\App\Controllers\Parent\AnnouncementController::class, 'index'], $parentAuth);
    $router->get('/parent/children/{studentId}/announcements', [\App\Controllers\Parent\AnnouncementController::class, 'index'], $parentAuth);
    $router->post('/parent/announcements/{id}/read', [\App\Controllers\Parent\AnnouncementController::class, 'read'], $parentAuth);

    // Parent Timetable Route
    $router->get('/parent/children/{studentId}/timetable', [\App\Controllers\Parent\TimetableController::class, 'index'], $parentAuth);

    // Parent Live Classes Route (SRS §31)
    $router->get('/parent/live-classes', [\App\Controllers\Parent\LiveClassController::class, 'index'], $parentAuth);

    // Parent Child Badges & Class Discussions Routes (SRS §33, §47 Phase 3)
    $router->get('/parent/children/{studentId}/badges', [\App\Controllers\Parent\ChildController::class, 'badges'], $parentAuth);
    $router->get('/parent/children/{studentId}/discussions', [\App\Controllers\Parent\ChildController::class, 'discussions'], $parentAuth);

    // Payments & Paystack-Ready Commerce Routes
    $paymentAuth = [AuthMiddleware::class];
    $personalPaymentAuth = [AuthMiddleware::class, RoleMiddleware::allow(['parent', 'student'])];
    $paymentFormAuth = [AuthMiddleware::class, CsrfMiddleware::class];
    $router->post('/payments/checkout/pin', [\App\Controllers\PaymentController::class, 'checkoutPin'], $paymentFormAuth);
    $router->post('/payments/simulate/{reference}', [\App\Controllers\PaymentController::class, 'simulate'], $paymentFormAuth);
    $router->get('/payments/callback', [\App\Controllers\PaymentController::class, 'callback'], $paymentAuth);
    $router->get('/payments/history', [\App\Controllers\PaymentController::class, 'history'], $personalPaymentAuth);
    $router->get('/payments/{reference}/receipt', [\App\Controllers\PaymentController::class, 'receipt'], $paymentAuth);

    // Role-Guarded Dashboards
    $router->get('/admin/dashboard', [\App\Controllers\Admin\DashboardController::class, 'index'], $adminAuth);

    $router->get('/teacher/dashboard', [\App\Controllers\Teacher\DashboardController::class, 'index'], $teacherAuth);

    $router->get('/student/dashboard', [\App\Controllers\Student\DashboardController::class, 'index'], $studentAuth);

    // Applicant Portal Routes (SRS §10, §57 Phase 4)
    $applicantAuth = [AuthMiddleware::class, RoleMiddleware::allow(['applicant', 'admin', 'super_admin'])];
    $applicantFormAuth = [AuthMiddleware::class, RoleMiddleware::allow(['applicant', 'admin', 'super_admin']), CsrfMiddleware::class];

    // Dashboard & Overview
    $router->get('/applicant/dashboard', [\App\Controllers\Applicant\DashboardController::class, 'index'], $applicantAuth);

    // Application & Ward Management
    $router->get('/applicant/application', [\App\Controllers\Applicant\ApplicationController::class, 'index'], $applicantAuth);
    $router->get('/applicant/wards/create', [\App\Controllers\Applicant\ApplicationController::class, 'createWard'], $applicantAuth);
    $router->post('/applicant/wards', [\App\Controllers\Applicant\ApplicationController::class, 'storeWard'], $applicantFormAuth);
    $router->get('/applicant/wards/{id}/edit', [\App\Controllers\Applicant\ApplicationController::class, 'editWard'], $applicantAuth);
    $router->post('/applicant/wards/{id}', [\App\Controllers\Applicant\ApplicationController::class, 'updateWard'], $applicantFormAuth);
    $router->post('/applicant/wards/{id}/delete', [\App\Controllers\Applicant\ApplicationController::class, 'deleteWard'], $applicantFormAuth);

    // Document Management
    $router->get('/applicant/wards/{id}/documents', [\App\Controllers\Applicant\ApplicationController::class, 'showDocuments'], $applicantAuth);
    $router->post('/applicant/wards/{id}/documents', [\App\Controllers\Applicant\ApplicationController::class, 'uploadDocument'], $applicantFormAuth);

    // Application Submission & Milestone Progress
    $router->post('/applicant/applications/{id}/submit', [\App\Controllers\Applicant\ApplicationController::class, 'submitApplication'], $applicantFormAuth);
    $router->get('/applicant/progress', [\App\Controllers\Applicant\ApplicationController::class, 'progress'], $applicantAuth);

    // Per-Ward Application Fee Payment Gate
    $router->get('/applicant/payment/callback', [\App\Controllers\Applicant\PaymentController::class, 'callback'], $applicantAuth);
    $router->post('/applicant/payment/simulate', [\App\Controllers\Applicant\PaymentController::class, 'simulate'], $applicantFormAuth);
    $router->get('/applicant/payment/{wardId}', [\App\Controllers\Applicant\PaymentController::class, 'showCheckout'], $applicantAuth);
    $router->post('/applicant/payment/{wardId}/checkout', [\App\Controllers\Applicant\PaymentController::class, 'checkout'], $applicantFormAuth);

    $router->get('/dashboard', function (Request $req): Response {
        $authenticator = new WebAuthenticator();
        $user = $authenticator->authenticate($req);
        if ($user) {
            if ($user->isAdmin()) return Response::redirect('/admin/dashboard');
            if ($user->isTeacher()) return Response::redirect('/teacher/dashboard');
            if ($user->isStudent()) return Response::redirect('/student/dashboard');
            if ($user->isParent()) return Response::redirect('/parent/dashboard');
        }
        return Response::html('<h1>Dashboard</h1><p>Welcome to Claret LMS.</p>');
    }, [AuthMiddleware::class]);

    // Dispatch request and send response
    $response = $router->dispatch($request);
    $response->send();
} catch (\App\Core\Exceptions\AuthorizationException $e) {
    if (isset($request) && ($request->isJson() || $request->isAjax())) {
        $response = Response::json([
            'error' => 'Forbidden',
            'message' => $e->getMessage(),
        ], 403);
    } else {
        $response = Response::html(
            '<h1>403 Forbidden</h1><p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>',
            403
        );
    }
    $response->send();
} catch (\App\Core\Exceptions\ResourceNotFoundException $e) {
    if (isset($request) && ($request->isJson() || $request->isAjax())) {
        $response = Response::json([
            'error' => 'Not Found',
            'message' => $e->getMessage(),
        ], 404);
    } else {
        $response = Response::html(
            '<h1>404 Not Found</h1><p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>',
            404
        );
    }
    $response->send();
} catch (Throwable $e) {
    // Structured JSON log with correlation ID
    $logger = new \App\Services\LoggerService();
    $logger->error('Unhandled Exception: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'code' => $e->getCode(),
        'exception' => get_class($e),
        'trace' => $e->getTraceAsString(),
    ]);

    if (isset($request) && ($request->isJson() || $request->isAjax())) {
        $response = Response::json([
            'error' => 'Internal Server Error',
            'code' => 'INTERNAL_SERVER_ERROR',
            'request_id' => $logger->getRequestId(),
        ], 500);
    } else {
        if ($debug) {
            $response = Response::html(
                '<h1>500 Server Error</h1><p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p><pre>' . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>',
                500
            );
        } else {
            $view = new \App\Core\View();
            $response = Response::html($view->render('errors/500'), 500);
        }
    }
    $response->send();
}
