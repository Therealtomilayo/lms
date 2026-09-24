<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\GradingScaleRepository;
use App\Repositories\StudentRepository;
use App\Services\TranscriptService;
use PDO;
use PHPUnit\Framework\TestCase;

final class TranscriptServiceTest extends TestCase
{
    private PDO $pdo;
    private StudentRepository $studentRepo;
    private GradingScaleRepository $gradingScaleRepo;
    private TranscriptService $service;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->createSchema();
        $this->seedTestData();

        $this->studentRepo = new StudentRepository($this->pdo);
        $this->gradingScaleRepo = new GradingScaleRepository($this->pdo);
        $this->service = new TranscriptService($this->pdo, $this->studentRepo, $this->gradingScaleRepo);
    }

    private function createSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(150) NOT NULL,
                first_name VARCHAR(100) NULL,
                last_name VARCHAR(100) NULL,
                email VARCHAR(150) NOT NULL,
                phone VARCHAR(50) NULL,
                role VARCHAR(50) NOT NULL DEFAULT 'student',
                status VARCHAR(50) NOT NULL DEFAULT 'active'
            );

            CREATE TABLE students (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                admission_number VARCHAR(50) NOT NULL,
                admission_date DATE NULL,
                date_of_birth DATE NULL,
                gender VARCHAR(20) NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'active',
                current_class_id INTEGER NULL
            );

            CREATE TABLE sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'active'
            );

            CREATE TABLE terms (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id INTEGER NOT NULL,
                name VARCHAR(100) NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'active'
            );

            CREATE TABLE academic_levels (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                stage VARCHAR(50) NOT NULL,
                rank_order INTEGER NOT NULL DEFAULT 0,
                grading_scale_id INTEGER NULL
            );

            CREATE TABLE classes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                section_arm VARCHAR(50) NULL,
                academic_level_id INTEGER NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'active'
            );

            CREATE TABLE subjects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(150) NOT NULL,
                code VARCHAR(50) NOT NULL
            );

            CREATE TABLE class_subjects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                class_id INTEGER NOT NULL,
                subject_id INTEGER NOT NULL,
                teacher_id INTEGER NULL
            );

            CREATE TABLE term_results (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                class_subject_id INTEGER NOT NULL,
                term_id INTEGER NOT NULL,
                computed_score DECIMAL(5,2) NOT NULL,
                grade_letter VARCHAR(5) NULL,
                remark VARCHAR(100) NULL,
                breakdown_json TEXT NOT NULL DEFAULT '{}',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE student_term_summaries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                term_id INTEGER NOT NULL,
                class_id INTEGER NOT NULL,
                rank_in_class INTEGER NULL,
                attendance_present_count INTEGER NULL,
                attendance_total_count INTEGER NULL,
                class_teacher_remark TEXT NULL,
                principal_remark TEXT NULL
            );

            CREATE TABLE grading_scales (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                stage VARCHAR(50) NULL,
                is_default INTEGER NOT NULL DEFAULT 0
            );

            CREATE TABLE grade_boundaries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                grading_scale_id INTEGER NOT NULL,
                letter VARCHAR(5) NOT NULL,
                min_score DECIMAL(5,2) NOT NULL,
                max_score DECIMAL(5,2) NOT NULL,
                remark VARCHAR(100) NULL
            );

            CREATE TABLE system_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value TEXT NULL
            );
        ");
    }

    private function seedTestData(): void
    {
        // System Settings
        $this->pdo->exec("
            INSERT INTO system_settings (setting_key, setting_value) VALUES
            ('school_name', 'Claret International School'),
            ('school_motto', 'Discipline & Excellence'),
            ('school_address', 'Plot 700 Mabushi, Abuja');
        ");

        // Grading Scale for junior_secondary: A (70-100), B (60-69), C (50-59), P (40-49), F (0-39)
        $this->pdo->exec("
            INSERT INTO grading_scales (id, name, stage, is_default) VALUES (1, 'Junior Secondary Scale', 'junior_secondary', 0);
            INSERT INTO grade_boundaries (grading_scale_id, letter, min_score, max_score, remark) VALUES
            (1, 'A', 70.00, 100.00, 'EXCELLENT'),
            (1, 'B', 60.00, 69.99, 'VERY GOOD'),
            (1, 'C', 50.00, 59.99, 'CREDIT'),
            (1, 'P', 40.00, 49.99, 'PASS'),
            (1, 'F', 0.00, 39.99, 'FAIL');
        ");

        // User & Student
        $this->pdo->exec("
            INSERT INTO users (id, name, first_name, last_name, email, role, status) VALUES (10, 'Samuel Okonkwo', 'Samuel', 'Okonkwo', 'samuel@claret.edu', 'student', 'active');
            INSERT INTO students (id, user_id, admission_number, admission_date, date_of_birth, gender, status, current_class_id)
            VALUES (1, 10, 'CLT/2024/001', '2024-09-01', '2012-05-15', 'Male', 'active', 2);
        ");

        // Academic Levels
        $this->pdo->exec("
            INSERT INTO academic_levels (id, name, stage, rank_order, grading_scale_id) VALUES
            (1, 'JSS 1', 'junior_secondary', 1, 1),
            (2, 'JSS 2', 'junior_secondary', 2, 1);
        ");

        // Sessions: 2024/2025 and 2025/2026
        $this->pdo->exec("
            INSERT INTO sessions (id, name, start_date, end_date, status) VALUES
            (1, '2024/2025', '2024-09-01', '2025-07-20', 'archived'),
            (2, '2025/2026', '2025-09-01', '2026-07-20', 'active');
        ");

        // Classes: JSS 1 and JSS 2
        $this->pdo->exec("
            INSERT INTO classes (id, name, academic_level_id) VALUES
            (1, 'JSS 1 Alpha', 1),
            (2, 'JSS 2 Alpha', 2);
        ");

        // Terms
        $this->pdo->exec("
            INSERT INTO terms (id, session_id, name, start_date, end_date, status) VALUES
            (1, 1, 'First Term', '2024-09-01', '2024-12-15', 'archived'),
            (2, 1, 'Second Term', '2025-01-10', '2025-04-10', 'archived'),
            (3, 2, 'First Term', '2025-09-01', '2025-12-15', 'active');
        ");

        // Subjects: Mathematics, English Language
        $this->pdo->exec("
            INSERT INTO subjects (id, name, code) VALUES
            (1, 'Mathematics', 'MTH'),
            (2, 'English Language', 'ENG');
        ");

        // Class Subjects
        $this->pdo->exec("
            INSERT INTO class_subjects (id, class_id, subject_id) VALUES
            (1, 1, 1), -- JSS 1 Math
            (2, 1, 2), -- JSS 1 English
            (3, 2, 1), -- JSS 2 Math
            (4, 2, 2); -- JSS 2 English
        ");

        // Term Results:
        // Session 1, Term 1: Math 80 (A), Eng 70 (A) -> Avg: 75.0%
        $this->pdo->exec("
            INSERT INTO term_results (student_id, term_id, class_subject_id, computed_score, grade_letter, remark, breakdown_json) VALUES
            (1, 1, 1, 80.00, 'A', 'EXCELLENT', '{\"ca\": 30, \"exam\": 50}'),
            (1, 1, 2, 70.00, 'A', 'EXCELLENT', '{\"ca\": 25, \"exam\": 45}');

            INSERT INTO student_term_summaries (student_id, term_id, class_id, rank_in_class, attendance_present_count, attendance_total_count, class_teacher_remark, principal_remark) VALUES
            (1, 1, 1, 2, 58, 60, 'Outstanding performance.', 'Promising student.');
        ");

        // Session 1, Term 2: Math 85 (A), Eng 75 (A) -> Avg: 80.0%
        $this->pdo->exec("
            INSERT INTO term_results (student_id, term_id, class_subject_id, computed_score, grade_letter, remark, breakdown_json) VALUES
            (1, 2, 1, 85.00, 'A', 'EXCELLENT', '{\"ca\": 35, \"exam\": 50}'),
            (1, 2, 2, 75.00, 'A', 'EXCELLENT', '{\"ca\": 30, \"exam\": 45}');
        ");

        // Session 2, Term 1: Math 90 (A), Eng 80 (A) -> Avg: 85.0%
        $this->pdo->exec("
            INSERT INTO term_results (student_id, term_id, class_subject_id, computed_score, grade_letter, remark, breakdown_json) VALUES
            (1, 3, 3, 90.00, 'A', 'EXCELLENT', '{\"ca\": 35, \"exam\": 55}'),
            (1, 3, 4, 80.00, 'A', 'EXCELLENT', '{\"ca\": 32, \"exam\": 48}');
        ");
    }

    public function testGetStudentTranscriptDataAggregatesMultiSessionRecords(): void
    {
        $transcript = $this->service->getStudentTranscriptData(1);

        // Student identity
        $this->assertEquals(1, $transcript['student']['id']);
        $this->assertEquals('Samuel Okonkwo', $transcript['student']['name']);
        $this->assertEquals('CLT/2024/001', $transcript['student']['admission_number']);

        // Multi-Session Structure: 2 Sessions
        $this->assertCount(2, $transcript['sessions']);
        $this->assertEquals('2024/2025', $transcript['sessions'][0]['session_name']);
        $this->assertEquals('2025/2026', $transcript['sessions'][1]['session_name']);

        // Session 1 has 2 Terms
        $this->assertCount(2, $transcript['sessions'][0]['terms']);
        $this->assertEquals('First Term', $transcript['sessions'][0]['terms'][0]['term_name']);
        $this->assertEquals('Second Term', $transcript['sessions'][0]['terms'][1]['term_name']);

        // Check Subject Results in Session 1, Term 1 (Alphabetical order: English Language, Mathematics)
        $term1Subjects = $transcript['sessions'][0]['terms'][0]['subjects'];
        $this->assertCount(2, $term1Subjects);
        $this->assertEquals('English Language', $term1Subjects[0]['name']);
        $this->assertEquals(70.0, $term1Subjects[0]['score']);
        $this->assertEquals('A', $term1Subjects[0]['grade']);
        $this->assertEquals('EXCELLENT', $term1Subjects[0]['remark']);

        $this->assertEquals('Mathematics', $term1Subjects[1]['name']);
        $this->assertEquals(80.0, $term1Subjects[1]['score']);
        $this->assertEquals('A', $term1Subjects[1]['grade']);
        $this->assertEquals('EXCELLENT', $term1Subjects[1]['remark']);

        // Check Attendance & Summary data on term
        $term1 = $transcript['sessions'][0]['terms'][0];
        $this->assertEquals(2, $term1['class_rank']);
        $this->assertEquals('58/60', $term1['attendance']);
        $this->assertEquals('Outstanding performance.', $term1['teacher_remark']);
        $this->assertEquals('Promising student.', $term1['principal_remark']);

        // Cumulative Statistics across all 3 terms:
        // Total subjects = 6
        // Scores: 80, 70, 85, 75, 90, 80 -> sum = 480 / 6 = 80.0%
        $stats = $transcript['cumulative_stats'];
        $this->assertEquals(2, $stats['total_sessions']);
        $this->assertEquals(3, $stats['total_terms']);
        $this->assertEquals(6, $stats['total_subjects']);
        $this->assertEquals(480.0, $stats['total_marks_obtained']);
        $this->assertEquals(600.0, $stats['total_max_marks']);
        $this->assertEquals(80.0, $stats['cumulative_average']);
        $this->assertEquals('DISTINCTION (EXCELLENT)', $stats['honors_classification']);

        // Check Zero GPA Compliance: no GPA or CGPA fields
        $this->assertArrayNotHasKey('gpa', $stats);
        $this->assertArrayNotHasKey('cgpa', $stats);
        $this->assertArrayNotHasKey('grade_point', $stats);

        // Check Verification Reference & QR Code
        $verification = $transcript['verification'];
        $this->assertStringStartsWith('CLT-TRN-001-', $verification['reference']);
        $this->assertNotEmpty($verification['url']);
        $this->assertStringContainsString('<svg', $verification['qr_code_svg']);
    }

    public function testVerifyTranscriptReferenceWithValidReference(): void
    {
        $transcript = $this->service->getStudentTranscriptData(1);
        $validRef = $transcript['verification']['reference'];

        $result = $this->service->verifyTranscriptReference($validRef);

        $this->assertNotNull($result);
        $this->assertTrue($result['is_valid']);
        $this->assertEquals($validRef, $result['reference']);
        $this->assertEquals('Samuel Okonkwo', $result['student_name']);
        $this->assertEquals('CLT/2024/001', $result['admission_number']);
        $this->assertEquals(80.0, $result['cumulative_average']);
        $this->assertEquals('DISTINCTION (EXCELLENT)', $result['honors_classification']);
    }

    public function testVerifyTranscriptReferenceWithInvalidOrTamperedReference(): void
    {
        // Bad pattern
        $this->assertNull($this->service->verifyTranscriptReference('INVALID-CODE'));

        // Non-existent student
        $this->assertNull($this->service->verifyTranscriptReference('CLT-TRN-999-ABCD'));

        // Tampered checksum for existing student #1
        $this->assertNull($this->service->verifyTranscriptReference('CLT-TRN-001-ZZZZ'));
    }

    public function testScopeFilteringJuniorSecondary(): void
    {
        // Scope to junior_secondary
        $transcript = $this->service->getStudentTranscriptData(1, 'junior_secondary');
        $this->assertCount(2, $transcript['sessions']);

        // Scope to senior_secondary (none exist for student)
        $emptyTranscript = $this->service->getStudentTranscriptData(1, 'senior_secondary');
        $this->assertCount(0, $emptyTranscript['sessions']);
        $this->assertEquals(0, $emptyTranscript['cumulative_stats']['total_subjects']);
        $this->assertEquals(0.0, $emptyTranscript['cumulative_stats']['cumulative_average']);
    }
}
