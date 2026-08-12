<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\AttendanceRepository;
use App\Repositories\StudentRepository;

class AttendanceController extends Controller
{
    private AttendanceRepository $attendanceRepo;
    private StudentRepository $studentRepo;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AttendanceRepository $attendanceRepo = null,
        ?StudentRepository $studentRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        $this->attendanceRepo = $attendanceRepo ?? new AttendanceRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    public function index(Request $request): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $student = $this->studentRepo->findByUserId($user->getUserId());
        if (!$student) {
            return Response::forbidden('Student profile not found.');
        }

        $currentTerm = $this->academicRepo->getCurrentTerm();
        $termId = $request->getQuery('term_id') ? (int)$request->getQuery('term_id') : ($currentTerm?->id ?? 0);

        $summary = $this->attendanceRepo->getStudentAttendanceSummary($student->id, $termId);
        $history = $this->attendanceRepo->getStudentAttendanceHistory($student->id, $termId);
        $terms = $this->academicRepo->getAllTerms();

        return Response::html($this->render('student/attendance/index', [
            'title' => 'My Attendance Record — Student Portal',
            'student' => $student,
            'summary' => $summary,
            'history' => $history,
            'terms' => $terms,
            'selectedTermId' => $termId,
        ], 'layouts/student'));
    }
}
