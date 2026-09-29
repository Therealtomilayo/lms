<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Policies\UserPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\ImportRepository;
use App\Services\ImportService;

/**
 * Controller for Bulk Onboarding and Imports Administration
 */
class ImportController extends Controller
{
    private ImportService $importService;
    private ImportRepository $importRepository;
    private AcademicRepository $academicRepository;

    public function __construct(
        ?ImportService $importService = null,
        ?ImportRepository $importRepository = null,
        ?AcademicRepository $academicRepository = null
    ) {
        $this->importService = $importService ?? new ImportService();
        $this->importRepository = $importRepository ?? new ImportRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
    }

    /**
     * Show main onboarding & bulk import dashboard.
     */
    public function show(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canManageImports($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $recentImports = $this->importRepository->getRecentImports(20);
        $sessions = $this->academicRepository->getAllSessions();
        $currentSession = $this->academicRepository->getCurrentSession();
        $classes = $this->academicRepository->getAllClasses();
        $classMappings = $this->importRepository->getAllClassMappings();

        return $this->view('admin/imports/index', [
            'title' => 'Bulk Student & Staff Onboarding — Claret LMS',
            'headerTitle' => 'Bulk Onboarding & Data Import',
            'recentImports' => $recentImports,
            'sessions' => $sessions,
            'currentSession' => $currentSession,
            'classes' => $classes,
            'classMappings' => $classMappings,
        ]);
    }

    /**
     * API: Initialize an import batch prior to chunked upload.
     */
    public function initBatch(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canManageImports($userContext)) {
            return Response::json(['success' => false, 'error' => 'Unauthorized action.'], 403);
        }

        $type = (string)$request->input('type', 'students');
        $fileName = (string)$request->input('file_name', 'spreadsheet.xlsx');
        $sha256 = (string)$request->input('sha256', '');
        $sessionId = $request->input('session_id') !== null ? (int)$request->input('session_id') : null;
        $totalRows = (int)$request->input('total_rows', 0);

        if ($sha256 === '') {
            return Response::json(['success' => false, 'error' => 'File checksum (SHA256) is required.'], 422);
        }

        try {
            $result = $this->importService->initBatch(
                uploadedBy: $userContext->getUserId(),
                type: $type,
                fileName: $fileName,
                sha256: $sha256,
                sessionId: $sessionId,
                totalRows: $totalRows
            );

            return Response::json([
                'success' => true,
                'data' => $result->getData(),
            ]);
        } catch (ValidationException|DomainRuleException|ResourceNotFoundException $e) {
            return Response::json(['success' => false, 'error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return Response::json(['success' => false, 'error' => 'Internal server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Resolve raw class strings from spreadsheet using conservative rules.
     */
    public function resolveClasses(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canManageImports($userContext)) {
            return Response::json(['success' => false, 'error' => 'Unauthorized action.'], 403);
        }

        $classStrings = (array)$request->input('class_strings', []);
        $sessionId = $request->input('session_id') !== null ? (int)$request->input('session_id') : null;

        try {
            $result = $this->importService->resolveClasses($classStrings, $sessionId);

            return Response::json([
                'success' => true,
                'data' => $result->getData(),
            ]);
        } catch (\Throwable $e) {
            return Response::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Save human-mapped classes for persistence across future imports.
     */
    public function saveClassMappings(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canManageImports($userContext)) {
            return Response::json(['success' => false, 'error' => 'Unauthorized action.'], 403);
        }

        $mappings = (array)$request->input('mappings', []);

        try {
            $result = $this->importService->saveClassMappings($mappings, $userContext->getUserId());

            return Response::json([
                'success' => true,
                'data' => $result->getData(),
            ]);
        } catch (\Throwable $e) {
            return Response::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Process a single chunk of import rows transactionally.
     */
    public function processChunk(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canManageImports($userContext)) {
            return Response::json(['success' => false, 'error' => 'Unauthorized action.'], 403);
        }

        $batchId = (int)$id;
        $chunkNumber = (int)$request->input('chunk_number', 1);
        $totalChunks = (int)$request->input('total_chunks', 1);
        $rows = (array)$request->input('rows', []);

        if (empty($rows)) {
            return Response::json(['success' => false, 'error' => 'No rows provided in chunk payload.'], 422);
        }

        try {
            $result = $this->importService->processChunk(
                importId: $batchId,
                chunkNumber: $chunkNumber,
                totalChunks: $totalChunks,
                rows: $rows,
                actor: $userContext
            );

            return Response::json([
                'success' => true,
                'data' => $result->getData(),
            ]);
        } catch (DomainRuleException|ResourceNotFoundException $e) {
            return Response::json(['success' => false, 'error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return Response::json(['success' => false, 'error' => 'Chunk processing failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Finalize import batch upon completion of all chunks.
     */
    public function finalizeBatch(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canManageImports($userContext)) {
            return Response::json(['success' => false, 'error' => 'Unauthorized action.'], 403);
        }

        $batchId = (int)$id;

        try {
            $result = $this->importService->finalizeBatch($batchId, $userContext);

            return Response::json([
                'success' => true,
                'data' => $result->getData(),
            ]);
        } catch (DomainRuleException|ResourceNotFoundException $e) {
            return Response::json(['success' => false, 'error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return Response::json(['success' => false, 'error' => 'Batch finalization failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download CSV report of row-level errors for an import batch.
     */
    public function downloadErrors(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canManageImports($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $batchId = (int)$id;
        $batch = $this->importRepository->findById($batchId);
        if (!$batch) {
            return Response::html('Import batch not found', 404);
        }

        $errors = $batch->errors;
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, ['Row Number', 'Errors', 'Raw Data']);

        foreach ($errors as $err) {
            fputcsv($fp, [
                $err['row_number'],
                implode('; ', $err['errors']),
                json_encode($err['raw_data']),
            ]);
        }

        rewind($fp);
        $csvOutput = stream_get_contents($fp);
        fclose($fp);

        return Response::download("import_errors_{$batchId}.csv", $csvOutput, 'text/csv');
    }

    /**
     * Legacy CSV validation endpoint.
     */
    public function validateCsv(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canManageImports($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $type = (string)$request->post('type', 'students');
        $csvText = (string)$request->post('csv_content', '');
        $fileName = 'direct_input.csv';

        if (!empty($_FILES['csv_file']['tmp_name']) && is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
            $csvText = (string)file_get_contents($_FILES['csv_file']['tmp_name']);
            $fileName = (string)($_FILES['csv_file']['name'] ?? 'upload.csv');
        }

        if (trim($csvText) === '') {
            return $this->redirectWithError('/admin/imports/users', 'Please provide a CSV file or paste CSV content.');
        }

        try {
            $result = $this->importService->validateCsv(
                csvContent: $csvText,
                type: $type,
                originalName: $fileName,
                uploadedBy: $userContext->getUserId()
            );

            $data = $result->getData();
            $batchId = $data['batch']->id;
            $_SESSION['_import_valid_rows_' . $batchId] = $data['valid_rows'];

            return $this->redirectWithSuccess("/admin/imports/{$batchId}/review", 'CSV processed and validated.');
        } catch (ValidationException|DomainRuleException $e) {
            return $this->redirectWithError('/admin/imports/users', $e->getMessage());
        }
    }

    /**
     * Legacy review view.
     */
    public function review(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canManageImports($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $batchId = (int)$id;
        $batch = $this->importRepository->findById($batchId);
        if (!$batch) {
            return Response::html('Import batch not found', 404);
        }

        $validRows = $_SESSION['_import_valid_rows_' . $batchId] ?? [];

        return $this->view('admin/imports/review', [
            'title' => "Review Import #{$batchId} — Claret LMS",
            'headerTitle' => "Review Import: {$batch->originalName}",
            'batch' => $batch,
            'validRows' => $validRows,
            'errors' => $batch->errors,
        ]);
    }

    /**
     * Legacy commit endpoint.
     */
    public function commit(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canManageImports($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $batchId = (int)$id;
        $validRows = $_SESSION['_import_valid_rows_' . $batchId] ?? [];

        try {
            $res = $this->importService->commitImport($batchId, $validRows, $userContext);
            unset($_SESSION['_import_valid_rows_' . $batchId]);

            $data = $res->getData();
            return $this->redirectWithSuccess(
                '/admin/imports/users',
                "Import committed successfully! Created {$data['created_count']} record(s)."
            );
        } catch (DomainRuleException|ResourceNotFoundException $e) {
            return $this->redirectWithError("/admin/imports/{$batchId}/review", $e->getMessage());
        }
    }
}
