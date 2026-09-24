<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Repositories\GradingScaleRepository;
use App\Repositories\StudentRepository;
use App\Utils\QrCode;
use PDO;

/**
 * Service for Aggregating, Structuring, and Certifying Multi-Session Student Transcripts
 * (SRS §26, §51 - Zero GPA / Stage-Aware Standards)
 */
class TranscriptService
{
    private readonly PDO $pdo;
    private readonly StudentRepository $studentRepo;
    private readonly GradingScaleRepository $gradingScaleRepo;

    public function __construct(
        ?PDO $pdo = null,
        ?StudentRepository $studentRepo = null,
        ?GradingScaleRepository $gradingScaleRepo = null
    ) {
        $this->pdo = $pdo ?? Database::getInstance();
        $this->studentRepo = $studentRepo ?? new StudentRepository($this->pdo);
        $this->gradingScaleRepo = $gradingScaleRepo ?? new GradingScaleRepository($this->pdo);
    }

    /**
     * Retrieve complete multi-session academic career transcript data for a student.
     *
     * @param int $studentId
     * @param string|null $scope 'all', 'junior_secondary', 'senior_secondary', 'primary'
     * @param int|null $fromSessionId
     * @param int|null $toSessionId
     * @return array<string, mixed>
     */
    public function getStudentTranscriptData(
        int $studentId,
        ?string $scope = null,
        ?int $fromSessionId = null,
        ?int $toSessionId = null
    ): array {
        $student = $this->studentRepo->findById($studentId);
        if (!$student) {
            throw new ResourceNotFoundException("Student #{$studentId} not found.");
        }

        // Fetch School Profile & Identity
        $school = $this->getSchoolProfile();

        // Build Historical Query with dynamic joins
        $sql = "
            SELECT 
                tr.id as result_id,
                tr.student_id,
                tr.computed_score,
                tr.grade_letter,
                tr.remark,
                tr.breakdown_json,
                t.id as term_id,
                t.name as term_name,
                t.status as term_status,
                t.start_date as term_start_date,
                t.end_date as term_end_date,
                sess.id as session_id,
                sess.name as session_name,
                sess.start_date as session_start_date,
                sess.end_date as session_end_date,
                cs.id as class_subject_id,
                sub.id as subject_id,
                sub.name as subject_name,
                sub.code as subject_code,
                c.id as class_id,
                c.name as class_name,
                c.section_arm as class_section_arm,
                al.id as level_id,
                al.name as level_name,
                al.stage as level_stage,
                al.rank_order as level_rank_order,
                al.grading_scale_id as level_grading_scale_id
            FROM term_results tr
            JOIN terms t ON t.id = tr.term_id
            JOIN sessions sess ON sess.id = t.session_id
            JOIN class_subjects cs ON cs.id = tr.class_subject_id
            JOIN subjects sub ON sub.id = cs.subject_id
            JOIN classes c ON c.id = cs.class_id
            JOIN academic_levels al ON al.id = c.academic_level_id
            WHERE tr.student_id = :student_id
        ";

        $params = [':student_id' => $studentId];

        // Scope filtering by institutional stage
        if ($scope && in_array($scope, ['junior_secondary', 'senior_secondary', 'primary', 'nursery', 'creche'], true)) {
            $sql .= " AND al.stage = :stage_scope";
            $params[':stage_scope'] = $scope;
        }

        // Session range filtering
        if ($fromSessionId !== null && $fromSessionId > 0) {
            $sql .= " AND sess.id >= :from_session";
            $params[':from_session'] = $fromSessionId;
        }
        if ($toSessionId !== null && $toSessionId > 0) {
            $sql .= " AND sess.id <= :to_session";
            $params[':to_session'] = $toSessionId;
        }

        $sql .= " ORDER BY sess.start_date ASC, sess.id ASC, t.id ASC, sub.name ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch historical student term summaries (comments, attendance, ranks)
        $summariesByTerm = $this->fetchTermSummaries($studentId);

        // Group records into Sessions -> Terms -> Subjects
        $sessionsData = [];
        $totalCareerMarks = 0.0;
        $totalCareerMaxMarks = 0.0;
        $totalCareerSubjects = 0;
        $allSessionIds = [];
        $allTermIds = [];

        foreach ($records as $row) {
            $sessId = (int)$row['session_id'];
            $termId = (int)$row['term_id'];
            $allSessionIds[$sessId] = true;
            $allTermIds[$termId] = true;

            if (!isset($sessionsData[$sessId])) {
                $sessionsData[$sessId] = [
                    'session_id' => $sessId,
                    'session_name' => $row['session_name'],
                    'start_date' => $row['session_start_date'],
                    'end_date' => $row['session_end_date'],
                    'class_name' => $row['class_name'] . (!empty($row['class_section_arm']) ? ' (' . $row['class_section_arm'] . ')' : ''),
                    'level_name' => $row['level_name'],
                    'stage' => $row['level_stage'],
                    'terms' => [],
                    'session_total_marks' => 0.0,
                    'session_max_marks' => 0.0,
                    'session_subjects_count' => 0,
                    'session_average' => 0.0,
                ];
            }

            if (!isset($sessionsData[$sessId]['terms'][$termId])) {
                $termSummary = $summariesByTerm[$termId] ?? null;
                $sessionsData[$sessId]['terms'][$termId] = [
                    'term_id' => $termId,
                    'term_name' => $row['term_name'],
                    'status' => $row['term_status'],
                    'subjects' => [],
                    'term_total_marks' => 0.0,
                    'term_max_marks' => 0.0,
                    'term_average' => 0.0,
                    'class_rank' => $termSummary['class_rank'] ?? null,
                    'attendance' => $termSummary ? ($termSummary['attendance_present'] . '/' . $termSummary['attendance_total']) : null,
                    'teacher_remark' => $termSummary['form_teacher_remark'] ?? null,
                    'principal_remark' => $termSummary['principal_remark'] ?? null,
                ];
            }

            // Resolve stage-aware grading scale for this exact class level & stage
            $levelId = (int)$row['level_id'];
            $stage = (string)$row['level_stage'];
            $scale = $this->gradingScaleRepo->getScaleForLevel($levelId, $stage);

            $score = (float)$row['computed_score'];
            $matched = $scale?->resolveGrade($score);
            $gradeLetter = $matched ? $matched->letter : ($row['grade_letter'] ?: 'F');
            $remark = ($matched && $matched->remark) ? $matched->remark : ($row['remark'] ?: '');

            $subjectItem = [
                'subject_id' => (int)$row['subject_id'],
                'name' => $row['subject_name'],
                'code' => $row['subject_code'] ?? null,
                'score' => round($score, 2),
                'max_score' => 100.0,
                'grade' => $gradeLetter,
                'remark' => strtoupper($remark),
                'scale_name' => $scale?->name ?? 'Standard Scale',
            ];

            $sessionsData[$sessId]['terms'][$termId]['subjects'][] = $subjectItem;
            $sessionsData[$sessId]['terms'][$termId]['term_total_marks'] += $score;
            $sessionsData[$sessId]['terms'][$termId]['term_max_marks'] += 100.0;

            $sessionsData[$sessId]['session_total_marks'] += $score;
            $sessionsData[$sessId]['session_max_marks'] += 100.0;
            $sessionsData[$sessId]['session_subjects_count']++;

            $totalCareerMarks += $score;
            $totalCareerMaxMarks += 100.0;
            $totalCareerSubjects++;
        }

        // Calculate term averages and session averages
        foreach ($sessionsData as &$sess) {
            foreach ($sess['terms'] as &$t) {
                $subCount = count($t['subjects']);
                $t['term_average'] = $subCount > 0 ? round($t['term_total_marks'] / $subCount, 2) : 0.0;
            }
            unset($t);

            $sess['session_average'] = $sess['session_subjects_count'] > 0 
                ? round($sess['session_total_marks'] / $sess['session_subjects_count'], 2) 
                : 0.0;
            
            // Re-index terms numerically for clean view iteration
            $sess['terms'] = array_values($sess['terms']);
        }
        unset($sess);

        $sessionsList = array_values($sessionsData);

        // Cumulative Career Analytics
        $cumulativeAverage = $totalCareerSubjects > 0 
            ? round(($totalCareerMarks / $totalCareerMaxMarks) * 100, 2) 
            : 0.0;

        $honorsClassification = $this->determineHonorsClassification($cumulativeAverage);

        // Deterministic Verification Reference & QR Code
        $verificationReference = $this->generateVerificationReference($studentId, array_keys($allSessionIds));
        $verificationUrl = "https://lms.test/verify/transcript/" . $verificationReference;
        $qrCodeSvg = QrCode::svg($verificationUrl, 140, 2);

        // Fetch Student User biodata
        $user = $student->user;
        $studentBiodata = [
            'id' => $student->id,
            'name' => $user ? $user->name : 'Student #' . $student->id,
            'admission_number' => $student->admissionNumber,
            'email' => $user ? $user->email : null,
            'gender' => !empty($student->gender) ? ucfirst(strtolower($student->gender)) : 'Unspecified',
            'date_of_birth' => !empty($student->dateOfBirth) ? date('jS F, Y', strtotime($student->dateOfBirth)) : 'On File',
            'state_of_origin' => $student->stateOfOrigin ?? 'Federal Capital Territory',
            'admission_date' => !empty($student->admissionDate) ? date('F Y', strtotime($student->admissionDate)) : '2024',
            'status' => !empty($student->status) ? ucfirst(strtolower((string)$student->status)) : 'Active',
            'current_class' => $student->schoolClass ? $student->schoolClass->getFullName() : 'Enrolled',
        ];

        return [
            'school' => $school,
            'student' => $studentBiodata,
            'sessions' => $sessionsList,
            'cumulative_stats' => [
                'total_sessions' => count($allSessionIds),
                'total_terms' => count($allTermIds),
                'total_subjects' => $totalCareerSubjects,
                'total_marks_obtained' => round($totalCareerMarks, 2),
                'total_max_marks' => round($totalCareerMaxMarks, 2),
                'cumulative_average' => $cumulativeAverage,
                'honors_classification' => $honorsClassification,
            ],
            'verification' => [
                'reference' => $verificationReference,
                'url' => $verificationUrl,
                'qr_code_svg' => $qrCodeSvg,
                'issued_at' => date('jS F, Y'),
                'issued_timestamp' => date('Y-m-d H:i:s'),
            ],
            'scope' => $scope ?: 'all',
        ];
    }

