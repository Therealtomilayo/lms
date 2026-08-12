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
 * Controller for Student Learning Materials & Course Content
 */
class ContentController extends Controller
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
     * List materials for student's enrolled subjects.
     * Route: GET /student/content
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

        $selectedClassSubjectId = (int)$request->query('class_subject_id', 0);
        $items = [];
        $topics = [];
        $selectedClassSubject = null;

        if ($selectedClassSubjectId > 0) {
            try {
                $result = $this->contentService->getContentForStudent($selectedClassSubjectId, $userContext);
                $items = $result->data['items'] ?? [];
                $topics = $result->data['topics'] ?? [];
                $selectedClassSubject = $result->data['class_subject'] ?? null;
            } catch (\Throwable $e) {
                // Return empty if unauthorized
            }
        } elseif (!empty($subjectEnrollments)) {
            $firstSubject = $subjectEnrollments[0]->classSubjectId;
            $selectedClassSubjectId = $firstSubject;
            try {
                $result = $this->contentService->getContentForStudent($firstSubject, $userContext);
                $items = $result->data['items'] ?? [];
                $topics = $result->data['topics'] ?? [];
                $selectedClassSubject = $result->data['class_subject'] ?? null;
            } catch (\Throwable $e) {
                // Return empty
            }
        }

        return $this->view('student/content/index', [
            'subjectEnrollments' => $subjectEnrollments,
            'selectedClassSubjectId' => $selectedClassSubjectId,
            'selectedClassSubject' => $selectedClassSubject,
            'items' => $items,
            'topics' => $topics,
            'activeSession' => $activeSession,
            'user' => $userContext,
            'student' => $student,
        ]);
    }

    /**
     * View a single lesson/content item.
     * Route: GET /student/content/{id}
     */
    public function show(Request $request, array $params): Response
    {
        $userContext = $this->authenticator->getUserContext();
        $id = (int)($params['id'] ?? 0);

        try {
            $result = $this->contentService->getContentItem($id, $userContext);
            $item = $result->data['content_item'];

            return $this->view('student/content/show', [
                'item' => $item,
                'user' => $userContext,
            ]);
        } catch (ResourceNotFoundException | AuthorizationException $e) {
            return $this->view('errors/404', ['message' => 'Lesson material not found or access restricted.'], 404);
        }
    }
}
