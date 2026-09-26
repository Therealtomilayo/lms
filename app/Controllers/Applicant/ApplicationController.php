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
        $session = $this->admissionService->getActiveSession();
        $levels = $this->admissionService->getAcademicLevels();

        $applications = $this->admissionRepo->getApplicationsByApplicant($user->id);

        $selectedAppId = (int)$request->query('app', 0);
        $app = null;
        if ($selectedAppId > 0) {
            foreach ($applications as $candidate) {
                if ($candidate->id === $selectedAppId) {
                    $app = $candidate;
                    break;
                }
            }
        }

        if (!$app) {
            $app = $this->admissionService->getApplicantActiveApplication($user->id, preferDraft: true);
        }

        $wards = $app ? $this->admissionRepo->getWardsForApplication($app->id) : [];

        return $this->view('applicant/application/index', [
            'title' => 'Admission Application Workspace — Claret',
            'user' => $user,
            'application' => $app,
            'applications' => $applications,
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
        $session = $this->admissionService->getActiveSession();
        if (!$session || !$session->isOpen()) {
            return $this->redirectWithError('/applicant/application', 'Admission applications are currently closed.');
        }

        // Get existing open draft docket or initialize a new draft docket if previous was submitted
        $app = $this->admissionService->getOrCreateDraftApplication($user->id);
        if (!$app) {
            return $this->redirectWithError('/applicant/application', 'Unable to initialize admission application docket.');
        }

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
                'class_grade' => 'required|min:1|max:50',
                'curriculum_choice' => 'max:100',
                'use_school_bus' => 'max:5',
                'previous_school' => 'required|min:2|max:255',
                'last_grade_passed' => 'required|min:1|max:50',
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

        // Process any direct document uploads from the ward creation form
        $files = $request->files ?? $_FILES;
        $directDocMap = [
            'passport_photo' => 'passport_photo',
            'parent_passport' => 'parent_passport',
            'immunization_record' => 'immunization_record',
        ];

        foreach ($directDocMap as $inputName => $docType) {
            if (!empty($files[$inputName]['tmp_name']) && $files[$inputName]['error'] === UPLOAD_ERR_OK) {
                try {
                    $fileRecord = $this->fileStorageService->storeUploadedFile(
                        file: $files[$inputName],
                        uploadedBy: $user->id,
                        ownerType: 'admission_ward',
                        ownerId: $ward->id
                    );
                    $this->admissionService->attachDocumentToWard(
                        wardId: $ward->id,
                        applicantUserId: $user->id,
                        documentType: $docType,
                        fileId: $fileRecord->id
                    );
                } catch (\Throwable) {
                    // Suppress and allow re-upload on documents screen
                }
            }
        }

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
                'class_grade' => 'required|min:1|max:50',
                'curriculum_choice' => 'max:100',
                'use_school_bus' => 'max:5',
                'previous_school' => 'required|min:2|max:255',
                'last_grade_passed' => 'required|min:1|max:50',
                'medical_notes' => 'max:1000',
                'state_of_origin' => 'max:100',
                'lga' => 'max:100',
                'nationality' => 'max:100',
                'religion' => 'max:100',
            ]);
        } catch (ValidationException $e) {
            return $this->redirectWithErrors("/applicant/wards/{$wardId}/edit", $e->getErrors(), $request->all());
        }

        // Process any direct document uploads from the ward edit form
        $files = $request->files ?? $_FILES;
        $directDocMap = [
            'passport_photo' => 'passport_photo',
            'parent_passport' => 'parent_passport',
            'immunization_record' => 'immunization_record',
        ];

        foreach ($directDocMap as $inputName => $docType) {
            if (!empty($files[$inputName]['tmp_name']) && $files[$inputName]['error'] === UPLOAD_ERR_OK) {
                try {
                    $fileRecord = $this->fileStorageService->storeUploadedFile(
                        file: $files[$inputName],
                        uploadedBy: $user->id,
                        ownerType: 'admission_ward',
                        ownerId: $wardId
                    );
                    $this->admissionService->attachDocumentToWard(
                        wardId: $wardId,
                        applicantUserId: $user->id,
                        documentType: $docType,
                        fileId: $fileRecord->id
                    );
                } catch (\Throwable) {
                    // Suppress and allow re-upload on documents screen
                }
            }
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

        $isJson = str_contains($request->header('Accept') ?? '', 'application/json')
            || $request->header('X-Requested-With') === 'XMLHttpRequest';

        if (!$ward) {
            if ($isJson) {
                return Response::json(['success' => false, 'message' => 'Ward record not found or access denied.'], 404);
            }
            return $this->notFound('Ward record not found or access denied.');
        }

        $app = $this->admissionRepo->findApplicationById($ward->applicationId);
        if (!$app || $app->status !== AdmissionApplication::STATUS_DRAFT) {
            if ($isJson) {
                return Response::json(['success' => false, 'message' => 'This application docket has been submitted and is locked under administrative assessment. Documents cannot be modified.'], 403);
            }
            return $this->redirectWithError("/applicant/wards/{$wardId}/documents", 'This application docket has been submitted and documents cannot be modified.');
        }

        $documentType = (string)$request->post('document_type', '');
        $files = $request->files ?? $_FILES;
        $file = $files['document'] ?? null;

        if (!$file || empty($file['tmp_name'])) {
            if ($isJson) {
                return Response::json(['success' => false, 'message' => 'Please select a file to upload.'], 422);
            }
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
                if ($isJson) {
                    return Response::json(['success' => false, 'message' => $result->getMessage()], 422);
                }
                return $this->redirectWithError("/applicant/wards/{$wardId}/documents", $result->getMessage());
            }

            if ($isJson) {
                return Response::json([
                    'success' => true,
                    'message' => ucwords(str_replace('_', ' ', $documentType)) . ' uploaded and attached successfully!',
                    'file_id' => $fileRecord->id,
                    'stream_url' => "/files/{$fileRecord->id}/stream",
                    'document_type' => $documentType,
                ]);
            }

            return $this->redirectWithSuccess(
                "/applicant/wards/{$wardId}/documents",
                ucwords(str_replace('_', ' ', $documentType)) . ' uploaded and attached successfully!'
            );
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $msg = !empty($errors) ? implode(' ', array_merge(...array_values($errors))) : 'Validation failed.';
            if ($isJson) {
                return Response::json(['success' => false, 'message' => $msg], 422);
            }
            return $this->redirectWithErrors("/applicant/wards/{$wardId}/documents", $errors);
        } catch (\Throwable $e) {
            if ($isJson) {
                return Response::json(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()], 500);
            }
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
