<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Admin\AcademicLevelController;
use App\Controllers\Admin\ClassController;
use App\Controllers\Admin\SessionController;
use App\Controllers\Admin\SubjectController;
use App\Controllers\Admin\TermController;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Core\UserContext;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\AcademicSession;
use App\Models\Term;
use App\Models\User;
use App\Repositories\AcademicRepository;
use App\Repositories\UserRepository;
use App\Services\AcademicSessionService;
use App\Services\AcademicStructureService;
use PDO;
use PHPUnit\Framework\TestCase;

final class AcademicFlowIntegrationTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $userRepository;
    private AcademicRepository $academicRepository;
    private AcademicSessionService $sessionService;
    private AcademicStructureService $structureService;
    private Router $router;
    private User $adminUser;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo->exec("
            CREATE TABLE `users` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `uuid` VARCHAR(36) NOT NULL UNIQUE,
                `name` VARCHAR(120) NOT NULL,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `phone` VARCHAR(30) NULL,
                `password_hash` VARCHAR(255) NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `must_change_password` INTEGER NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `user_roles` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL,
                `role` VARCHAR(30) NOT NULL,
                `is_active` INTEGER NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL,
                UNIQUE (`user_id`, `role`)
            );

            CREATE TABLE `user_sessions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL,
                `session_hash` VARCHAR(64) NOT NULL UNIQUE,
                `expires_at` DATETIME NOT NULL,
                `last_seen_at` DATETIME NOT NULL,
                `user_agent_hash` VARCHAR(64) NULL,
                `ip_hash` VARCHAR(64) NULL,
                `revoked_at` DATETIME NULL
            );

            CREATE TABLE `grading_scales` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `is_default` INTEGER NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `academic_levels` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL UNIQUE,
                `stage` VARCHAR(50) NOT NULL,
                `rank_order` INTEGER NOT NULL DEFAULT 0,
                `grading_scale_id` INTEGER NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `classes` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `academic_level_id` INTEGER NOT NULL,
                `name` VARCHAR(100) NOT NULL,
                `section_arm` VARCHAR(50) NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE (`academic_level_id`, `name`, `section_arm`)
            );

            CREATE TABLE `sessions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL UNIQUE,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'planning',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `terms` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `session_id` INTEGER NOT NULL,
                `name` VARCHAR(100) NOT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `grading_starts_at` DATETIME NULL,
                `grading_ends_at` DATETIME NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'planning',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE (`session_id`, `name`)
            );

            CREATE TABLE `subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(120) NOT NULL,
                `code` VARCHAR(30) NOT NULL UNIQUE,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );
        ");

        $this->userRepository = new UserRepository($this->pdo);
        $this->academicRepository = new AcademicRepository($this->pdo);
        $this->sessionService = new AcademicSessionService($this->academicRepository);
        $this->structureService = new AcademicStructureService($this->academicRepository);

        $this->adminUser = $this->userRepository->create([
            'uuid' => 'u-admin-academic',
            'name' => 'Academic Admin',
            'email' => 'academic_admin@claret.edu',
            'password_hash' => password_hash('Pass12345!', PASSWORD_DEFAULT),
        ], ['admin']);

        // Set active user session
        Session::start();
        $sessionHash = hash('sha256', 'test-academic-admin-token');
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);
        $this->userRepository->createSession($this->adminUser->id, $sessionHash, $expiresAt);
        Session::set('session_hash', $sessionHash);

        // Configure Router with Controllers
        $this->router = new Router();
        $sessionController = new SessionController($this->sessionService, $this->academicRepository);
        $termController = new TermController($this->sessionService, $this->academicRepository);
        $levelController = new AcademicLevelController($this->structureService, $this->academicRepository);
        $classController = new ClassController($this->structureService, $this->academicRepository);
        $subjectController = new SubjectController($this->structureService, $this->academicRepository);

        $adminAuth = [AuthMiddleware::class, RoleMiddleware::allow(['admin', 'super_admin'])];
        $adminFormAuth = [AuthMiddleware::class, RoleMiddleware::allow(['admin', 'super_admin']), CsrfMiddleware::class];

        $this->router->get('/admin/sessions', [$sessionController, 'index'], $adminAuth);
        $this->router->post('/admin/sessions', [$sessionController, 'store'], $adminFormAuth);
        $this->router->post('/admin/sessions/{id}/make-current', [$sessionController, 'makeCurrent'], $adminFormAuth);
        $this->router->post('/admin/sessions/{id}/archive', [$sessionController, 'archive'], $adminFormAuth);

        $this->router->get('/admin/terms', [$termController, 'index'], $adminAuth);
        $this->router->post('/admin/terms', [$termController, 'store'], $adminFormAuth);
        $this->router->post('/admin/terms/{id}/make-current', [$termController, 'makeCurrent'], $adminFormAuth);
        $this->router->post('/admin/terms/{id}/status', [$termController, 'status'], $adminFormAuth);

        $this->router->get('/admin/academic-levels', [$levelController, 'index'], $adminAuth);
        $this->router->post('/admin/academic-levels', [$levelController, 'store'], $adminFormAuth);

        $this->router->get('/admin/classes', [$classController, 'index'], $adminAuth);
        $this->router->post('/admin/classes', [$classController, 'store'], $adminFormAuth);

        $this->router->get('/admin/subjects', [$subjectController, 'index'], $adminAuth);
        $this->router->post('/admin/subjects', [$subjectController, 'store'], $adminFormAuth);
    }

    protected function tearDown(): void
    {
        Session::destroy();
    }

    private function createAuthRequest(string $method, string $uri, array $body = []): Request
    {
        $request = new Request([], $body, ['REQUEST_METHOD' => $method, 'REQUEST_URI' => $uri]);
        $request->setAttribute('_user_context', UserContext::fromUser($this->adminUser));
        return $request;
    }

    public function testCompleteAcademicSessionAndTermFlow(): void
    {
        $csrf = Csrf::generate();

        // 1. Create Session
        $reqCreateSession = $this->createAuthRequest('POST', '/admin/sessions', [
            '_csrf_token' => $csrf,
            'name' => '2026/2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-31',
        ]);

        $res = $this->router->dispatch($reqCreateSession);
        $this->assertSame(302, $res->getStatusCode());

        $session = $this->academicRepository->findSessionByName('2026/2027');
        $this->assertNotNull($session);
        $this->assertTrue($session->isPlanning());

        // 2. Activate Session
        $reqMakeActive = $this->createAuthRequest('POST', "/admin/sessions/{$session->id}/make-current", [
            '_csrf_token' => $csrf,
        ]);

        $res = $this->router->dispatch($reqMakeActive);
        $this->assertSame(302, $res->getStatusCode());
        $activeSession = $this->academicRepository->findActiveSession();
        $this->assertNotNull($activeSession);
        $this->assertSame($session->id, $activeSession->id);

        // 3. Create Term
        $reqCreateTerm = $this->createAuthRequest('POST', '/admin/terms', [
            '_csrf_token' => $csrf,
            'session_id' => $session->id,
            'name' => 'First Term',
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-18',
        ]);

        $res = $this->router->dispatch($reqCreateTerm);
        $this->assertSame(302, $res->getStatusCode());

        $term = $this->academicRepository->findTermByNameInSession($session->id, 'First Term');
        $this->assertNotNull($term);
        $this->assertTrue($term->isPlanning());

        // 4. Activate Term
        $reqActivateTerm = $this->createAuthRequest('POST', "/admin/terms/{$term->id}/make-current", [
            '_csrf_token' => $csrf,
        ]);

        $res = $this->router->dispatch($reqActivateTerm);
        $this->assertSame(302, $res->getStatusCode());
        $activeTerm = $this->academicRepository->findActiveTermInSession($session->id);
        $this->assertNotNull($activeTerm);
        $this->assertSame($term->id, $activeTerm->id);

        // 5. Open Grading Window
        $reqOpenGrading = $this->createAuthRequest('POST', "/admin/terms/{$term->id}/status", [
            '_csrf_token' => $csrf,
            'status' => 'grading_open',
        ]);

        $res = $this->router->dispatch($reqOpenGrading);
        $this->assertSame(302, $res->getStatusCode());
        $updatedTerm = $this->academicRepository->findTermById($term->id);
        $this->assertTrue($updatedTerm->isGradingOpen());

        // 6. Lock Grading Window
        $reqLockGrading = $this->createAuthRequest('POST', "/admin/terms/{$term->id}/status", [
            '_csrf_token' => $csrf,
            'status' => 'grading_locked',
        ]);

        $res = $this->router->dispatch($reqLockGrading);
        $this->assertSame(302, $res->getStatusCode());
        $updatedTerm = $this->academicRepository->findTermById($term->id);
        $this->assertTrue($updatedTerm->isGradingLocked());
    }

    public function testCompleteAcademicStructureFlow(): void
    {
        $csrf = Csrf::generate();

        // 1. Create Academic Level
        $reqLevel = $this->createAuthRequest('POST', '/admin/academic-levels', [
            '_csrf_token' => $csrf,
            'name' => 'JSS 1',
            'stage' => 'Junior Secondary',
            'rank_order' => 7,
        ]);

        $res = $this->router->dispatch($reqLevel);
        $this->assertSame(302, $res->getStatusCode());
        $level = $this->academicRepository->findLevelByName('JSS 1');
        $this->assertNotNull($level);

        // 2. Create Class with section arm
        $reqClass = $this->createAuthRequest('POST', '/admin/classes', [
            '_csrf_token' => $csrf,
            'academic_level_id' => $level->id,
            'name' => 'JSS 1 Diamond',
            'section_arm' => 'Diamond',
        ]);

        $res = $this->router->dispatch($reqClass);
        $this->assertSame(302, $res->getStatusCode());
        $class = $this->academicRepository->findClassByNameAndLevel('JSS 1 Diamond', $level->id, 'Diamond');
        $this->assertNotNull($class);
        $this->assertSame('Diamond', $class->sectionArm);

        // 3. Create Subject
        $reqSub = $this->createAuthRequest('POST', '/admin/subjects', [
            '_csrf_token' => $csrf,
            'name' => 'Civic Education',
            'code' => 'CVE101',
        ]);

        $res = $this->router->dispatch($reqSub);
        $this->assertSame(302, $res->getStatusCode());
        $subject = $this->academicRepository->findSubjectByCode('CVE101');
        $this->assertNotNull($subject);
        $this->assertSame('Civic Education', $subject->name);
    }
}
