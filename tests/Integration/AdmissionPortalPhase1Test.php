<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\AdmissionController;
use App\Controllers\Applicant\DashboardController;
use App\Core\Request;
use App\Core\UserContext;
use App\Models\AdmissionApplication;
use App\Models\AdmissionSession;
use App\Models\User;
use App\Repositories\AdmissionRepository;
use App\Repositories\UserRepository;
use App\Services\AdmissionService;
use PDO;
use PHPUnit\Framework\TestCase;

final class AdmissionPortalPhase1Test extends TestCase
{
    private PDO $pdo;
    private AdmissionRepository $admissionRepo;
    private UserRepository $userRepo;
    private AdmissionService $admissionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->createSchema();
        $this->seedFoundation();

        $this->admissionRepo = new AdmissionRepository($this->pdo);
        $this->userRepo = new UserRepository($this->pdo);
        $this->admissionService = new AdmissionService($this->admissionRepo, $this->userRepo);
    }

    private function createSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                phone TEXT NULL,
                password_hash TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'active',
                must_change_password INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS user_roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                role TEXT NOT NULL,
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS user_sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                session_hash TEXT NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                revoked_at DATETIME NULL,
                user_agent_hash TEXT NULL,
                ip_hash TEXT NULL
            );

            CREATE TABLE IF NOT EXISTS sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                status TEXT NOT NULL DEFAULT 'active'
            );

            CREATE TABLE IF NOT EXISTS academic_levels (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                rank_order INTEGER NOT NULL DEFAULT 1
            );

            CREATE TABLE IF NOT EXISTS students (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL UNIQUE,
                admission_number TEXT NOT NULL UNIQUE,
                date_of_birth DATE NULL,
                gender TEXT NULL,
                current_class_id INTEGER NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS admission_sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                academic_session_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                application_fee REAL NOT NULL DEFAULT 0.00,
                currency TEXT NOT NULL DEFAULT 'NGN',
                opens_at DATETIME NOT NULL,
                closes_at DATETIME NOT NULL,
                is_active INTEGER NOT NULL DEFAULT 1,
                required_documents_json TEXT NULL,
                instructions TEXT NULL,
                created_by INTEGER NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS admission_applications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                application_number TEXT NOT NULL UNIQUE,
                admission_session_id INTEGER NOT NULL,
                applicant_user_id INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT 'draft',
                rejection_reason TEXT NULL,
                submitted_at DATETIME NULL,
                reviewed_at DATETIME NULL,
                reviewed_by INTEGER NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS admission_wards (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                application_id INTEGER NOT NULL,
                first_name TEXT NOT NULL,
                middle_name TEXT NULL,
                last_name TEXT NOT NULL,
                date_of_birth DATE NOT NULL,
                gender TEXT NOT NULL,
                applying_for_level_id INTEGER NOT NULL,
                class_grade TEXT NOT NULL,
                curriculum_choice TEXT NULL,
                use_school_bus INTEGER NOT NULL DEFAULT 0,
                previous_school TEXT NULL,
                last_grade_passed TEXT NULL,
                medical_notes TEXT NULL,
                passport_photo_file_id INTEGER NULL,
                birth_certificate_file_id INTEGER NULL,
                previous_report_file_id INTEGER NULL,
                payment_status TEXT NOT NULL DEFAULT 'unpaid',
                converted_student_id INTEGER NULL UNIQUE,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS admission_payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                application_id INTEGER NOT NULL,
                ward_id INTEGER NOT NULL,
                reference TEXT NOT NULL UNIQUE,
                user_id INTEGER NOT NULL,
                amount REAL NOT NULL,
                currency TEXT NOT NULL DEFAULT 'NGN',
                channel TEXT NOT NULL DEFAULT 'paystack',
                status TEXT NOT NULL DEFAULT 'pending',
                gateway_reference TEXT NULL,
                metadata TEXT NULL,
                paid_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS admission_status_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                application_id INTEGER NOT NULL,
                from_status TEXT NOT NULL,
                to_status TEXT NOT NULL,
                comment TEXT NULL,
                changed_by INTEGER NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");
    }

    private function seedFoundation(): void
    {
        $this->pdo->exec("
            INSERT INTO users (id, uuid, name, email, password_hash, status)
            VALUES (1, 'uuid-admin', 'Administrator', 'admin@claret.edu', 'hash', 'active');

            INSERT INTO sessions (id, name, start_date, end_date, status)
            VALUES (1, '2026/2027', '2026-09-01', '2027-07-31', 'active');

            INSERT INTO academic_levels (id, name, slug, rank_order)
            VALUES (1, 'Primary', 'primary', 2);
        ");
    }

    public function testActiveAdmissionSessionDetection(): void
    {
        // Initially no active admission session
        $this->assertNull($this->admissionService->getActiveSession());
        $this->assertFalse($this->admissionService->isAdmissionOpen());

        // Create active admission session
        $session = $this->admissionRepo->createSession([
            'academic_session_id' => 1,
            'title' => '2026/2027 Admissions',
            'application_fee' => 10000.00,
            'currency' => 'NGN',
            'opens_at' => date('Y-m-d H:i:s', time() - 3600),
            'closes_at' => date('Y-m-d H:i:s', time() + 86400),
            'is_active' => 1,
            'instructions' => 'Welcome prospective parents.',
            'created_by' => 1,
        ]);

        $this->assertNotNull($session);
        $this->assertTrue($this->admissionService->isAdmissionOpen());
        $this->assertSame('2026/2027 Admissions', $session->title);
    }

    public function testRegisterApplicantCreatesAccountWithApplicantRoleAndApplication(): void
    {
        // 1. Seed active session
        $this->admissionRepo->createSession([
            'academic_session_id' => 1,
            'title' => '2026/2027 Admissions',
            'application_fee' => 10000.00,
            'opens_at' => date('Y-m-d H:i:s', time() - 3600),
            'closes_at' => date('Y-m-d H:i:s', time() + 86400),
            'is_active' => 1,
            'created_by' => 1,
        ]);

        // 2. Register applicant
        $result = $this->admissionService->registerApplicant(
            name: 'Mrs. Folake Johnson',
            email: 'folake.johnson@example.com',
            password: 'SuperSecretPassword123!',
            phone: '08031234567'
        );

        $this->assertTrue($result->isSuccess());
        $data = $result->data;

        /** @var User $user */
        $user = $data['user'];
        $this->assertSame('Mrs. Folake Johnson', $user->name);
        $this->assertSame('folake.johnson@example.com', $user->email);
        $this->assertTrue($user->hasRole('applicant'));
        $this->assertTrue($user->isApplicant());

        /** @var AdmissionApplication $app */
        $app = $data['application'];
        $this->assertStringStartsWith('APP-', $app->applicationNumber);
        $this->assertTrue($app->isDraft());

        // 3. Prevent duplicate email registration
        $duplicate = $this->admissionService->registerApplicant(
            name: 'Another Johnson',
            email: 'folake.johnson@example.com',
            password: 'AnotherPassword123!'
        );
        $this->assertTrue($duplicate->isFailure());
        $this->assertSame('EMAIL_TAKEN', $duplicate->errorCode);
    }

    public function testApplicantDashboardDataRetrieval(): void
    {
        $session = $this->admissionRepo->createSession([
            'academic_session_id' => 1,
            'title' => '2026/2027 Admissions',
            'application_fee' => 10000.00,
            'opens_at' => date('Y-m-d H:i:s', time() - 3600),
            'closes_at' => date('Y-m-d H:i:s', time() + 86400),
            'is_active' => 1,
            'created_by' => 1,
        ]);

        $regResult = $this->admissionService->registerApplicant(
            name: 'Chief Emeka Okafor',
            email: 'emeka.okafor@example.com',
            password: 'Password123!'
        );

        $userId = $regResult->data['user']->id;
        $appId = $regResult->data['application']->id;

        // Add a prospective ward
        $ward = $this->admissionRepo->addWard($appId, [
            'first_name' => 'Chinedu',
            'middle_name' => 'David',
            'last_name' => 'Okafor',
            'date_of_birth' => '2016-05-14',
            'gender' => 'male',
            'applying_for_level_id' => 1,
            'class_grade' => 'Primary 4',
        ]);

        $this->assertNotNull($ward);
        $this->assertSame('Chinedu David Okafor', $ward->getFullName());

        $dashboardData = $this->admissionService->getApplicantDashboardData($userId);
        $this->assertCount(1, $dashboardData['applications']);
        $this->assertSame('2026/2027 Admissions', $dashboardData['activeSession']->title);
        $this->assertCount(1, $dashboardData['applications'][0]->wards);
        $this->assertSame('Primary 4', $dashboardData['applications'][0]->wards[0]->classGrade);
    }
}
