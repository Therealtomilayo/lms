<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Models\User;
use App\Repositories\AdmissionRepository;
use App\Repositories\UserRepository;
use App\Services\AdmissionService;
use App\Services\AuthService;
use App\Services\FileStorageService;
use PDO;
use PHPUnit\Framework\TestCase;

class ApplicantWorkflowTest extends TestCase
{
    private PDO $db;
    private AdmissionService $admissionService;
    private AdmissionRepository $admissionRepo;
    private AuthService $authService;
    private int $applicantUserId;
    private int $sessionId;
    protected function setUp(): void
    {
        $this->db = Database::getConnection();
        $this->admissionRepo = new AdmissionRepository($this->db);
        $userRepo = new UserRepository($this->db);
        $this->admissionService = new AdmissionService($this->admissionRepo, $userRepo);
        $this->authService = new AuthService($userRepo, $this->db);

        // Register test applicant
        $email = 'applicant_test_' . uniqid() . '@example.com';
        $res = $this->admissionService->registerApplicant('Guardian Tester', $email, 'Password123!', '08012345678');
        if (!$res->isSuccess()) {
            throw new \RuntimeException('Failed to register test applicant: ' . json_encode($res->getErrors()));
        }
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
            $this->db->prepare("DELETE FROM admission_payments WHERE ward_id IN (SELECT id FROM admission_wards WHERE application_id = ?)")->execute([$appId]);
            $this->db->prepare("DELETE FROM admission_status_history WHERE application_id = ?")->execute([$appId]);
            $this->db->prepare("DELETE FROM admission_wards WHERE application_id = ?")->execute([$appId]);
            $this->db->prepare("DELETE FROM admission_applications WHERE id = ?")->execute([$appId]);
        }

        $this->db->prepare("DELETE FROM user_roles WHERE user_id = ?")->execute([$this->applicantUserId]);
        $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$this->applicantUserId]);
        \App\Core\Session::destroy();
    }

    public function testApplicantWorkflowFullCycle(): void
    {
        // 1. Verify active application is retrieved
        $application = $this->admissionService->getApplicantActiveApplication($this->applicantUserId);
        $this->assertNotNull($application);
        $this->assertEquals('draft', $application->status);
        $appId = (int)$application->id;

        // 2. Add Ward
        $levels = $this->admissionService->getAcademicLevels();
        $levelId = !empty($levels) ? (int)$levels[0]->id : 1;

        $wardData = [
            'first_name' => 'Chukwu',
            'middle_name' => 'David',
            'last_name' => 'Tester',
            'gender' => 'male',
            'date_of_birth' => '2015-05-12',
            'state_of_origin' => 'Imo',
            'lga' => 'Owerri Municipal',
            'applying_for_level_id' => $levelId,
            'class_grade' => 'JSS 1',
            'previous_school' => 'Claret Primary School',
            'previous_class' => 'Primary 6'
        ];
        $addResult = $this->admissionService->addWard($this->applicantUserId, $wardData);
        $this->assertTrue($addResult->isSuccess(), $addResult->getMessage() ?? '');
        $ward = $addResult->getData();
        $wardId = (int)$ward->id;
        $this->assertGreaterThan(0, $wardId);

        // Fetch ward
        $ward = $this->admissionService->getWard($wardId, $this->applicantUserId);
        $this->assertNotNull($ward);
        $this->assertEquals('Chukwu', $ward->firstName);
        $this->assertEquals('unpaid', $ward->paymentStatus);

        // 3. Attempt to submit while unpaid - should fail
        $submitFail = $this->admissionService->submitApplication($appId, $this->applicantUserId);
        $this->assertFalse($submitFail->isSuccess());
        $this->assertStringContainsString('paid', $submitFail->getMessage() ?? '');

        // 4. Initialize Payment & Simulate Payment confirmation
        $payInit = $this->admissionService->initializeWardPayment($wardId, $this->applicantUserId, 'https://lms.test/applicant/payment/callback');
        $this->assertTrue($payInit->isSuccess(), $payInit->getMessage() ?? '');
        $payData = $payInit->getData();
        $this->assertArrayHasKey('reference', $payData);
        $reference = $payData['reference'];

        $simulated = $this->admissionService->simulateSuccessfulWardPayment($reference, $this->applicantUserId);
        $this->assertTrue($simulated->isSuccess(), $simulated->getMessage() ?? '');

        // Verify ward is now paid
        $wardAfterPay = $this->admissionService->getWard($wardId, $this->applicantUserId);
        $this->assertEquals('paid', $wardAfterPay->paymentStatus);

        // 5. Attempt submit without required documents - should fail
        $docFail = $this->admissionService->submitApplication($appId, $this->applicantUserId);
        $this->assertFalse($docFail->isSuccess());
        $this->assertStringContainsString('birth certificate', $docFail->getMessage() ?? '');

        // 6. Attach documents
        $fileRepo = new \App\Repositories\FileRepository($this->db);
        $birthCert = $fileRepo->create(
            uuid: 'test-birth-cert-' . uniqid(),
            storageKey: 'storage/uploads/admissions/' . uniqid() . '_birth_cert.pdf',
            originalName: 'birth_cert.pdf',
            mimeType: 'application/pdf',
            sizeBytes: 1024,
            sha256: hash('sha256', 'dummy1' . uniqid()),
            uploadedBy: $this->applicantUserId,
            ownerType: 'admission_ward',
            ownerId: $wardId
        );
        $birthCertFileId = (int)$birthCert->id;

        $passport = $fileRepo->create(
            uuid: 'test-passport-' . uniqid(),
            storageKey: 'storage/uploads/admissions/' . uniqid() . '_passport.jpg',
            originalName: 'passport.jpg',
            mimeType: 'image/jpeg',
            sizeBytes: 2048,
            sha256: hash('sha256', 'dummy2' . uniqid()),
            uploadedBy: $this->applicantUserId,
            ownerType: 'admission_ward',
            ownerId: $wardId
        );
        $passportFileId = (int)$passport->id;

        $attachBirth = $this->admissionService->attachDocumentToWard($wardId, $this->applicantUserId, 'birth_certificate', $birthCertFileId);
        $this->assertTrue($attachBirth->isSuccess());

        $attachPass = $this->admissionService->attachDocumentToWard($wardId, $this->applicantUserId, 'passport_photo', $passportFileId);
        $this->assertTrue($attachPass->isSuccess());

        // Verify ward documents attached
        $wardWithDocs = $this->admissionService->getWard($wardId, $this->applicantUserId);
        $this->assertEquals($birthCertFileId, $wardWithDocs->birthCertificateFileId);
        $this->assertEquals($passportFileId, $wardWithDocs->passportPhotoFileId);

        // 7. Submit Application docket
        $submitSuccess = $this->admissionService->submitApplication($appId, $this->applicantUserId);
        $this->assertTrue($submitSuccess->isSuccess(), $submitSuccess->getMessage() ?? '');

        // Verify application status changed to submitted and submitted_at recorded
        $refreshedApp = $this->admissionService->getApplicantActiveApplication($this->applicantUserId);
        $this->assertEquals('submitted', $refreshedApp->status);
        $this->assertNotNull($refreshedApp->submittedAt);

        // Clean up dummy files
        $this->db->prepare("DELETE FROM files WHERE id IN (?, ?)")->execute([$birthCertFileId, $passportFileId]);
    }
}
