<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\Controller;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\TeacherRepository;
use App\Services\TimetableService;

/**
 * Teacher Timetable Controller
 * Provides read-only view of the instructor's personalized weekly teaching schedule.
 */
class TimetableController extends Controller
{
    private TimetableService $timetableService;
    private AcademicRepository $academicRepo;
    private TeacherRepository $teacherRepo;

    public function __construct(
        ?TimetableService $timetableService = null,
        ?AcademicRepository $academicRepo = null,
        ?TeacherRepository $teacherRepo = null
    ) {
        $this->timetableService = $timetableService ?? new TimetableService();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository();
    }

    /**
     * View personal weekly teaching timetable.
     */
    public function index(Request $request): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $teacherId = $user->getTeacherId($this->teacherRepo);
        if (!$teacherId) {
            return Response::html($this->render('teacher/timetable/index', [
                'title' => 'My Teaching Timetable — Faculty Portal',
                'headerTitle' => 'Teaching Schedule',
                'headerSubtitle' => 'Weekly instructional roster',
                'error' => 'No active faculty profile found for your account.',
                'scheduleData' => null,
                'terms' => [],
                'selectedTerm' => null,
            ], 'layouts/teacher'));
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
            $scheduleData = $this->timetableService->getTeacherTimetable($teacherId, $selectedTerm?->id, $user);
        } catch (AuthorizationException | ResourceNotFoundException $e) {
            return Response::html($this->render('teacher/timetable/index', [
                'title' => 'My Teaching Timetable — Faculty Portal',
                'headerTitle' => 'Teaching Schedule',
                'headerSubtitle' => 'Weekly instructional roster',
                'error' => $e->getMessage(),
                'scheduleData' => null,
                'terms' => $terms,
                'selectedTerm' => $selectedTerm,
            ], 'layouts/teacher'));
        }

        return Response::html($this->render('teacher/timetable/index', [
            'title' => 'My Teaching Timetable — Faculty Portal',
            'headerTitle' => 'Teaching Schedule',
            'headerSubtitle' => 'Weekly instructional schedule and allocated classrooms',
            'scheduleData' => $scheduleData,
            'terms' => $terms,
            'selectedTerm' => $selectedTerm,
        ], 'layouts/teacher'));
    }
}
