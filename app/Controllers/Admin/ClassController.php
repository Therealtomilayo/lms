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
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Services\AcademicStructureService;

/**
 * Controller for Classes and Arms Administration
 */
class ClassController extends Controller
{
    private AcademicStructureService $structureService;
    private AcademicRepository $repository;
    private TeacherRepository $teacherRepository;
    private StudentRepository $studentRepository;

    public function __construct(
        ?AcademicStructureService $structureService = null,
        ?AcademicRepository $repository = null,
        ?TeacherRepository $teacherRepository = null,
        ?StudentRepository $studentRepository = null
    ) {
        $this->structureService = $structureService ?? new AcademicStructureService();
        $this->repository = $repository ?? new AcademicRepository();
        $this->teacherRepository = $teacherRepository ?? new TeacherRepository();
        $this->studentRepository = $studentRepository ?? new StudentRepository();
    }

    public function index(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageAcademicStructure($userContext)) {
            return $this->forbidden('You are not authorized to manage classes.');
        }

        $search = trim((string)$request->query('search', '')) ?: null;
        $levelId = (int)$request->query('level_id', 0) ?: null;
        $page = max(1, (int)$request->query('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $classes = $this->repository->getAllClasses();
        $levels = $this->repository->getAllLevels();
        $teachers = $this->teacherRepository->getAllTeachers();

        if ($levelId !== null) {
            $classes = array_filter($classes, fn($c) => (int)$c->academicLevelId === $levelId);
        }

        if ($search !== null) {
            $sLower = strtolower($search);
            $classes = array_filter($classes, function($c) use ($sLower) {
                return str_contains(strtolower($c->name), $sLower) ||
                       str_contains(strtolower((string)$c->sectionArm), $sLower);
            });
        }

        $classes = array_values($classes);
        $totalClasses = count($classes);
        $totalPages = max(1, (int)ceil($totalClasses / $limit));
        $pagedClasses = array_slice($classes, $offset, $limit);

        return $this->view('admin/classes/index', [
            'title' => 'Classes & Arms — Claret LMS',
            'headerTitle' => 'Classes & Arms',
            'classes' => $pagedClasses,
            'levels' => $levels,
            'teachers' => $teachers,
            'search' => $search,
            'selectedLevelId' => $levelId,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalResults' => $totalClasses,
            'perPage' => $limit,
        ]);
    }

    public function store(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageAcademicStructure($userContext)) {
            return $this->forbidden('You are not authorized to create classes.');
        }

        try {
            $this->structureService->createClass([
                'academic_level_id' => $request->post('academic_level_id'),
                'name' => $request->post('name'),
                'section_arm' => $request->post('section_arm'),
                'form_teacher_id' => $request->post('form_teacher_id'),
                'status' => $request->post('status', 'active'),
            ]);

            return $this->redirectWithSuccess('/admin/classes', 'Class created successfully.');
        } catch (ValidationException|DomainRuleException $e) {
            return $this->redirectWithError('/admin/classes', $e->getMessage());
        }
    }

    public function update(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageAcademicStructure($userContext)) {
            return Response::html('Forbidden', 403);
        }

        $id = (int)$id;

        try {
            $this->structureService->updateClass($id, [
                'academic_level_id' => $request->post('academic_level_id'),
                'name' => $request->post('name'),
                'section_arm' => $request->post('section_arm'),
                'form_teacher_id' => $request->post('form_teacher_id'),
            ]);

            return $this->redirectWithSuccess('/admin/classes', 'Class updated successfully.');
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        } catch (DomainRuleException|ValidationException $e) {
            return $this->redirectWithError('/admin/classes', $e->getMessage());
        }
    }

    public function status(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageAcademicStructure($userContext)) {
            return Response::html('Forbidden', 403);
        }

        $id = (int)($id ?: $request->post('id', 0));
        $status = (string)$request->post('status', 'active');

        try {
            $this->structureService->updateClassStatus($id, $status);
            return $this->redirectWithSuccess('/admin/classes', 'Class status updated successfully.');
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        } catch (DomainRuleException $e) {
            return $this->redirectWithError('/admin/classes', $e->getMessage());
        }
    }

    public function show(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageAcademicStructure($userContext)) {
            return $this->forbidden('You are not authorized to view class details.');
        }

        $classId = (int)$id;
        $class = $this->repository->findClassById($classId);
        if (!$class) {
            return Response::html('Class not found.', 404);
        }

        $level = $this->repository->findLevelById($class->academicLevelId);
        $formTeacher = $class->formTeacherId ? $this->teacherRepository->findTeacherById($class->formTeacherId) : null;
        $students = $this->studentRepository->getStudentsWithDetails(classId: $classId, limit: 300);

        // Fetch subjects allocated to this class
        $pdo = $this->repository->getPdo();
        $stmt = $pdo->prepare(
            'SELECT cs.id as class_subject_id, cs.status, s.name as subject_name, s.code as subject_code,
                    tu.name as teacher_name, t.staff_id as teacher_staff_id, tu.phone as teacher_phone
             FROM `class_subjects` cs
             JOIN `subjects` s ON s.id = cs.subject_id
             LEFT JOIN `teachers` t ON t.id = cs.teacher_id
             LEFT JOIN `users` tu ON tu.id = t.user_id
             WHERE cs.class_id = :class_id
             ORDER BY s.name ASC'
        );
        $stmt->execute([':class_id' => $classId]);
        $subjects = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $maleCount = count(array_filter($students, fn($s) => strtolower($s['gender'] ?? '') === 'male'));
        $femaleCount = count(array_filter($students, fn($s) => strtolower($s['gender'] ?? '') === 'female'));

        return $this->view('admin/classes/show', [
            'title' => "Class Dossier: {$class->name} — Claret LMS",
            'headerTitle' => 'Class Cohort & Subject Allocation',
            'class' => $class,
            'level' => $level,
            'formTeacher' => $formTeacher,
            'students' => $students,
            'subjects' => $subjects,
            'maleCount' => $maleCount,
            'femaleCount' => $femaleCount,
        ]);
    }
}
