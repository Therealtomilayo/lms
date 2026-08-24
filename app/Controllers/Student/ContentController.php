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
 * Controller for Student Learning Materials & Course Study Content
 */
class ContentController extends Controller
{
    private ContentService $contentService;
    private AcademicRepository $academicRepo;
    private StudentRepository $studentRepo;
    private EnrollmentRepository $enrollmentRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ContentService $contentService = null,
        ?AcademicRepository $academicRepo = null,
        ?StudentRepository $studentRepo = null,
        ?EnrollmentRepository $enrollmentRepo = null
    ) {
        parent::__construct($authenticator);
        $this->contentService = $contentService ?? new ContentService();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->enrollmentRepo = $enrollmentRepo ?? new EnrollmentRepository();
    }

    /**
     * List study materials for student's enrolled subjects.
     * Route: GET /student/content
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $student = $this->studentRepo->findByUserId($userContext->id);

        if (!$student && !$userContext->isAdmin()) {
            return Response::forbidden('Student profile not found.');
        }

        $activeSession = $this->academicRepo->findCurrentSession();
        $activeTerm = $this->academicRepo->findCurrentTerm();
        $sessionId = $activeSession ? $activeSession->id : 0;
        
        $subjectEnrollments = ($student && $sessionId > 0)
            ? $this->enrollmentRepo->getStudentSubjectEnrollments($student->id, $sessionId)
            : [];

        $selectedClassSubjectId = (int)$request->get('class_subject_id', 0);
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
                // Ignore error if student isn't enrolled
            }
        } elseif (!empty($subjectEnrollments)) {
            $firstSubject = (int)$subjectEnrollments[0]->classSubjectId;
            $selectedClassSubjectId = $firstSubject;
            try {
                $result = $this->contentService->getContentForStudent($firstSubject, $userContext);
                $items = $result->data['items'] ?? [];
                $topics = $result->data['topics'] ?? [];
                $selectedClassSubject = $result->data['class_subject'] ?? null;
            } catch (\Throwable $e) {
                // Fallback
            }
        }

        return Response::html($this->render('student/content/index', [
            'title' => 'Learning Materials & Notes — Student Portal',
            'headerTitle' => 'Course Materials & Notes',
            'user' => $userContext,
            'student' => $student,
            'activeSession' => $activeSession,
            'activeTerm' => $activeTerm,
            'subjectEnrollments' => $subjectEnrollments,
            'selectedClassSubjectId' => $selectedClassSubjectId,
            'selectedClassSubject' => $selectedClassSubject,
            'items' => $items,
            'topics' => $topics,
        ], 'layouts/student'));
    }

    /**
     * View a single lesson/content item.
     * Route: GET /student/content/{id}
     */
    public function show(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $cId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;

        try {
            $result = $this->contentService->getContentItem($cId, $userContext);
            $item = $result->data['content_item'];

            return Response::html($this->render('student/content/show', [
                'title' => "{$item->title} — Study Material",
                'headerTitle' => 'Lesson Material',
                'user' => $userContext,
                'item' => $item,
            ], 'layouts/student'));
        } catch (ResourceNotFoundException | AuthorizationException $e) {
            return Response::notFound('Lesson material not found or access restricted.');
        }
    }
}
