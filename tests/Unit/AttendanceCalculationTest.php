<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\AttendanceRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class AttendanceCalculationTest extends TestCase
{
    private PDO $db;
    private AttendanceRepository $repo;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->db->exec("
            CREATE TABLE attendance_records (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id INTEGER,
                term_id INTEGER,
                class_id INTEGER,
                class_subject_id INTEGER,
                student_id INTEGER,
                date TEXT,
                period_number INTEGER,
                status TEXT,
                marked_by INTEGER,
                updated_by INTEGER,
                correction_reason TEXT,
                created_at TEXT,
                updated_at TEXT
            );
        ");

        $this->repo = new AttendanceRepository($this->db);
    }

    public function testAttendanceRateCalculationWithPresentAndLate(): void
    {
        // 10 total days: 6 present, 2 late, 1 absent, 1 excused
        // Attended = 6 + 2 = 8. Rate = (8/10) * 100 = 80.0%
        $statuses = ['present', 'present', 'present', 'present', 'present', 'present', 'late', 'late', 'absent', 'excused'];
        foreach ($statuses as $idx => $st) {
            $this->db->exec("
                INSERT INTO attendance_records (session_id, term_id, class_id, student_id, date, status, marked_by)
                VALUES (1, 1, 1, 10, '2026-01-" . str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT) . "', '{$st}', 1)
            ");
        }

        $summary = $this->repo->getStudentAttendanceSummary(10, 1);

        $this->assertSame(10, $summary['total_days']);
        $this->assertSame(6, $summary['present_days']);
        $this->assertSame(2, $summary['late_days']);
        $this->assertSame(1, $summary['absent_days']);
        $this->assertSame(1, $summary['excused_days']);
        $this->assertSame(80.0, $summary['attendance_rate']);
    }

    public function testZeroAttendanceRecordsFallback(): void
    {
        $summary = $this->repo->getStudentAttendanceSummary(999, 1);

        $this->assertSame(0, $summary['total_days']);
        $this->assertSame(0, $summary['present_days']);
        $this->assertSame(0, $summary['late_days']);
        $this->assertSame(0, $summary['absent_days']);
        $this->assertSame(0, $summary['excused_days']);
        $this->assertSame(100.0, $summary['attendance_rate']);
    }
}
