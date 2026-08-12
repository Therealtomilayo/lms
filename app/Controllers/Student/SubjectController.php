<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\StudentRepository;
use App\Services\ContentService;

/**
 * Controller for Student Enrolled Subjects Overview
 */
class SubjectController extends Controller
{
    private ContentService $contentService;
    private AcademicRepository $academicRepository;
    private StudentRepository $studentRepository;
    private EnrollmentRepository $enrollmentRepository;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ContentService $contentService = null,
        ?AcademicRepository $academicRepository = null,
        ?StudentRepository $studentRepository = null,
        ?EnrollmentRepository $enrollmentRepository = null
    ) {
        parent::__construct($authenticator);
        $this->contentService = $contentService ?? new ContentService();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->studentRepository = $studentRepository ?? new StudentRepository();
        $this->enrollmentRepository = $enrollmentRepository ?? new EnrollmentRepository();
    }

    /**
     * List all subjects enrolled by the student in the active session.
     * Route: GET /student/subjects
     */
    public function index(Request $request): Response
    {
        $userContext = $this->authenticator->getUserContext();
        $student = $this->studentRepository->findByUserId($userContext->userId);

        if (!$student) {
            return $this->view('errors/403', ['message' => 'Student profile not found.'], 403);
        }

        $activeSession = $this->academicRepository->getActiveSession();
        $sessionId = $activeSession ? $activeSession->id : 0;
        $subjectEnrollments = $sessionId > 0
            ? $this->enrollmentRepository->getStudentSubjectEnrollments($student->id, $sessionId)
            : [];

        return $this->view('student/subjects/index', [
            'subjectEnrollments' => $subjectEnrollments,
            'activeSession' => $activeSession,
            'user' => $userContext,
            'student' => $student,
        ]);
    }

    /**
     * Show specific subject overview, teacher information, and syllabus / published materials.
     * Route: GET /student/subjects/{classSubjectId}
     */
    public function show(Request $request, array $params): Response
    {
        $userContext = $this->authenticator->getUserContext();
        $classSubjectId = (int)($params['classSubjectId'] ?? 0);

        try {
            $result = $this->contentService->getContentForStudent($classSubjectId, $userContext);
            $classSubject = $result->data['class_subject'];
            $items = $result->data['items'];
            $topics = $result->data['topics'];

            return $this->view('student/subjects/show', [
                'classSubject' => $classSubject,
                'items' => $items,
                'topics' => $topics,
                'user' => $userContext,
            ]);
        } catch (AuthorizationException | ResourceNotFoundException $e) {
            return $this->view('errors/404', ['message' => 'Subject not found or you are not enrolled.'], 404);
        }
    }
}
