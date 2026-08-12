<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Policies\AssignmentPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\AssignmentRepository;
use App\Repositories\TeacherRepository;
use App\Services\AssignmentService;

/**
 * Controller for Teacher Submission Review and Grading
 */
class SubmissionController extends Controller
{
    private AssignmentService $assignmentService;
    private AssignmentRepository $assignmentRepository;
    private AcademicRepository $academicRepository;
    private TeacherRepository $teacherRepository;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?AssignmentService $assignmentService = null,
        ?AssignmentRepository $assignmentRepository = null,
        ?AcademicRepository $academicRepository = null,
        ?TeacherRepository $teacherRepository = null
    ) {
        parent::__construct($authenticator);
        $this->assignmentService = $assignmentService ?? new AssignmentService();
        $this->assignmentRepository = $assignmentRepository ?? new AssignmentRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->teacherRepository = $teacherRepository ?? new TeacherRepository();
    }

    /**
     * List submissions for an assignment.
     * Route: GET /teacher/assignments/{id}/submissions
     */
    public function index(Request $request, array $params): Response
    {
        $userContext = $this->authenticator->getUserContext();
        $assignmentId = (int)($params['id'] ?? 0);

        $assignment = $this->assignmentRepository->findById($assignmentId);
        if (!$assignment) {
            return $this->view('errors/404', ['message' => 'Assignment not found.'], 404);
        }

        if (!AssignmentPolicy::canViewAssignment(
            $userContext,
            $assignment,
            $this->academicRepository,
            $this->teacherRepository
        )) {
            return $this->view('errors/403', ['message' => 'You are not authorized to view submissions for this assignment.'], 403);
        }

        $submissions = $this->assignmentRepository->getSubmissionsForAssignment($assignmentId);

        return $this->view('teacher/assignments/submissions', [
            'assignment' => $assignment,
            'submissions' => $submissions,
            'user' => $userContext,
        ]);
    }

    /**
     * Grade a student's submission.
     * Route: POST /teacher/submissions/{id}/grade
     */
    public function grade(Request $request, array $params): Response
    {
        $userContext = $this->authenticator->getUserContext();
        $submissionId = (int)($params['id'] ?? 0);

        $score = (float)$request->post('score', 0.0);
        $teacherComment = $request->post('teacher_comment', null);

        try {
            $result = $this->assignmentService->gradeSubmission(
                $submissionId,
                $score,
                $teacherComment,
                $userContext
            );

            $submission = $result->data;
            $assignmentId = $submission ? $submission->assignmentId : 0;

            return $this->redirectWithSuccess(
                "/teacher/assignments/{$assignmentId}/submissions",
                'Submission graded successfully.'
            );
        } catch (ValidationException $e) {
            $submission = $this->assignmentRepository->findSubmissionById($submissionId);
            $assignmentId = $submission ? $submission->assignmentId : 0;
            return $this->redirectWithErrors("/teacher/assignments/{$assignmentId}/submissions", $e->getErrors());
        } catch (ResourceNotFoundException $e) {
            return $this->view('errors/404', ['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            return $this->redirectWithError('/teacher/assignments', $e->getMessage());
        }
    }
}
