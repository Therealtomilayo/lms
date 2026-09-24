<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Models\AdmissionApplication;
use App\Models\AdmissionWard;
use App\Repositories\AcademicRepository;
use App\Repositories\AdmissionRepository;
use App\Repositories\FileRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\UserRepository;
use App\Services\AdmissionService;
use App\Services\AuthService;
use PDO;
use PHPUnit\Framework\TestCase;

class AdminAdmissionsManagementTest extends TestCase
{
    private PDO $db;
    private AdmissionService $admissionService;
    private AdmissionRepository $admissionRepo;
    private UserRepository $userRepo;
    private StudentRepository $studentRepo;
    private ParentRepository $parentRepo;
    private AcademicRepository $academicRepo;
    private AuthService $authService;
    private int $adminUserId;
    private int $applicantUserId;
    private int $sessionId;

    protected function setUp(): void
    {
        $this->db = Database::getConnection();
        $this->admissionRepo = new AdmissionRepository($this->db);
        $this->userRepo = new UserRepository($this->db);
        $this->academicRepo = new AcademicRepository($this->db);
        $this->studentRepo = new StudentRepository($this->db);
        $this->parentRepo = new ParentRepository($this->db);
        
        $this->admissionService = new AdmissionService(
            $this->admissionRepo,
            $this->userRepo,
            $this->academicRepo,
            $this->studentRepo,
            $this->parentRepo,
            $this->db
        );
        $this->authService = new AuthService($this->userRepo, $this->db);

        // Ensure active session
        $session = $this->admissionRepo->getActiveAdmissionSession();
        $this->assertNotNull($session);
        $this->sessionId = (int)$session->id;

        // Admin user
        $adminEmail = 'admin_adm_' . uniqid() . '@claret.edu.ng';
        $admin = $this->userRepo->create([
            'uuid' => 'admin-uuid-' . uniqid(),
            'name' => 'Admissions Officer Admin',
            'email' => $adminEmail,
            'phone' => '08099998888',
            'password_hash' => password_hash('Secret123!', PASSWORD_BCRYPT),
            'status' => 'active',
            'must_change_password' => 0,
        ], ['admin']);
        $this->adminUserId = (int)$admin->id;

        // Applicant user
        $applicantEmail = 'parent_adm_' . uniqid() . '@example.com';
        $res = $this->admissionService->registerApplicant('Barrister Okonkwo', $applicantEmail, 'Password123!', '08011223344');
        $this->assertTrue($res->isSuccess());
        $this->applicantUserId = (int)$res->getData()['user']->id;
    }

    protected function tearDown(): void
    {
        // Cleanup test data
        $stmt = $this->db->prepare("SELECT id FROM admission_applications WHERE applicant_user_id = ?");
        $stmt->execute([$this->applicantUserId]);
        $apps = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($apps as $app) {
            $appId = (int)$app['id'];
            // Find converted students
            $wStmt = $this->db->prepare("SELECT converted_student_id FROM admission_wards WHERE application_id = ? AND converted_student_id IS NOT NULL");
            $wStmt->execute([$appId]);
            $studentIds = $wStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

            foreach ($studentIds as $stuId) {
                $stu = $this->studentRepo->findById((int)$stuId);
                $this->db->prepare("DELETE FROM student_subject_enrollments WHERE student_id = ?")->execute([$stuId]);
                $this->db->prepare("DELETE FROM class_enrollments WHERE student_id = ?")->execute([$stuId]);
                $this->db->prepare("DELETE FROM parent_student WHERE student_id = ?")->execute([$stuId]);
                $this->db->prepare("DELETE FROM students WHERE id = ?")->execute([$stuId]);
                if ($stu) {
                    $this->db->prepare("DELETE FROM user_roles WHERE user_id = ?")->execute([$stu->userId]);
                    $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$stu->userId]);
                }
            }

