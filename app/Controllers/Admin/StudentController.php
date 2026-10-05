<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Policies\AcademicPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\StudentRepository;

/**
 * Controller for Dedicated Admin Student Cohort & Academic Dossier Directory
 */
class StudentController extends Controller
{
    private StudentRepository $studentRepository;
    private AcademicRepository $academicRepository;

    public function __construct(
        ?StudentRepository $studentRepository = null,
        ?AcademicRepository $academicRepository = null
    ) {
        $this->studentRepository = $studentRepository ?? new StudentRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
    }

    public function index(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageAcademicStructure($userContext)) {
            return $this->forbidden('You are not authorized to view the students directory.');
        }

        $classId = (int)$request->query('class_id', 0) ?: null;
        $stage = trim((string)$request->query('stage', '')) ?: null;
        $gender = trim((string)$request->query('gender', '')) ?: null;
        $search = trim((string)$request->query('search', '')) ?: null;
        $page = max(1, (int)$request->query('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $filteredTotal = $this->studentRepository->countStudentsWithDetails($classId, $stage, $gender, $search);
        $totalPages = max(1, (int)ceil($filteredTotal / $limit));
        $students = $this->studentRepository->getStudentsWithDetails($classId, $stage, $gender, $search, limit: $limit, offset: $offset);

        $classes = $this->academicRepository->getAllClasses();
        $stages = $this->academicRepository->getAllStages();

        $totalCount = $this->studentRepository->countAll();
        $allStudentsForGender = $this->studentRepository->getStudentsWithDetails(limit: 500);
        $maleCount = count(array_filter($allStudentsForGender, fn($s) => strtolower($s['gender'] ?? '') === 'male'));
        $femaleCount = count(array_filter($allStudentsForGender, fn($s) => strtolower($s['gender'] ?? '') === 'female'));

        return $this->view('admin/students/index', [
            'title' => 'Students Directory & Enrollment Roster — Claret LMS',
            'headerTitle' => 'Students Directory',
            'students' => $students,
            'classes' => $classes,
            'stages' => $stages,
            'selectedClassId' => $classId,
            'selectedStage' => $stage,
            'selectedGender' => $gender,
            'search' => $search,
            'totalCount' => $totalCount,
            'maleCount' => $maleCount,
            'femaleCount' => $femaleCount,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalResults' => $filteredTotal,
            'perPage' => $limit,
        ]);
    }

    public function show(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageAcademicStructure($userContext)) {
            return $this->forbidden('You are not authorized to view student details.');
        }

        $studentId = (int)$id;
        $student = $this->studentRepository->getStudentWithFullDetails($studentId);
        if (!$student) {
            return Response::html('Student record not found.', 404);
        }

        $enrolledSubjects = $this->studentRepository->getEnrolledSubjects($studentId);
        $parents = $this->studentRepository->getParents($studentId);

        return $this->view('admin/students/show', [
            'title' => "Student Dossier: {$student['user_name']} — Claret LMS",
            'headerTitle' => 'Student Academic Dossier',
            'student' => $student,
            'enrolledSubjects' => $enrolledSubjects,
            'parents' => $parents,
        ]);
    }
}
