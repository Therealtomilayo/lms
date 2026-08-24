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
    public function store(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $assignmentId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $postData = $request->all();
        $files = $_FILES ?? [];
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
            return $this->redirectWithError("/student/assignments/{$assignmentId}", implode(' ', $e->getErrors()));
        } catch (DomainRuleException $e) {
            return $this->redirectWithError("/student/assignments/{$assignmentId}", $e->getMessage());
        } catch (ResourceNotFoundException $e) {
            return Response::notFound($e->getMessage());
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        } catch (\Throwable $e) {
            return $this->redirectWithError("/student/assignments/{$assignmentId}", $e->getMessage());
        }
    }
}
