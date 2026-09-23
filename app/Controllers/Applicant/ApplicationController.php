<?php

declare(strict_types=1);

namespace App\Controllers\Applicant;

use App\Controllers\Controller;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\AdmissionApplication;
use App\Models\AdmissionWard;
use App\Services\AdmissionService;
use App\Services\FileStorageService;

/**
 * Controller for Prospective Ward Application Management, Document Uploads, and Submission
 */
class ApplicationController extends Controller
{
    private AdmissionService $admissionService;
    private FileStorageService $fileStorageService;

    public function __construct(
        ?AdmissionService $admissionService = null,
        ?FileStorageService $fileStorageService = null
    ) {
        parent::__construct();
        $this->admissionService = $admissionService ?? new AdmissionService();
        $this->fileStorageService = $fileStorageService ?? new FileStorageService();
    }

    /**
     * Application Workspace & Ward List: GET /applicant/application
     */
    public function index(Request $request): Response
    {
        $user = $this->requireAuthContext($request);
        $app = $this->admissionService->getApplicantActiveApplication($user->id);
        $session = $this->admissionService->getActiveSession();
        $levels = $this->admissionService->getAcademicLevels();

        $wards = $app ? $this->admissionService->getApplicantDashboardData($user->id)['applications'][0]->wards ?? [] : [];

        return $this->view('applicant/application/index', [
            'title' => 'Admission Application Workspace — Claret',
            'user' => $user,
            'application' => $app,
            'session' => $session,
            'levels' => $levels,
            'wards' => $wards,
            'errors' => Session::getFlash('errors', []),
        ]);
    }

    /**
     * Show Ward Creation Form: GET /applicant/wards/create
     */
    public function createWard(Request $request): Response
    {
        $user = $this->requireAuthContext($request);
        $app = $this->admissionService->getApplicantActiveApplication($user->id);

        if (!$app || $app->status !== AdmissionApplication::STATUS_DRAFT) {
            return $this->redirectWithError('/applicant/application', 'Cannot add wards to an application that has already been submitted.');
        }

        $session = $this->admissionService->getActiveSession();
        $levels = $this->admissionService->getAcademicLevels();

        return $this->view('applicant/application/ward_form', [
            'title' => 'Add Prospective Ward — Claret Admissions',
            'user' => $user,
            'application' => $app,
            'session' => $session,
            'levels' => $levels,
            'ward' => null,
            'errors' => Session::getFlash('errors', []),
        ]);
    }

    /**
     * Process Ward Creation: POST /applicant/wards
     */
    public function storeWard(Request $request): Response
    {
        $user = $this->requireAuthContext($request);

        try {
            $validated = $this->validate($request, [
                'first_name' => 'required|min:2|max:100',
                'middle_name' => 'max:100',
                'last_name' => 'required|min:2|max:100',
                'date_of_birth' => 'required',
                'gender' => 'required|in:male,female',
                'applying_for_level_id' => 'required|integer',
                'class_grade' => 'required|min:2|max:50',
                'curriculum_choice' => 'max:50',
                'use_school_bus' => 'max:5',
                'previous_school' => 'max:255',
                'last_grade_passed' => 'max:50',
                'medical_notes' => 'max:1000',
                'state_of_origin' => 'max:100',
                'lga' => 'max:100',
                'nationality' => 'max:100',
                'religion' => 'max:100',
            ]);
        } catch (ValidationException $e) {
            return $this->redirectWithErrors('/applicant/wards/create', $e->getErrors(), $request->all());
        }

        $result = $this->admissionService->addWard($user->id, $validated);
        if (!$result->isSuccess()) {
            return $this->redirectWithErrors('/applicant/wards/create', ['general' => [$result->getMessage()]], $request->all());
        }

        $ward = $result->getData();
        return $this->redirectWithSuccess(
            "/applicant/payment/{$ward->id}",
            "Prospective ward \"{$ward->getFullName()}\" added! Please complete the application fee payment to proceed."
        );
    }

    /**
     * Edit Ward Details: GET /applicant/wards/{id}/edit
     */
    public function editWard(Request $request, string $id): Response
    {
        $user = $this->requireAuthContext($request);
        $wardId = (int)$id;
        $ward = $this->admissionService->getWard($wardId, $user->id);

        if (!$ward) {
            return $this->notFound('Ward record not found or access denied.');
        }

        $app = $this->admissionService->getApplicantActiveApplication($user->id);
        if (!$app || $app->status !== AdmissionApplication::STATUS_DRAFT) {
            return $this->redirectWithError('/applicant/application', 'Cannot edit ward details once submitted.');
        }

        $session = $this->admissionService->getActiveSession();
        $levels = $this->admissionService->getAcademicLevels();

        return $this->view('applicant/application/ward_form', [
            'title' => 'Edit Ward Details — Claret Admissions',
            'user' => $user,
            'application' => $app,
            'session' => $session,
            'levels' => $levels,
            'ward' => $ward,
            'errors' => Session::getFlash('errors', []),
        ]);
    }

