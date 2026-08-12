<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Services\AssignmentService;

/**
 * Controller for Student Assignment Submission
 */
class SubmissionController extends Controller
{
    private AssignmentService $assignmentService;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?AssignmentService $assignmentService = null
    ) {
        parent::__construct($authenticator);
        $this->assignmentService = $assignmentService ?? new AssignmentService();
    }

    /**
     * Submit assignment response (text and/or file).
     * Route: POST /student/assignments/{id}/submit
     */
    public function store(Request $request, array $params): Response
    {
        $userContext = $this->authenticator->getUserContext();
        $assignmentId = (int)($params['id'] ?? 0);
        $postData = $request->post();
        $files = $request->files();
        $uploadedFile = $files['attachment'] ?? null;

        try {
            $this->assignmentService->submitAssignment(
                $assignmentId,
                $postData,
                $uploadedFile,
                $userContext
            );

            return $this->redirectWithSuccess(
                "/student/assignments/{$assignmentId}",
                'Your assignment has been submitted successfully.'
            );
        } catch (ValidationException $e) {
            return $this->redirectWithErrors("/student/assignments/{$assignmentId}", $e->getErrors(), $postData);
        } catch (DomainRuleException $e) {
            return $this->redirectWithError("/student/assignments/{$assignmentId}", $e->getMessage());
        } catch (ResourceNotFoundException $e) {
            return $this->view('errors/404', ['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            return $this->redirectWithError("/student/assignments/{$assignmentId}", $e->getMessage());
        }
    }
}
