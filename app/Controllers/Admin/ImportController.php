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
use App\Repositories\ImportRepository;
use App\Services\ImportService;

/**
 * Controller for CSV Bulk User Imports Administration
 */
class ImportController extends Controller
{
    private ImportService $importService;
    private ImportRepository $importRepository;

    public function __construct(
        ?ImportService $importService = null,
        ?ImportRepository $importRepository = null
    ) {
        $this->importService = $importService ?? new ImportService();
        $this->importRepository = $importRepository ?? new ImportRepository();
    }

    public function show(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canManageImports($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $recentImports = $this->importRepository->getRecentImports(15);

        return $this->view('admin/imports/index', [
            'title' => 'Bulk User Imports — Claret LMS',
            'headerTitle' => 'Bulk User CSV Imports',
            'recentImports' => $recentImports,
        ]);
    }

    public function validateCsv(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canManageImports($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $type = (string)$request->post('type', 'students');
        $csvText = (string)$request->post('csv_content', '');
        $fileName = 'direct_input.csv';

        // Check if a file was uploaded via $_FILES
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

            // Store valid rows in session temporarily for commit
            $_SESSION['_import_valid_rows_' . $batchId] = $data['valid_rows'];

            return $this->redirectWithSuccess("/admin/imports/{$batchId}/review", 'CSV processed and validated.');
        } catch (ValidationException|DomainRuleException $e) {
            return $this->redirectWithError('/admin/imports/users', $e->getMessage());
        }
    }

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
                "Import committed successfully! Created {$data['committed_count']} user record(s)."
            );
        } catch (DomainRuleException|ResourceNotFoundException $e) {
            return $this->redirectWithError("/admin/imports/{$batchId}/review", $e->getMessage());
        }
    }

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
}
