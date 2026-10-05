<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Policies\AcademicPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\TeacherRepository;

/**
 * Controller for Dedicated Admin Teacher Personnel & Academic Allocations Directory
 */
class TeacherController extends Controller
{
    private TeacherRepository $teacherRepository;
    private AcademicRepository $academicRepository;

    public function __construct(
        ?TeacherRepository $teacherRepository = null,
        ?AcademicRepository $academicRepository = null
    ) {
        $this->teacherRepository = $teacherRepository ?? new TeacherRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
    }

    public function index(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageAcademicStructure($userContext)) {
            return $this->forbidden('You are not authorized to view the teachers directory.');
        }

        $search = trim((string)$request->query('search', ''));
        $filter = trim((string)$request->query('filter', 'all'));
        $page = max(1, (int)$request->query('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $allMatchingTeachers = $this->teacherRepository->getAllTeachersWithStats($search, $filter);
        $allTeachers = $this->teacherRepository->getAllTeachersWithStats();

        $formTeachersCount = count(array_filter($allTeachers, fn($t) => !empty($t['form_classes'])));
        $subjectTeachersCount = count(array_filter($allTeachers, fn($t) => (int)$t['subjects_count'] > 0));

        $totalFiltered = count($allMatchingTeachers);
        $totalPages = max(1, (int)ceil($totalFiltered / $limit));
        $pagedTeachers = array_slice($allMatchingTeachers, $offset, $limit);

        return $this->view('admin/teachers/index', [
            'title' => 'Teachers Directory & Workspaces — Claret LMS',
            'headerTitle' => 'Teachers & Staff Directory',
            'teachers' => $pagedTeachers,
            'search' => $search,
            'filter' => $filter,
            'totalTeachers' => count($allTeachers),
            'formTeachersCount' => $formTeachersCount,
            'subjectTeachersCount' => $subjectTeachersCount,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalResults' => $totalFiltered,
            'perPage' => $limit,
        ]);
    }

    public function show(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageAcademicStructure($userContext)) {
            return $this->forbidden('You are not authorized to view teacher details.');
        }

        $teacherId = (int)$id;
        $teacher = $this->teacherRepository->findTeacherById($teacherId);
        if (!$teacher) {
            return Response::html('Teacher record not found.', 404);
        }

        $formClasses = $this->teacherRepository->getFormClasses($teacherId);
        $allocations = $this->teacherRepository->getAllocations($teacherId);

        // Calculate student reach across allocations
        $totalStudentsTaught = array_sum(array_column($allocations, 'student_count'));

        return $this->view('admin/teachers/show', [
            'title' => "Teacher Profile: {$teacher->name} — Claret LMS",
            'headerTitle' => 'Teacher Personnel Dossier',
            'teacher' => $teacher,
            'formClasses' => $formClasses,
            'allocations' => $allocations,
            'totalStudentsTaught' => $totalStudentsTaught,
        ]);
    }
}