    /**
     * Update Ward Details: POST /applicant/wards/{id}
     */
    public function updateWard(Request $request, string $id): Response
    {
        $user = $this->requireAuthContext($request);
        $wardId = (int)$id;

        try {
            $validated = $this->validate($request, [
                'first_name' => 'required|min:2|max:100',
                'middle_name' => 'max:100',
                'last_name' => 'required|min:2|max:100',
                'date_of_birth' => 'required',
                'gender' => 'required|in:male,female',
                'applying_for_level_id' => 'required|integer',
                'class_grade' => 'required|min:2|max:50',
                'curriculum_choice' => 'max:50',
                'use_school_bus' => 'max:5',
                'previous_school' => 'max:255',
                'last_grade_passed' => 'max:50',
                'medical_notes' => 'max:1000',
                'state_of_origin' => 'max:100',
                'lga' => 'max:100',
                'nationality' => 'max:100',
                'religion' => 'max:100',
            ]);
        } catch (ValidationException $e) {
            return $this->redirectWithErrors("/applicant/wards/{$wardId}/edit", $e->getErrors(), $request->all());
        }

        $result = $this->admissionService->updateWard($wardId, $user->id, $validated);
        if (!$result->isSuccess()) {
            return $this->redirectWithErrors("/applicant/wards/{$wardId}/edit", ['general' => [$result->getMessage()]], $request->all());
        }

        return $this->redirectWithSuccess('/applicant/application', 'Ward details updated successfully.');
    }

    /**
     * Delete Ward: POST /applicant/wards/{id}/delete
     */
    public function deleteWard(Request $request, string $id): Response
    {
        $user = $this->requireAuthContext($request);
        $wardId = (int)$id;

        $result = $this->admissionService->deleteWard($wardId, $user->id);
        if (!$result->isSuccess()) {
            return $this->redirectWithError('/applicant/application', $result->getMessage());
        }

        return $this->redirectWithSuccess('/applicant/application', 'Ward removed from application docket.');
    }

    /**
     * Document Upload Screen: GET /applicant/wards/{id}/documents
     */
    public function showDocuments(Request $request, string $id): Response
    {
        $user = $this->requireAuthContext($request);
        $wardId = (int)$id;
        $ward = $this->admissionService->getWard($wardId, $user->id);

        if (!$ward) {
            return $this->notFound('Ward record not found or access denied.');
        }

        if ($ward->paymentStatus !== AdmissionWard::PAYMENT_PAID) {
            return $this->redirectWithWarning(
                "/applicant/payment/{$wardId}",
                'Please complete application fee payment before uploading ward documents.'
            );
        }

        $app = $this->admissionService->getApplicantActiveApplication($user->id);

        return $this->view('applicant/application/documents', [
            'title' => 'Upload Documents — ' . $ward->getFullName(),
            'user' => $user,
            'application' => $app,
            'ward' => $ward,
            'errors' => Session::getFlash('errors', []),
        ]);
    }

    /**
     * Process Document Upload: POST /applicant/wards/{id}/documents
     */
    public function uploadDocument(Request $request, string $id): Response
    {
        $user = $this->requireAuthContext($request);
        $wardId = (int)$id;
        $ward = $this->admissionService->getWard($wardId, $user->id);

        if (!$ward) {
            return $this->notFound('Ward record not found or access denied.');
        }

        $documentType = (string)$request->post('document_type', '');
        $files = $request->files ?? $_FILES;
        $file = $files['document'] ?? null;

        if (!$file || empty($file['tmp_name'])) {
            return $this->redirectWithError("/applicant/wards/{$wardId}/documents", 'Please select a file to upload.');
        }

        try {
            $fileRecord = $this->fileStorageService->storeUploadedFile(
                file: $file,
                uploadedBy: $user->id,
                ownerType: 'admission_ward',
                ownerId: $wardId
            );

            $result = $this->admissionService->attachDocumentToWard(
                wardId: $wardId,
                applicantUserId: $user->id,
                documentType: $documentType,
                fileId: $fileRecord->id
            );

            if (!$result->isSuccess()) {
                return $this->redirectWithError("/applicant/wards/{$wardId}/documents", $result->getMessage());
            }

            return $this->redirectWithSuccess(
                "/applicant/wards/{$wardId}/documents",
                ucwords(str_replace('_', ' ', $documentType)) . ' uploaded and attached successfully!'
            );
        } catch (ValidationException $e) {
            return $this->redirectWithErrors("/applicant/wards/{$wardId}/documents", $e->getErrors());
        } catch (\Throwable $e) {
            return $this->redirectWithError("/applicant/wards/{$wardId}/documents", 'Upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Final Submission of Application Docket: POST /applicant/applications/{id}/submit
     */
    public function submitApplication(Request $request, string $id): Response
    {
        $user = $this->requireAuthContext($request);
        $appId = (int)$id;

        $result = $this->admissionService->submitApplication($appId, $user->id);
        if (!$result->isSuccess()) {
            return $this->redirectWithError('/applicant/application', $result->getMessage());
        }

        return $this->redirectWithSuccess(
            '/applicant/progress',
            'Application submitted successfully! Our admissions committee will review your application and keep you updated.'
        );
    }

    /**
     * Application Milestone Tracker: GET /applicant/progress
     */
    public function progress(Request $request): Response
    {
        $user = $this->requireAuthContext($request);
        $data = $this->admissionService->getApplicantDashboardData($user->id);
        $app = $data['applications'][0] ?? null;

        return $this->view('applicant/progress', [
            'title' => 'Application Progress — Claret Admissions',
            'user' => $user,
            'application' => $app,
            'session' => $data['activeSession'],
        ]);
    }
}
