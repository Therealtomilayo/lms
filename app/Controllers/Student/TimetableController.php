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
        ?AuthenticatorInterface $authenticator = null,
        ?TimetableService $timetableService = null,
        ?AcademicRepository $academicRepo = null,
        ?StudentRepository $studentRepo = null
    ) {
        parent::__construct($authenticator);
        $this->timetableService = $timetableService ?? new TimetableService();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
    }

    /**
     * View personal weekly learning timetable.
     * Route: GET /student/timetable
     */
    public function index(Request $request): Response
    {
        $user = $this->requireAuthContext($request);
        $student = $this->studentRepo->findByUserId($user->id);

        if (!$student && !$user->isAdmin()) {
            return Response::forbidden('Student profile not found.');
        }

        $activeSession = $this->academicRepo->findCurrentSession();
        $terms = $this->academicRepo->getAllTerms();
        $termId = $request->get('term_id') ? (int)$request->get('term_id') : null;

        $selectedTerm = null;
        if ($termId) {
            $selectedTerm = $this->academicRepo->findTermById($termId);
        }
        if (!$selectedTerm) {
            $selectedTerm = $this->academicRepo->findCurrentTerm() ?? (!empty($terms) ? $terms[0] : null);
        }

        $studentId = $student ? $student->id : ($user->getStudentId() ?: 0);

        try {
            $scheduleData = $this->timetableService->getStudentTimetable($studentId, $selectedTerm?->id, $user);
        } catch (AuthorizationException | ResourceNotFoundException $e) {
            $scheduleData = null;
        }

        return Response::html($this->render('student/timetable/index', [
            'title' => 'Weekly Class Timetable — Student Portal',
            'headerTitle' => 'Learning Timetable',
            'user' => $user,
            'student' => $student,
            'activeSession' => $activeSession,
            'scheduleData' => $scheduleData,
            'terms' => $terms,
            'selectedTerm' => $selectedTerm,
        ], 'layouts/student'));
    }
}
