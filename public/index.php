<?php

declare(strict_types=1);

// Set execution timezone to UTC
date_default_timezone_set('UTC');

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/config.php';

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

    // Developer Showcase Route (Environment-restricted inside the controller)
    $router->get('/dev/showcase', [\App\Controllers\Dev\ShowcaseController::class, 'index']);

    // Authenticated Routes
    $router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class, CsrfMiddleware::class]);

    $router->get('/profile/password', [AuthController::class, 'showChangePassword'], [AuthMiddleware::class]);
    $router->post('/profile/password', [AuthController::class, 'changePassword'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->get('/password/change', [AuthController::class, 'showChangePassword'], [AuthMiddleware::class]);
    $router->post('/password/change', [AuthController::class, 'changePassword'], [AuthMiddleware::class, CsrfMiddleware::class]);

    // Academic Setup & Structure Routes (Admin)
    $adminAuth = [AuthMiddleware::class, RoleMiddleware::allow(['admin', 'super_admin'])];
    $adminFormAuth = [AuthMiddleware::class, RoleMiddleware::allow(['admin', 'super_admin']), CsrfMiddleware::class];

    // Sessions
    $router->get('/admin/sessions', [\App\Controllers\Admin\SessionController::class, 'index'], $adminAuth);
    $router->post('/admin/sessions', [\App\Controllers\Admin\SessionController::class, 'store'], $adminFormAuth);
    $router->post('/admin/sessions/{id}', [\App\Controllers\Admin\SessionController::class, 'update'], $adminFormAuth);
    $router->post('/admin/sessions/{id}/make-current', [\App\Controllers\Admin\SessionController::class, 'makeCurrent'], $adminFormAuth);
    $router->post('/admin/sessions/{id}/archive', [\App\Controllers\Admin\SessionController::class, 'archive'], $adminFormAuth);

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
    $router->post('/admin/results/compute', [\App\Controllers\Admin\ResultReviewController::class, 'compute'], $adminFormAuth);
    $router->post('/admin/results/publish', [\App\Controllers\Admin\ResultPublicationController::class, 'publish'], $adminFormAuth);
    $router->post('/admin/results/unpublish', [\App\Controllers\Admin\ResultPublicationController::class, 'unpublish'], $adminFormAuth);
    $router->get('/admin/reports/student/{studentId}/{termId}.pdf', [\App\Controllers\Admin\ReportController::class, 'pdf'], $adminAuth);

    // Admin Attendance Oversight & Reporting Routes
    $router->get('/admin/attendance', [\App\Controllers\Admin\AttendanceController::class, 'index'], $adminAuth);
    $router->get('/admin/attendance/{classId}/{date}/edit', [\App\Controllers\Admin\AttendanceController::class, 'edit'], $adminAuth);
    $router->post('/admin/attendance/{classId}/{date}/edit', [\App\Controllers\Admin\AttendanceController::class, 'update'], $adminFormAuth);
    $router->get('/admin/attendance/report', [\App\Controllers\Admin\AttendanceReportController::class, 'report'], $adminAuth);

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

    // Teacher Content & Coursework Management Routes
    $teacherAuth = [AuthMiddleware::class, RoleMiddleware::allow(['teacher', 'admin', 'super_admin'])];
    $teacherFormAuth = [AuthMiddleware::class, RoleMiddleware::allow(['teacher', 'admin', 'super_admin']), CsrfMiddleware::class];

    $router->get('/teacher/content', [\App\Controllers\Teacher\ContentController::class, 'index'], $teacherAuth);
    $router->get('/teacher/content/create', [\App\Controllers\Teacher\ContentController::class, 'create'], $teacherAuth);
    $router->post('/teacher/content/create', [\App\Controllers\Teacher\ContentController::class, 'store'], $teacherFormAuth);
    $router->get('/teacher/content/{id}/edit', [\App\Controllers\Teacher\ContentController::class, 'edit'], $teacherAuth);
    $router->post('/teacher/content/{id}/edit', [\App\Controllers\Teacher\ContentController::class, 'update'], $teacherFormAuth);
    $router->post('/teacher/content/{id}/publish', [\App\Controllers\Teacher\ContentController::class, 'togglePublish'], $teacherFormAuth);
    $router->post('/teacher/content/{id}/delete', [\App\Controllers\Teacher\ContentController::class, 'delete'], $teacherFormAuth);

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
    $router->get('/teacher/question-bank/{id}/edit', [\App\Controllers\Teacher\QuestionBankController::class, 'edit'], $teacherAuth);
    $router->post('/teacher/question-bank/{id}/edit', [\App\Controllers\Teacher\QuestionBankController::class, 'update'], $teacherFormAuth);
    $router->post('/teacher/question-bank/{id}/delete', [\App\Controllers\Teacher\QuestionBankController::class, 'delete'], $teacherFormAuth);

    // Teacher Quiz & Assessment Management Routes
    $router->get('/teacher/quizzes', [\App\Controllers\Teacher\QuizController::class, 'index'], $teacherAuth);
    $router->get('/teacher/quizzes/create', [\App\Controllers\Teacher\QuizController::class, 'create'], $teacherAuth);
    $router->post('/teacher/quizzes/create', [\App\Controllers\Teacher\QuizController::class, 'store'], $teacherFormAuth);
    $router->get('/teacher/quizzes/{id}/edit', [\App\Controllers\Teacher\QuizController::class, 'edit'], $teacherAuth);
    $router->post('/teacher/quizzes/{id}/edit', [\App\Controllers\Teacher\QuizController::class, 'update'], $teacherFormAuth);
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

    // Teacher Attendance & Announcements Routes
    $router->get('/teacher/attendance', [\App\Controllers\Teacher\AttendanceController::class, 'index'], $teacherAuth);
    $router->get('/teacher/attendance/{classId}/{date}', [\App\Controllers\Teacher\AttendanceController::class, 'form'], $teacherAuth);
    $router->post('/teacher/attendance/{classId}/{date}', [\App\Controllers\Teacher\AttendanceController::class, 'store'], $teacherFormAuth);
    $router->get('/teacher/announcements', [\App\Controllers\Teacher\AnnouncementController::class, 'index'], $teacherAuth);
    $router->get('/teacher/announcements/create', [\App\Controllers\Teacher\AnnouncementController::class, 'create'], $teacherAuth);
    $router->post('/teacher/announcements', [\App\Controllers\Teacher\AnnouncementController::class, 'store'], $teacherFormAuth);

    // Teacher Timetable Route
    $router->get('/teacher/timetable', [\App\Controllers\Teacher\TimetableController::class, 'index'], $teacherAuth);

    // Student Content & Enrolled Subjects Routes
    $studentAuth = [AuthMiddleware::class, RoleMiddleware::allow(['student', 'admin', 'super_admin'])];
    $studentFormAuth = [AuthMiddleware::class, RoleMiddleware::allow(['student', 'admin', 'super_admin']), CsrfMiddleware::class];

    $router->get('/student/subjects', [\App\Controllers\Student\SubjectController::class, 'index'], $studentAuth);
    $router->get('/student/subjects/{classSubjectId}', [\App\Controllers\Student\SubjectController::class, 'show'], $studentAuth);
    $router->get('/student/content', [\App\Controllers\Student\ContentController::class, 'index'], $studentAuth);
    $router->get('/student/content/{id}', [\App\Controllers\Student\ContentController::class, 'show'], $studentAuth);

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

    // Student Attendance & Announcements Routes
    $router->get('/student/attendance', [\App\Controllers\Student\AttendanceController::class, 'index'], $studentAuth);
    $router->get('/student/announcements', [\App\Controllers\Student\AnnouncementController::class, 'index'], $studentAuth);
    $router->post('/student/announcements/{id}/read', [\App\Controllers\Student\AnnouncementController::class, 'read'], $studentAuth);

    // Student Timetable Route
    $router->get('/student/timetable', [\App\Controllers\Student\TimetableController::class, 'index'], $studentAuth);

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

    // Parent Attendance & Announcements Routes
    $router->get('/parent/attendance', [\App\Controllers\Parent\AttendanceController::class, 'index'], $parentAuth);
    $router->get('/parent/children/{studentId}/attendance', [\App\Controllers\Parent\AttendanceController::class, 'index'], $parentAuth);
    $router->get('/parent/announcements', [\App\Controllers\Parent\AnnouncementController::class, 'index'], $parentAuth);
    $router->get('/parent/children/{studentId}/announcements', [\App\Controllers\Parent\AnnouncementController::class, 'index'], $parentAuth);
    $router->post('/parent/announcements/{id}/read', [\App\Controllers\Parent\AnnouncementController::class, 'read'], $parentAuth);

    // Parent Timetable Route
    $router->get('/parent/children/{studentId}/timetable', [\App\Controllers\Parent\TimetableController::class, 'index'], $parentAuth);

    // Role-Guarded Dashboards
    $router->get('/admin/dashboard', function (Request $req): Response {
        $view = new \App\Core\View();
        return Response::html($view->render('admin/sessions/index', [
            'title' => 'Admin Dashboard — Claret LMS',
            'headerTitle' => 'Admin Overview',
            'sessions' => (new \App\Repositories\AcademicRepository())->getAllSessions(),
        ], 'layouts/admin'));
    }, [AuthMiddleware::class, RoleMiddleware::allow(['admin', 'super_admin'])]);

    $router->get('/teacher/dashboard', function (Request $req): Response {
        return Response::html('<h1>Teacher Dashboard</h1><p>Welcome to the Teacher instructional portal.</p>');
    }, [AuthMiddleware::class, RoleMiddleware::allow(['teacher'])]);

    $router->get('/student/dashboard', function (Request $req): Response {
        return Response::html('<h1>Student Dashboard</h1><p>Welcome to the Student learning portal.</p>');
    }, [AuthMiddleware::class, RoleMiddleware::allow(['student'])]);

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
