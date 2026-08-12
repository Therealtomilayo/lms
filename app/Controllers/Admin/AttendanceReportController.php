<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\AttendanceRepository;

class AttendanceReportController extends Controller
{
    private AttendanceRepository $attendanceRepo;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AttendanceRepository $attendanceRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        $this->attendanceRepo = $attendanceRepo ?? new AttendanceRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    public function report(Request $request): Response
    {
        $classes = $this->academicRepo->getAllClasses();
        $terms = $this->academicRepo->getAllTerms();
        $currentTerm = $this->academicRepo->getCurrentTerm();

        $selectedClassId = $request->getQuery('class_id') ? (int)$request->getQuery('class_id') : ($classes[0]->id ?? 0);
        $selectedTermId = $request->getQuery('term_id') ? (int)$request->getQuery('term_id') : ($currentTerm?->id ?? 0);
        $startDate = $request->getQuery('start_date') ? (string)$request->getQuery('start_date') : null;
        $endDate = $request->getQuery('end_date') ? (string)$request->getQuery('end_date') : null;

        $reportData = [];
        if ($selectedClassId > 0 && $selectedTermId > 0) {
            $reportData = $this->attendanceRepo->getClassAttendanceReport(
                classId: $selectedClassId,
                termId: $selectedTermId,
                startDate: $startDate,
                endDate: $endDate
            );
        }

        return Response::html($this->render('admin/attendance/report', [
            'title' => 'Attendance Analytics & Reports — Admin Portal',
            'headerTitle' => 'Attendance Analytics & Report',
            'classes' => $classes,
            'terms' => $terms,
            'selectedClassId' => $selectedClassId,
            'selectedTermId' => $selectedTermId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportData' => $reportData,
        ], 'layouts/admin'));
    }
}