    /**
     * Publicly verify a transcript by its unique security reference.
     *
     * @return array<string, mixed>|null
     */
    public function verifyTranscriptReference(string $reference): ?array
    {
        $reference = strtoupper(trim($reference));
        // Reference pattern: CLT-TRN-{STUDENT_ID}-{CHECKSUM}
        if (!preg_match('/^CLT-TRN-(\d+)-([A-Z0-9]{4,8})$/', $reference, $matches)) {
            return null;
        }

        $studentId = (int)$matches[1];
        $checksum = $matches[2];

        try {
            $transcript = $this->getStudentTranscriptData($studentId);
        } catch (\Throwable) {
            return null;
        }

        if ($transcript['verification']['reference'] !== $reference) {
            return null;
        }

        return [
            'is_valid' => true,
            'reference' => $reference,
            'student_name' => $transcript['student']['name'],
            'admission_number' => $transcript['student']['admission_number'],
            'school_name' => $transcript['school']['name'],
            'total_sessions' => $transcript['cumulative_stats']['total_sessions'],
            'total_terms' => $transcript['cumulative_stats']['total_terms'],
            'total_subjects' => $transcript['cumulative_stats']['total_subjects'],
            'cumulative_average' => $transcript['cumulative_stats']['cumulative_average'],
            'honors_classification' => $transcript['cumulative_stats']['honors_classification'],
            'verified_at' => date('Y-m-d H:i:s'),
        ];
    }

