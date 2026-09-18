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
use App\Repositories\AssignmentRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\QuizRepository;
use App\Repositories\StudentRepository;
use App\Services\ContentService;
use App\Services\ModuleService;

/**
 * Controller for Student Enrolled Subjects & Unified Subject Workspace
 */
class SubjectController extends Controller
{
    private ContentService $contentService;
    private ModuleService $moduleService;
    private AcademicRepository $academicRepo;
    private StudentRepository $studentRepo;
    private EnrollmentRepository $enrollmentRepo;
    private AssignmentRepository $assignmentRepo;
    private QuizRepository $quizRepo;
    private GradebookRepository $gradebookRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ContentService $contentService = null,
        ?ModuleService $moduleService = null,
        ?AcademicRepository $academicRepo = null,
        ?StudentRepository $studentRepo = null,
        ?EnrollmentRepository $enrollmentRepo = null,
        ?AssignmentRepository $assignmentRepo = null,
        ?QuizRepository $quizRepo = null,
        ?GradebookRepository $gradebookRepo = null
    ) {
        parent::__construct($authenticator);
        $this->contentService = $contentService ?? new ContentService();
        $this->moduleService = $moduleService ?? new ModuleService();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->enrollmentRepo = $enrollmentRepo ?? new EnrollmentRepository();
        $this->assignmentRepo = $assignmentRepo ?? new AssignmentRepository();
        $this->quizRepo = $quizRepo ?? new QuizRepository();
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository();
    }

    /**
     * List all subjects enrolled by the student in the active session.
     * Route: GET /student/subjects
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

        $subjectProgressMap = [];
        if ($student && !empty($subjectEnrollments)) {
            foreach ($subjectEnrollments as $se) {
                $csId = (int)$se->classSubjectId;
                if ($csId > 0) {
                    $path = $this->moduleService->getLearningPathForStudent($csId, $student->id, $userContext);
                    $subjectProgressMap[$csId] = [
                        'progress_percent' => $path['course_progress_percent'],
                        'is_completed' => $path['is_course_completed'],
                        'has_modules' => $path['has_modules'],
                    ];
                }
            }
        }

        return Response::html($this->render('student/subjects/index', [
            'title' => 'My Enrolled Subjects — Student Learning Portal',
            'headerTitle' => 'Enrolled Academic Courses',
            'user' => $userContext,
            'student' => $student,
            'subjectEnrollments' => $subjectEnrollments,
            'subjectProgressMap' => $subjectProgressMap,
            'activeSession' => $activeSession,
            'activeTerm' => $activeTerm,
        ], 'layouts/student'));
    }

    /**
     * Show comprehensive subject workspace (Lessons, Coursework, CBTs & Grades).
     * Route: GET /student/subjects/{classSubjectId}
     */
    public function show(Request $request, array|string|int $classSubjectId): Response
    {
        $userContext = $this->requireAuthContext($request);
        $csId = is_array($classSubjectId) ? (int)($classSubjectId['classSubjectId'] ?? $classSubjectId['id'] ?? 0) : (int)$classSubjectId;

        $student = $this->studentRepo->findByUserId($userContext->id);
        $activeTerm = $this->academicRepo->findCurrentTerm();
        $termId = $activeTerm ? $activeTerm->id : 0;

        try {
            $result = $this->contentService->getContentForStudent($csId, $userContext);
            $classSubject = $result->data['class_subject'];
            $items = $result->data['items'] ?? [];
            $topics = $result->data['topics'] ?? [];

            // Fetch assignments for this subject
            $assignments = $termId > 0
                ? $this->assignmentRepo->findByClassSubjectAndTerm($csId, $termId)
                : [];

            // Fetch published quizzes for this subject
            $quizzes = [];
            if ($classSubject->teacherId > 0) {
                $teacherQuizzes = $this->quizRepo->findByTeacher($classSubject->teacherId, $csId, $termId > 0 ? $termId : null);
                $quizzes = array_filter($teacherQuizzes, fn($q) => $q->isPublished);
            }

            // Student subject score in gradebook
            $termResult = ($student && $termId > 0)
                ? $this->gradebookRepo->getTermResult($student->id, $csId, $termId)
                : null;

            // Fetch student attempts for all quizzes
            $studentAttempts = [];
            if ($student) {
                foreach ($quizzes as $quiz) {
                    $studentAttempts[$quiz->id] = $this->quizRepo->getStudentAttempts($quiz->id, $student->id);
                }
            }

            // Fetch structured modular learning path and derived progress (PHASE-6)
            $learningPath = $student
                ? $this->moduleService->getLearningPathForStudent($csId, $student->id, $userContext)
                : [
                    'modules' => [],
                    'course_progress_percent' => 0.0,
                    'is_course_completed' => false,
                    'total_required_items' => 0,
                    'total_completed_items' => 0,
                    'has_modules' => false,
                ];

            // Fetch current/resume learning target (PHASE-7)
            $resumeTarget = $student
                ? $this->moduleService->getStudentResumeTarget($csId, $student->id)
                : null;

            $sName = $classSubject->subject?->name ?? 'Subject';
            $cName = $classSubject->schoolClass?->name ?? 'Class';

            return Response::html($this->render('student/subjects/show', [
                'title' => "{$sName} ({$cName}) — Course Workspace",
                'headerTitle' => 'Subject Learning Hub',
                'user' => $userContext,
                'student' => $student,
                'classSubject' => $classSubject,
                'items' => $items,
                'topics' => $topics,
                'assignments' => $assignments,
                'quizzes' => array_values($quizzes),
                'studentAttempts' => $studentAttempts,
                'termResult' => $termResult,
                'activeTerm' => $activeTerm,
                'learningPath' => $learningPath,
                'resumeTarget' => $resumeTarget,
            ], 'layouts/student'));
        } catch (AuthorizationException | ResourceNotFoundException $e) {
            return $this->notFound('Subject course not found or you are not enrolled.');
        }
    }
}
