<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\ResultPublicationRepository;
use App\Repositories\ResultSubmissionRepository;
use App\Repositories\TeacherRepository;

/**
 * Controller for Form/Class Teacher Class Results Overview, Broadsheet, and Admin Submission
 */
class ResultOverviewController extends Controller
{
    private TeacherRepository $teacherRepo;
    private AcademicRepository $academicRepo;
    private GradebookRepository $gradebookRepo;
    private EnrollmentRepository $enrollmentRepo;
    private ResultPublicationRepository $publicationRepo;
    private ResultSubmissionRepository $submissionRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?TeacherRepository $teacherRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?GradebookRepository $gradebookRepo = null,
        ?EnrollmentRepository $enrollmentRepo = null,
        ?ResultPublicationRepository $publicationRepo = null,
        ?ResultSubmissionRepository $submissionRepo = null
    ) {
        parent::__construct($authenticator);
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository();
        $this->enrollmentRepo = $enrollmentRepo ?? new EnrollmentRepository();
        $this->publicationRepo = $publicationRepo ?? new ResultPublicationRepository();
        $this->submissionRepo = $submissionRepo ?? new ResultSubmissionRepository();
    }

    /**
     * View Class Broadsheet Results Overview across all subjects for assigned Form Class
     * Route: GET /teacher/results/overview
     */
    public function overview(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $teacher = $this->teacherRepo->findTeacherByUserId($userContext->id);
        $teacherId = $teacher ? $teacher->id : null;

        if (!$teacherId && !$userContext->isAdmin()) {
            throw new AuthorizationException('Teacher profile required.');
        }

        // Form classes assigned to this teacher (or all classes if admin)
        if ($userContext->isAdmin()) {
            $formClasses = $this->academicRepo->getAllClasses();
        } else {
            $formClasses = $teacherId ? $this->academicRepo->getClassesByFormTeacherId($teacherId) : [];
        }

        $allSessions = $this->academicRepo->getAllSessions();
        $activeSession = $this->academicRepo->findCurrentSession() ?? $this->academicRepo->findActiveSession();
        $selectedSessionId = (int)($request->get('session_id') ?: ($activeSession?->id ?? ($allSessions[0]->id ?? 0)));

        $terms = $selectedSessionId > 0 ? $this->academicRepo->getTermsBySession($selectedSessionId) : [];
        $activeTerm = null;
        foreach ($terms as $t) {
            if ($t->status === 'active' || $t->status === 'open') {
                $activeTerm = $t;
                break;
            }
        }
        $selectedTermId = (int)($request->get('term_id') ?: ($activeTerm?->id ?? ($terms[0]->id ?? 0)));
        $selectedTerm = $selectedTermId > 0 ? $this->academicRepo->findTermById($selectedTermId) : null;

        $formClassIds = array_map(fn($c) => (int)$c->id, $formClasses);
        $requestedClassId = (int)$request->get('class_id', 0);
        $selectedClassId = ($requestedClassId > 0 && in_array($requestedClassId, $formClassIds, true))
            ? $requestedClassId
            : ($formClasses[0]->id ?? 0);

        $selectedClass = $selectedClassId > 0 ? $this->academicRepo->findClassById($selectedClassId) : null;

        $classSubjects = [];
        $students = [];
        $resultsMatrix = [];
        $summaryMap = [];
        $subjectAverages = [];
        $classMean = null;
        $submission = null;
        $isPublished = false;

        if ($selectedClass && $selectedTerm) {
            $sessionId = $selectedTerm->sessionId;
            $classSubjects = $this->academicRepo->getClassSubjectsByClassAndSession($selectedClassId, $sessionId);
            $students = $this->enrollmentRepo->getStudentsByClassAndSession($selectedClassId, $sessionId);
            $resultsMatrix = $this->gradebookRepo->getTermResultsMatrixByClass($selectedClassId, $selectedTerm->id);
            $summaries = $this->gradebookRepo->getSummariesByClassAndTerm($selectedClassId, $selectedTerm->id);
            $submission = $this->submissionRepo->findSubmission($selectedClassId, $selectedTerm->id);
            $isPublished = $this->publicationRepo->isPublished($selectedTerm->id, $selectedClassId);

            $totalSum = 0;
            $countSum = 0;
            foreach ($summaries as $sm) {
                $summaryMap[$sm->studentId] = $sm;
                if ($sm->averageScore !== null) {
                    $totalSum += (float)$sm->averageScore;
                    $countSum++;
                }
            }
            if ($countSum > 0) {
                $classMean = round($totalSum / $countSum, 1);
            }

            foreach ($classSubjects as $cs) {
                $stats = $this->gradebookRepo->getTermResultsStatsByClassSubject($cs->id, $selectedTerm->id);
                $subjectAverages[$cs->subjectId] = [
                    'avg' => $stats['avg_score'],
                    'min' => $stats['min_score'],
                    'max' => $stats['max_score'],
                ];
            }

            // Sort students by rank ascending, then by name
            usort($students, function ($a, $b) use ($summaryMap) {
                $rankA = $summaryMap[$a->id]->classRank ?? 9999;
                $rankB = $summaryMap[$b->id]->classRank ?? 9999;
                if ($rankA === $rankB) {
                    return strcmp($a->name, $b->name);
                }
                return $rankA <=> $rankB;
            });
        }

        return Response::html($this->render('teacher/results/overview', [
            'title' => 'Form Class Results Broadsheet — Claret Faculty Portal',
            'headerTitle' => 'Form Class Results Overview',
            'headerSubtitle' => 'Inspect comprehensive terminal student standings across all registered subjects and submit results for administrative approval.',
            'user' => $userContext,
            'teacher' => $teacher,
            'sessions' => $allSessions,
            'selectedSessionId' => $selectedSessionId,
            'terms' => $terms,
            'selectedTermId' => $selectedTermId,
            'selectedTerm' => $selectedTerm,
            'formClasses' => $formClasses,
            'selectedClassId' => $selectedClassId,
            'selectedClass' => $selectedClass,
            'classSubjects' => $classSubjects,
            'students' => $students,
            'resultsMatrix' => $resultsMatrix,
            'summaryMap' => $summaryMap,
            'subjectAverages' => $subjectAverages,
            'classMean' => $classMean,
            'submission' => $submission,
            'isPublished' => $isPublished,
            'flashSuccess' => \App\Core\Session::getFlash('success'),
            'flashError' => \App\Core\Session::getFlash('error'),
        ], 'layouts/teacher'));
    }

    /**
     * Submit class results for administrative review and approval
     * Route: POST /teacher/results/submit
     */
    public function submit(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $teacher = $this->teacherRepo->findTeacherByUserId($userContext->id);
        $teacherId = $teacher ? $teacher->id : null;

        if (!$teacherId && !$userContext->isAdmin()) {
            throw new AuthorizationException('Teacher profile required.');
        }

        $classId = (int)$request->post('class_id', 0);
        $termId = (int)$request->post('term_id', 0);
        $notes = trim((string)$request->post('notes', ''));

        if ($classId <= 0 || $termId <= 0) {
            return $this->redirectWithError('/teacher/results/overview', 'Invalid class or term specified.');
        }

        $class = $this->academicRepo->findClassById($classId);
        if (!$class) {
            return $this->notFound('Class not found.');
        }

        // Verify form teacher authority
        if (!$userContext->isAdmin() && (int)$class->formTeacherId !== (int)$teacherId) {
            throw new AuthorizationException('Only the assigned Form Teacher of this class can submit its terminal results.');
        }

        $this->submissionRepo->submit($classId, $termId, $teacherId ?? 1, !empty($notes) ? $notes : null);

        $className = method_exists($class, 'getFullName') ? $class->getFullName() : $class->name;
        $redirectUrl = "/teacher/results/overview?class_id={$classId}&term_id={$termId}";

        return $this->redirectWithSuccess(
            $redirectUrl,
            "Terminal results for {$className} have been submitted to the administration for review and approval."
        );
    }
}
