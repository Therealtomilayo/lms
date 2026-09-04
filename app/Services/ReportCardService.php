<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Models\StudentTermSummary;
use App\Repositories\AcademicRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use PDO;

/**
 * Service for Student Report Card Aggregation and Rendering
 */
final class ReportCardService
{
    private readonly GradebookRepository $gradebookRepo;
    private readonly StudentRepository $studentRepo;
    private readonly AcademicRepository $academicRepo;
    private readonly TeacherRepository $teacherRepo;

    public function __construct(
        ?GradebookRepository $gradebookRepo = null,
        ?StudentRepository $studentRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?TeacherRepository $teacherRepo = null
    ) {
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository();
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

        // Fetch School Identity & Settings
        $pdo = Database::getInstance();
        $settings = [];
        try {
            $stmt = $pdo->query('SELECT setting_key, setting_value FROM system_settings');
            if ($stmt) {
                $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                if (is_array($rows)) {
                    $settings = $rows;
                }
            }
        } catch (\Throwable) {
            // fallback defaults
        }

        $school = [
            'name' => $settings['school_name'] ?? 'Claret Academy Secondary School',
            'motto' => $settings['school_motto'] ?? 'Discipline, Integrity & Ardour',
            'address' => $settings['school_address'] ?? 'Plot 700, Gitto Street, Mabushi, Mabushi, Abuja, Federal Capital Territory, 900104, Nigeria',
            'phone' => $settings['school_phone'] ?? '+234 803 788 1737',
            'email' => $settings['school_email'] ?? 'info@claret.edu',
            'logo' => '/assets/img/logo.png',
        ];

        // Class Cohort Benchmark Analytics
        $classStats = [
            'total_students' => 0,
            'highest_avg' => 0.0,
            'lowest_avg' => 0.0,
            'class_avg' => 0.0,
        ];

        $classId = $summary?->classId ?? $student->currentClassId;
        $class = $summary?->class;
        if (!$class && $classId) {
            try {
                $class = $this->academicRepo->findClassById($classId);
            } catch (\Throwable) {
                $class = null;
            }
        }

        if ($classId) {
            $classSummaries = $this->gradebookRepo->getSummariesByClassAndTerm($classId, $termId);
            if (!empty($classSummaries)) {
                $scores = array_map(fn($s) => (float)$s->averageScore, $classSummaries);
                $classStats['total_students'] = count($classSummaries);
                $classStats['highest_avg'] = max($scores);
                $classStats['lowest_avg'] = min($scores);
                $classStats['class_avg'] = round(array_sum($scores) / count($scores), 2);
            } else {
                try {
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM students WHERE current_class_id = :cid');
                    $stmt->execute([':cid' => $classId]);
                    $classStats['total_students'] = (int)$stmt->fetchColumn() ?: 24;
                } catch (\Throwable) {
                    $classStats['total_students'] = 24;
                }
            }
        }

        // Cumulative Session Data across all terms
        $sessionTerms = $session ? $this->academicRepo->getTermsBySession($session->id) : [$term];
        $cumulativeResults = [];
        $cumulativeSummaries = [];
        foreach ($sessionTerms as $st) {
            $cumulativeResults[$st->id] = $this->gradebookRepo->getTermResultsByStudent($studentId, $st->id);
            $cumulativeSummaries[$st->id] = $this->gradebookRepo->findStudentTermSummary($studentId, $st->id);
        }

        // Form Teacher Name
        $formTeacher = 'Subject Teacher';
        if (!empty($subjectResults)) {
            foreach ($subjectResults as $res) {
                if ($res->classSubject && $res->classSubject->teacherId) {
                    $t = $this->teacherRepo->findTeacherById($res->classSubject->teacherId);
                    if ($t) {
                        $formTeacher = $t->userName ?: $t->name;
                        break;
                    }
                }
            }
        }

        return [
            'student' => $student,
            'class' => $class,
            'term' => $term,
            'session' => $session,
            'summary' => $summary,
            'subject_results' => $subjectResults,
            'school' => $school,
            'class_stats' => $classStats,
            'session_terms' => $sessionTerms,
            'cumulative_results' => $cumulativeResults,
            'cumulative_summaries' => $cumulativeSummaries,
            'form_teacher' => $formTeacher,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }
}