            $this->db->prepare("DELETE FROM admission_payments WHERE ward_id IN (SELECT id FROM admission_wards WHERE application_id = ?)")->execute([$appId]);
            $this->db->prepare("DELETE FROM admission_status_history WHERE application_id = ?")->execute([$appId]);
            $this->db->prepare("DELETE FROM admission_wards WHERE application_id = ?")->execute([$appId]);
            $this->db->prepare("DELETE FROM admission_applications WHERE id = ?")->execute([$appId]);
        }

        $this->db->prepare("DELETE FROM parent_student WHERE parent_id IN (SELECT id FROM parents WHERE user_id = ?)")->execute([$this->applicantUserId]);
        $this->db->prepare("DELETE FROM parents WHERE user_id = ?")->execute([$this->applicantUserId]);
        $this->db->prepare("DELETE FROM user_roles WHERE user_id IN (?, ?)")->execute([$this->applicantUserId, $this->adminUserId]);
        $this->db->prepare("DELETE FROM users WHERE id IN (?, ?)")->execute([$this->applicantUserId, $this->adminUserId]);
    }

    public function testAdminAdmissionsManagementAndConversionEngine(): void
    {
        // 1. Prepare application docket with 1 ward, simulated payment, and documents
        $application = $this->admissionService->getApplicantActiveApplication($this->applicantUserId);
        $this->assertNotNull($application);
        $appId = (int)$application->id;

        $levels = $this->admissionService->getAcademicLevels();
        $levelId = !empty($levels) ? (int)$levels[0]->id : 1;

        $wardData = [
            'first_name' => 'Emeka',
            'middle_name' => 'Francis',
            'last_name' => 'Okonkwo',
            'gender' => 'male',
            'date_of_birth' => '2014-08-20',
            'state_of_origin' => 'Anambra',
            'lga' => 'Onitsha North',
            'applying_for_level_id' => $levelId,
            'class_grade' => 'JSS 1',
            'previous_school' => 'Holy Rosary Academy',
            'previous_class' => 'Primary 6'
        ];
        $addRes = $this->admissionService->addWard($this->applicantUserId, $wardData);
        $this->assertTrue($addRes->isSuccess());
        $wardId = (int)$addRes->getData()->id;

        // Payment
        $payInit = $this->admissionService->initializeWardPayment($wardId, $this->applicantUserId, 'https://lms.test/callback');
        $this->assertTrue($payInit->isSuccess());
        $sim = $this->admissionService->simulateSuccessfulWardPayment($payInit->getData()['reference'], $this->applicantUserId);
        $this->assertTrue($sim->isSuccess());

        // Documents
        $fileRepo = new FileRepository($this->db);
        $birthCert = $fileRepo->create(
            uuid: 'tc-birth-' . uniqid(),
            storageKey: 'storage/uploads/admissions/' . uniqid() . '_birth.pdf',
            originalName: 'birth.pdf',
            mimeType: 'application/pdf',
            sizeBytes: 1024,
            sha256: hash('sha256', 'dummy1' . uniqid()),
            uploadedBy: $this->applicantUserId,
            ownerType: 'admission_ward',
            ownerId: $wardId
        );
        $passport = $fileRepo->create(
            uuid: 'tc-passport-' . uniqid(),
            storageKey: 'storage/uploads/admissions/' . uniqid() . '_passport.jpg',
            originalName: 'passport.jpg',
            mimeType: 'image/jpeg',
            sizeBytes: 2048,
            sha256: hash('sha256', 'dummy2' . uniqid()),
            uploadedBy: $this->applicantUserId,
            ownerType: 'admission_ward',
            ownerId: $wardId
        );

        $this->admissionService->attachDocumentToWard($wardId, $this->applicantUserId, 'birth_certificate', (int)$birthCert->id);
        $this->admissionService->attachDocumentToWard($wardId, $this->applicantUserId, 'passport_photo', (int)$passport->id);

        // Submit docket
        $sub = $this->admissionService->submitApplication($appId, $this->applicantUserId);
        $this->assertTrue($sub->isSuccess());

        // 2. Admin: Filter and search
        $filtered = $this->admissionService->getAllApplications([
            'session_id' => $this->sessionId,
            'status' => 'submitted',
            'search' => 'Okonkwo'
        ]);
        $this->assertNotEmpty($filtered);
        $found = false;
        foreach ($filtered as $f) {
            if ($f->id === $appId) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Application should be returned in filtered list.');

        // 3. Admin: Dossier retrieval
        $dossier = $this->admissionService->getApplicationDossier($appId);
        $this->assertNotNull($dossier);
        $this->assertEquals($appId, $dossier['application']->id);
        $this->assertCount(1, $dossier['wards']);
        $this->assertNotNull($dossier['wards'][0]['birth_cert']);
        $this->assertNotNull($dossier['wards'][0]['passport']);
        $this->assertNotNull($dossier['wards'][0]['payment']);

        // 4. Admin: Transition to Under Review
        $reviewRes = $this->admissionService->updateApplicationStatus(
            $appId,
            AdmissionApplication::STATUS_UNDER_REVIEW,
            $this->adminUserId,
            'Entrance examination scheduled.'
        );
        $this->assertTrue($reviewRes->isSuccess());

        $refreshed = $this->admissionRepo->findApplicationById($appId);
        $this->assertEquals(AdmissionApplication::STATUS_UNDER_REVIEW, $refreshed->status);

        // 5. Admin: Approve Application & Execute Student Matriculation Conversion (with assigned class)
        $classes = $this->academicRepo->getAllClasses();
        $targetClass = !empty($classes) ? $classes[0] : null;
        $wardAllocations = $targetClass ? [$wardId => $targetClass->id] : [];

        $approveRes = $this->admissionService->approveApplication(
            $appId,
            $this->adminUserId,
            'Entrance criteria met with distinction. Admitted.',
            $wardAllocations
        );
        $this->assertTrue($approveRes->isSuccess(), $approveRes->getMessage() ?? '');

        // Verify Application status is APPROVED
        $approvedApp = $this->admissionRepo->findApplicationById($appId);
        $this->assertEquals(AdmissionApplication::STATUS_APPROVED, $approvedApp->status);
        $this->assertEquals($this->adminUserId, $approvedApp->reviewedBy);

        // Verify Ward has converted_student_id
        $wardAfterApproval = $this->admissionService->getWard($wardId, $this->applicantUserId);
        $this->assertNotNull($wardAfterApproval->convertedStudentId);
        $this->assertGreaterThan(0, $wardAfterApproval->convertedStudentId);

        // Verify Student record was created with STD-xxxxx format
        $student = $this->studentRepo->findById((int)$wardAfterApproval->convertedStudentId);
        $this->assertNotNull($student);
        $this->assertMatchesRegularExpression('/^STD-\d{5}$/', $student->admissionNumber);
        if ($targetClass) {
            $this->assertEquals($targetClass->id, $student->currentClassId);
            $enrStmt = $this->db->prepare("SELECT COUNT(*) FROM class_enrollments WHERE student_id = ? AND class_id = ?");
            $enrStmt->execute([$student->id, $targetClass->id]);
            $this->assertGreaterThan(0, (int)$enrStmt->fetchColumn());
        }

        // Verify Student user exists
        $studentUser = $this->userRepo->findById($student->userId);
        $this->assertNotNull($studentUser);
        $this->assertTrue($studentUser->hasRole('student'));
        $this->assertStringEndsWith('@student.claret.edu.ng', $studentUser->email);

        // Verify Applicant user received 'parent' role
        $guardianUser = $this->userRepo->findById($this->applicantUserId);
        $this->assertTrue($guardianUser->hasRole('parent'));

        // Verify parent profile & linkage
        $parentProfile = $this->parentRepo->findByUserId($this->applicantUserId);
        $this->assertNotNull($parentProfile);
        $this->assertTrue($this->parentRepo->isLinked($parentProfile->id, $student->id));

        // Clean up files
        $this->db->prepare("DELETE FROM files WHERE id IN (?, ?)")->execute([$birthCert->id, $passport->id]);
    }

    public function testAdminAdmissionControllerViewsRender(): void
    {
        $adminActor = new \App\Core\UserContext(
            id: $this->adminUserId,
            uuid: 'admin-uuid',
            name: 'Admin Officer',
            email: 'admin@claret.edu.ng',
            roles: ['admin'],
            mustChangePassword: false
        );

        $controller = new \App\Controllers\Admin\AdmissionController(
            $this->admissionService,
            $this->admissionRepo,
            new AcademicRepository($this->db)
        );

        // 1. Applications Index View
        $req1 = new \App\Core\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/admissions/applications']);
        $req1->setAttribute('user_context', $adminActor);
        $res1 = $controller->index($req1);
        $this->assertSame(200, $res1->getStatusCode());
        $this->assertStringContainsString('Admissions Portal', $res1->getContent());

        // 2. Admission Sessions View
        $req2 = new \App\Core\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/admissions/sessions']);
        $req2->setAttribute('user_context', $adminActor);
        $res2 = $controller->sessions($req2);
        $this->assertSame(200, $res2->getStatusCode());
        $this->assertStringContainsString('Admission Sessions', $res2->getContent());

        // 3. Application Show View
        $app = $this->admissionService->getApplicantActiveApplication($this->applicantUserId);
        $this->assertNotNull($app);
        $req3 = new \App\Core\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/admissions/applications/' . $app->id]);
        $req3->setAttribute('user_context', $adminActor);
        $res3 = $controller->show($req3, (string)$app->id);
        $this->assertSame(200, $res3->getStatusCode());
        $this->assertStringContainsString($app->applicationNumber, $res3->getContent());
    }
}
