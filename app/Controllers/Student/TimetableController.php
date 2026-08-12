<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\Controller;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\StudentRepository;
use App\Services\TimetableService;

/**
 * Student Timetable Controller
 * Provides read-only view of the student's personal weekly learning schedule and enrolled subjects.
 */
class TimetableController extends Controller
{
    private TimetableService $timetableService;
    private AcademicRepository $academicRepo;
    private StudentRepository $studentRepo;

    public function __construct(
        ?TimetableService $timetableService = null,
        ?AcademicRepository $academicRepo = null,
        ?StudentRepository $studentRepo = null
    ) {
        $this->timetableService = $timetableService ?? new TimetableService();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
    }

    /**
     * View personal weekly learning timetable.
     */
    public function index(Request $request): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        // Student identity resolved strictly from session user context
        $studentId = $user->getStudentId($this->studentRepo);
        if (!$studentId) {
            return Response::html($this->render('student/timetable/index', [
                'title' => 'My Class Timetable — Student Portal',
                'headerTitle' => 'Class Schedule',
                'error' => 'No active student record found for your account.',
                'scheduleData' => null,
                'terms' => [],
                'selectedTerm' => null,
            ], 'layouts/student'));
        }

        $termId = $request->getQuery('term_id') ? (int)$request->getQuery('term_id') : null;
        $terms = $this->academicRepo->getAllTerms();
        $selectedTerm = null;

        if ($termId) {
            $selectedTerm = $this->academicRepo->findTermById($termId);
        }
        if (!$selectedTerm) {
            $selectedTerm = $this->academicRepo->findCurrentTerm() ?? $this->academicRepo->findActiveTerm() ?? (!empty($terms) ? $terms[0] : null);
        }

        try {
            $scheduleData = $this->timetableService->getStudentTimetable($studentId, $selectedTerm?->id, $user);
        } catch (AuthorizationException | ResourceNotFoundException $e) {
            return Response::html($this->render('student/timetable/index', [
                'title' => 'My Class Timetable — Student Portal',
                'headerTitle' => 'Class Schedule',
                'error' => $e->getMessage(),
                'scheduleData' => null,
                'terms' => $terms,
                'selectedTerm' => $selectedTerm,
            ], 'layouts/student'));
        }

        return Response::html($this->render('student/timetable/index', [
            'title' => 'My Class Timetable — Student Portal',
            'headerTitle' => 'Class Timetable',
            'scheduleData' => $scheduleData,
            'terms' => $terms,
            'selectedTerm' => $selectedTerm,
        ], 'layouts/student'));
    }
}
