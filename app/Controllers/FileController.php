<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Services\FileStorageService;

/**
 * Controller for Protected File Downloads & Streams
 * Enforces ownership/relationship authorization policies before streaming bytes.
 */
class FileController extends Controller
{
    private FileStorageService $fileStorageService;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?FileStorageService $fileStorageService = null
    ) {
        parent::__construct($authenticator);
        $this->fileStorageService = $fileStorageService ?? new FileStorageService();
    }

    /**
     * Stream or download a protected file after authorization.
     * Route: GET /files/{id}/download
     */
    public function download(Request $request, array|string|int $id): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext || !$userContext->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $fileId = is_array($id) ? ($id['id'] ?? '') : (string)$id;
        if ($fileId === '') {
            return $this->view('errors/404', ['message' => 'File not found.'], 404);
        }

        try {
            $result = $this->fileStorageService->getFileForDownload($fileId, $userContext);
            $file = $result['file'];
            $path = $result['path'];

            return Response::download($path, $file->originalName, $file->mimeType);
        } catch (ResourceNotFoundException | AuthorizationException $e) {
            // Masked denial per 06-rbac-permissions.md
            return $this->view('errors/404', ['message' => 'File not found or access denied.'], 404);
        } catch (\Throwable $e) {
            return $this->view('errors/500', ['message' => 'An unexpected error occurred while retrieving the file.'], 500);
        }
    }
}