    private function generateVerificationReference(int $studentId, array $sessionIds): string
    {
        sort($sessionIds);
        $seed = "CLT_TRANSCRIPT_{$studentId}_" . implode('_', $sessionIds);
        $hash = strtoupper(substr(md5($seed), 0, 4));
        $paddedId = str_pad((string)$studentId, 3, '0', STR_PAD_LEFT);
        return "CLT-TRN-{$paddedId}-{$hash}";
    }

    private function determineHonorsClassification(float $average): string
    {
        if ($average >= 75.0) {
            return 'DISTINCTION (EXCELLENT)';
        }
        if ($average >= 65.0) {
            return 'CREDIT (VERY GOOD)';
        }
        if ($average >= 50.0) {
            return 'CREDIT (GOOD)';
        }
        if ($average >= 40.0) {
            return 'PASS';
        }
        return 'UNSATISFACTORY';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchTermSummaries(int $studentId): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM student_term_summaries WHERE student_id = :student_id
            ");
            $stmt->execute([':student_id' => $studentId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $mapped = [];
            foreach ($rows as $r) {
                $mapped[(int)$r['term_id']] = [
                    'term_id' => (int)$r['term_id'],
                    'class_rank' => $r['rank_in_class'] ?? $r['class_rank'] ?? null,
                    'attendance_present' => $r['attendance_present_count'] ?? $r['attendance_present'] ?? null,
                    'attendance_total' => $r['attendance_total_count'] ?? $r['attendance_total'] ?? null,
                    'form_teacher_remark' => $r['class_teacher_remark'] ?? $r['form_teacher_remark'] ?? null,
                    'principal_remark' => $r['principal_remark'] ?? null,
                ];
            }
            return $mapped;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, string>
     */
    private function getSchoolProfile(): array
    {
        $settings = [];
        try {
            $stmt = $this->pdo->query('SELECT setting_key, setting_value FROM system_settings');
            if ($stmt) {
                $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
            }
        } catch (\Throwable) {
            $settings = [];
        }

        return [
            'name' => $settings['school_name'] ?? 'Claret International School',
            'motto' => $settings['school_motto'] ?? 'Discipline, Integrity & Ardour',
            'address' => $settings['school_address'] ?? 'Plot 700, Gitto Street, Mabushi, Abuja, Federal Capital Territory, Nigeria',
            'phone' => $settings['school_phone'] ?? '+234 803 788 1737',
            'email' => $settings['school_email'] ?? 'info@claret.edu',
            'website' => $settings['school_website'] ?? 'https://claret.edu',
            'logo' => !empty($settings['school_logo_url']) ? $settings['school_logo_url'] : '/assets/img/logo.png',
            'head_teacher_name' => $settings['head_teacher_name'] ?? 'Mrs. Cynthia Rowland',
            'head_teacher_title' => $settings['head_teacher_title'] ?? 'Head of School',
            'head_teacher_signature_url' => $settings['head_teacher_signature_url'] ?? '',
            'school_stamp_url' => $settings['school_stamp_url'] ?? '',
        ];
    }
}
