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
use App\Policies\AcademicPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\TeacherRepository;
use App\Services\TeacherAssignmentService;

/**
 * Controller for Class Subject Associations and Teacher Mappings Administration
 */
class ClassSubjectController extends Controller
{
    private TeacherAssignmentService $assignmentService;
    private AcademicRepository $academicRepository;
    private TeacherRepository $teacherRepository;

    public function __construct(
        ?TeacherAssignmentService $assignmentService = null,
        ?AcademicRepository $academicRepository = null,
        ?TeacherRepository $teacherRepository = null
    ) {
        $this->assignmentService = $assignmentService ?? new TeacherAssignmentService();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->teacherRepository = $teacherRepository ?? new TeacherRepository();
    }

    public function index(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageClassSubjects($userContext)) {
            return $this->forbidden('You are not authorized to manage class subjects.');
        }

        $sessions = $this->academicRepository->getAllSessions();
        $activeSession = $this->academicRepository->findActiveSession();

        $selectedSessionId = (int)$request->query('session_id', $activeSession ? $activeSession->id : ($sessions[0]->id ?? 0));
        $selectedClassId = (int)$request->query('class_id', 0);

        $classSubjects = [];
        if ($selectedSessionId > 0) {
            $classSubjects = $this->academicRepository->getClassSubjectsBySession($selectedSessionId, $selectedClassId > 0 ? $selectedClassId : null);
        }

        $classes = $this->academicRepository->getAllClasses();
        $subjects = $this->academicRepository->getAllSubjects();
        $teachers = $this->teacherRepository->getAllTeachers();

        return $this->view('admin/class_subjects/index', [
            'title' => 'Class Subjects & Teachers — Claret LMS',
            'headerTitle' => 'Class Subjects & Teacher Mappings',
            'sessions' => $sessions,
            'selectedSessionId' => $selectedSessionId,
            'selectedClassId' => $selectedClassId,
            'classSubjects' => $classSubjects,
            'classes' => $classes,
            'subjects' => $subjects,
            'teachers' => $teachers,
        ]);
    }

    public function store(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageClassSubjects($userContext)) {
            return $this->forbidden('You are not authorized to create class subject mappings.');
        }

        $sessionId = (int)$request->post('session_id', 0);
        $redirectUrl = '/admin/class-subjects' . ($sessionId > 0 ? '?session_id=' . $sessionId : '');

        try {
            $this->assignmentService->assignTeacherToClassSubject([
                'session_id' => $sessionId,
                'class_id' => $request->post('class_id'),
                'subject_id' => $request->post('subject_id'),
                'teacher_id' => $request->post('teacher_id'),
                'status' => $request->post('status', 'active'),
            ]);

            return $this->redirectWithSuccess($redirectUrl, 'Subject and teacher assigned to class successfully.');
        } catch (ValidationException|DomainRuleException $e) {
            return $this->redirectWithError($redirectUrl, $e->getMessage());
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        }
    }

    public function update(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageClassSubjects($userContext)) {
            return Response::html('Forbidden', 403);
        }

        $id = (int)$id;
        $sessionId = (int)$request->post('session_id', 0);
        $redirectUrl = '/admin/class-subjects' . ($sessionId > 0 ? '?session_id=' . $sessionId : '');

        try {
            $newTeacherId = (int)$request->post('teacher_id', 0);
            $this->assignmentService->reassignTeacher($id, $newTeacherId);

            return $this->redirectWithSuccess($redirectUrl, 'Teacher reassigned successfully.');
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        } catch (DomainRuleException|ValidationException $e) {
            return $this->redirectWithError($redirectUrl, $e->getMessage());
        }
    }

    public function status(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageClassSubjects($userContext)) {
            return Response::html('Forbidden', 403);
        }

        $id = (int)($id ?: $request->post('id', 0));
        $status = (string)$request->post('status', 'active');
        $sessionId = (int)$request->post('session_id', 0);
        $redirectUrl = '/admin/class-subjects' . ($sessionId > 0 ? '?session_id=' . $sessionId : '');

        try {
            $this->assignmentService->updateClassSubjectStatus($id, $status);
            return $this->redirectWithSuccess($redirectUrl, 'Class-subject status updated successfully.');
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        } catch (DomainRuleException $e) {
            return $this->redirectWithError($redirectUrl, $e->getMessage());
        }
    }
}
