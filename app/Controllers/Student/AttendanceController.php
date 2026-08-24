<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\AttendanceRepository;
use App\Repositories\StudentRepository;

/**
 * Controller for Student Attendance Records and Roll Call History
 */
class AttendanceController extends Controller
{
    private AttendanceRepository $attendanceRepo;
    private StudentRepository $studentRepo;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?AttendanceRepository $attendanceRepo = null,
        ?StudentRepository $studentRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        parent::__construct($authenticator);
        $this->attendanceRepo = $attendanceRepo ?? new AttendanceRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    /**
     * Route: GET /student/attendance
     */
    public function index(Request $request): Response
    {
        $user = $this->requireAuthContext($request);
        $student = $this->studentRepo->findByUserId($user->id);

        if (!$student && !$user->isAdmin()) {
            return Response::forbidden('Student profile not found.');
        }

        $activeSession = $this->academicRepo->findCurrentSession();
        $currentTerm = $this->academicRepo->findCurrentTerm();
        $termId = $request->get('term_id') ? (int)$request->get('term_id') : ($currentTerm?->id ?? 0);

        $studentId = $student ? $student->id : ($user->getStudentId() ?: 0);
        $summary = $this->attendanceRepo->getStudentAttendanceSummary($studentId, $termId);
        $history = $this->attendanceRepo->getStudentAttendanceHistory($studentId, $termId);
        $terms = $this->academicRepo->getAllTerms();

        return Response::html($this->render('student/attendance/index', [
            'title' => 'My Attendance Record — Student Portal',
            'headerTitle' => 'Attendance Tracker',
            'user' => $user,
            'student' => $student,
            'activeSession' => $activeSession,
            'currentTerm' => $currentTerm,
            'summary' => $summary,
            'history' => $history,
            'terms' => $terms,
            'selectedTermId' => $termId,
        ], 'layouts/student'));
    }
}
