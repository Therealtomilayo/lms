<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\ResourceNotFoundException;
use App\Models\StudentTermSummary;
use App\Repositories\AcademicRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\StudentRepository;

/**
 * Service for Student Report Card Aggregation and Rendering
 */
final class ReportCardService
{
    private readonly GradebookRepository $gradebookRepo;
    private readonly StudentRepository $studentRepo;
    private readonly AcademicRepository $academicRepo;

    public function __construct(
        ?GradebookRepository $gradebookRepo = null,
        ?StudentRepository $studentRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    public function getReportCardData(int $studentId, int $termId): array
    {
        $student = $this->studentRepo->findById($studentId);
        if (!$student) {
            throw new ResourceNotFoundException('Student not found.');
        }

        $term = $this->academicRepo->findTermById($termId);
        if (!$term) {
            throw new ResourceNotFoundException('Academic term not found.');
        }

        $session = $this->academicRepo->findSessionById($term->sessionId);

        $summary = $this->gradebookRepo->findStudentTermSummary($studentId, $termId);
        $subjectResults = $this->gradebookRepo->getTermResultsByStudent($studentId, $termId);

        return [
            'student' => $student,
            'term' => $term,
            'session' => $session,
            'summary' => $summary,
            'subject_results' => $subjectResults,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }
}
