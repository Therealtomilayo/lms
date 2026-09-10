<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Config;
use App\Models\StaffAttendance;
use App\Repositories\AcademicRepository;
use App\Repositories\StaffAttendanceRepository;
use App\Services\StaffAttendanceService;
use PDO;
use PHPUnit\Framework\TestCase;

class StaffAttendanceIntegrationTest extends TestCase
{
    private PDO $db;
    private StaffAttendanceRepository $attendanceRepo;
    private AcademicRepository $academicRepo;
    private StaffAttendanceService $attendanceService;

    private int $teacherUserId = 10;
    private int $adminUserId = 1;

    protected function setUp(): void
    {
        Config::reset();
        Config::load(dirname(__DIR__, 2) . '/config/.env');

        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // SQLite schema setup
        $this->db->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'active'
            );

            CREATE TABLE user_roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                role TEXT NOT NULL
            );

            CREATE TABLE system_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key TEXT UNIQUE NOT NULL,
                setting_value TEXT NULL,
                is_secret INTEGER DEFAULT 0,
                updated_by INTEGER NULL,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'active',
                is_current INTEGER DEFAULT 1
            );

            CREATE TABLE terms (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'active',
                is_current INTEGER DEFAULT 1
            );

            CREATE TABLE staff_attendance (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                session_id INTEGER NULL,
                term_id INTEGER NULL,
                attendance_date TEXT NOT NULL,
                clock_in_at TEXT NOT NULL,
                clock_out_at TEXT NULL,
                clock_in_latitude REAL NOT NULL,
                clock_in_longitude REAL NOT NULL,
                clock_in_distance_meters INTEGER NOT NULL,
                clock_out_latitude REAL NULL,
                clock_out_longitude REAL NULL,
                clock_out_distance_meters INTEGER NULL,
                status TEXT NOT NULL DEFAULT 'present',
                is_late INTEGER NOT NULL DEFAULT 0,
                is_early_departure INTEGER NOT NULL DEFAULT 0,
                work_duration_minutes INTEGER NULL,
                device_fingerprint TEXT NULL,
                ip_address TEXT NULL,
                user_agent TEXT NULL,
                notes TEXT NULL,
                is_offline_sync INTEGER NOT NULL DEFAULT 0,
                synced_at TEXT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(user_id, attendance_date)
            );

            CREATE TABLE staff_attendance_breaches (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                attempt_type TEXT NOT NULL DEFAULT 'clock_in',
                attempted_at TEXT DEFAULT CURRENT_TIMESTAMP,
                latitude REAL NOT NULL,
                longitude REAL NOT NULL,
                distance_meters INTEGER NOT NULL,
                allowed_radius_meters INTEGER NOT NULL,
                ip_address TEXT NULL,
                device_fingerprint TEXT NULL,
                user_agent TEXT NULL,
                failure_reason TEXT NOT NULL
            );
        ");

        // Seed basic users and roles
        $this->db->exec("
            INSERT INTO users (id, name, email, status) VALUES 
                (1, 'Super Administrator', 'superadmin@claret.edu', 'active'),
                (10, 'Okonkwo Chukwuma', 'okonwko@claret.edu', 'active'),
                (11, 'Grace Adeleke', 'grace@claret.edu', 'active');

            INSERT INTO user_roles (user_id, role) VALUES (1, 'super_admin'), (10, 'teacher'), (11, 'teacher');

            INSERT INTO sessions (id, name, status, is_current) VALUES (1, '2026/2027', 'active', 1);
            INSERT INTO terms (id, session_id, name, status, is_current) VALUES (1, 1, 'First Term', 'active', 1);

            INSERT INTO system_settings (setting_key, setting_value) VALUES 
                ('staff_geofence_latitude', '9.08820000'),
                ('staff_geofence_longitude', '7.46410000'),
                ('staff_geofence_radius_meters', '250'),
                ('staff_workday_start_time', '08:00'),
                ('staff_workday_late_threshold', '08:15'),
                ('staff_workday_end_time', '15:30'),
                ('staff_geofence_enforced', '1');
        ");

        $this->attendanceRepo = new StaffAttendanceRepository($this->db);
        $this->academicRepo = new AcademicRepository($this->db);
        $this->attendanceService = new StaffAttendanceService(
            $this->attendanceRepo,
            $this->academicRepo,
            $this->db
        );
    }

    public function testCalculateDistanceMetersUsingHaversine(): void
    {
        // Mabushi center: 9.0882, 7.4641
        // Point 0 meters away:
        $distSame = $this->attendanceService->calculateDistanceMeters(9.0882, 7.4641, 9.0882, 7.4641);
        $this->assertEqualsWithDelta(0.0, $distSame, 0.1);

        // Point nearby (~110 meters north)
        $distNear = $this->attendanceService->calculateDistanceMeters(9.0882, 7.4641, 9.0892, 7.4641);
        $this->assertGreaterThan(100.0, $distNear);
        $this->assertLessThan(125.0, $distNear);

        // Point far away in Lagos (6.5244, 3.3792) -> ~530 km
        $distFar = $this->attendanceService->calculateDistanceMeters(9.0882, 7.4641, 6.5244, 3.3792);
        $this->assertGreaterThan(500000.0, $distFar);
    }

    public function testSuccessfulClockInWithinGeofence(): void
    {
        // Exactly on campus: Mabushi coordinates
        $res = $this->attendanceService->clockIn(
            userId: $this->teacherUserId,
            latitude: 9.0882,
            longitude: 7.4641,
            fingerprint: 'fp_test_123',
            ip: '127.0.0.1',
            userAgent: 'Mozilla/5.0 PHPUnit'
        );

        $this->assertTrue($res->isSuccess(), 'Clock in should succeed within campus perimeter.');
        /** @var StaffAttendance $att */
        $att = $res->getData();
        $this->assertInstanceOf(StaffAttendance::class, $att);
        $this->assertSame($this->teacherUserId, $att->userId);
        $this->assertSame(date('Y-m-d'), $att->attendanceDate);
        $this->assertLessThanOrEqual(5, $att->clockInDistanceMeters);
        $this->assertFalse($att->isClockedOut());

        // Verify database persistence
        $saved = $this->attendanceRepo->getTodayAttendanceForUser($this->teacherUserId, date('Y-m-d'));
        $this->assertNotNull($saved);
        $this->assertSame('okonwko@claret.edu', $saved->staffEmail);
    }

    public function testClockInRejectedOutsideGeofencePerimeterAndLogged(): void
    {
        // 2 km away from campus
        $remoteLat = 9.1100;
        $remoteLng = 7.4641;

        $res = $this->attendanceService->clockIn(
            userId: $this->teacherUserId,
            latitude: $remoteLat,
            longitude: $remoteLng,
            fingerprint: 'fp_cheat_device',
            ip: '197.210.45.12',
            userAgent: 'Mozilla/5.0 FraudCheck'
        );

        $this->assertFalse($res->isSuccess(), 'Clock in outside geofence must be rejected.');
        $this->assertSame('OUT_OF_BOUNDS', $res->errorCode);
        $this->assertStringContainsString('Clock-in rejected', $res->getMessage());
        $this->assertStringContainsString('campus perimeter', $res->getMessage());

        // Verify breach was recorded in security log
        $breaches = $this->attendanceRepo->getBreaches(date('Y-m-d'), 10);
        $this->assertCount(1, $breaches);
        $this->assertSame($this->teacherUserId, $breaches[0]->userId);
        $this->assertGreaterThan(250, $breaches[0]->distanceMeters);
        $this->assertSame('fp_cheat_device', $breaches[0]->deviceFingerprint);
    }

    public function testPunctualityStatusCalculation(): void
    {
        // 1. Punctual arrival (07:55 AM)
        $resOnTime = $this->attendanceService->clockIn(
            userId: $this->teacherUserId,
            latitude: 9.0882,
            longitude: 7.4641,
            isOffline: true,
            offlineTimestamp: '2026-09-09 07:55:00'
        );

        $this->assertTrue($resOnTime->isSuccess());
        /** @var StaffAttendance $attOnTime */
        $attOnTime = $resOnTime->getData();
        $this->assertSame(StaffAttendance::STATUS_PRESENT, $attOnTime->status);
        $this->assertFalse($attOnTime->isLate);

        // 2. Late arrival for another teacher (08:35 AM - threshold is 08:15 AM)
        $resLate = $this->attendanceService->clockIn(
            userId: 11, // Grace Adeleke
            latitude: 9.0882,
            longitude: 7.4641,
            isOffline: true,
            offlineTimestamp: '2026-09-09 08:35:00'
        );

        $this->assertTrue($resLate->isSuccess());
        /** @var StaffAttendance $attLate */
        $attLate = $resLate->getData();
        $this->assertSame(StaffAttendance::STATUS_LATE, $attLate->status);
        $this->assertTrue($attLate->isLate);
    }

    public function testClockOutAndDutyDuration(): void
    {
        // Clock in at 07:30 AM
        $this->attendanceRepo->recordClockIn([
            'user_id' => $this->teacherUserId,
            'session_id' => 1,
            'term_id' => 1,
            'attendance_date' => date('Y-m-d'),
            'clock_in_at' => date('Y-m-d') . ' 07:30:00',
            'clock_in_latitude' => 9.0882,
            'clock_in_longitude' => 7.4641,
            'clock_in_distance_meters' => 20,
            'status' => 'present',
            'is_late' => 0,
        ]);

        // Clock out now
        $resOut = $this->attendanceService->clockOut(
            userId: $this->teacherUserId,
            latitude: 9.0882,
            longitude: 7.4641,
            fingerprint: 'fp_test_123',
            notes: 'Completed all lessons for the day'
        );

        $this->assertTrue($resOut->isSuccess());
        /** @var StaffAttendance $updated */
        $updated = $resOut->getData();
        $this->assertTrue($updated->isClockedOut());
        $this->assertNotNull($updated->clockOutAt);
        $this->assertGreaterThanOrEqual(0, $updated->workDurationMinutes);
    }

    public function testPreventDuplicateClockInOnSameDate(): void
    {
        $res1 = $this->attendanceService->clockIn(
            userId: $this->teacherUserId,
            latitude: 9.0882,
            longitude: 7.4641
        );
        $this->assertTrue($res1->isSuccess());

        // Second clock in on same date
        $res2 = $this->attendanceService->clockIn(
            userId: $this->teacherUserId,
            latitude: 9.0882,
            longitude: 7.4641
        );
        $this->assertFalse($res2->isSuccess());
        $this->assertSame('DUPLICATE_ENTRY', $res2->errorCode);
    }

    public function testOfflineRecordsBatchSynchronization(): void
    {
        $offlineQueue = [
            [
                'action' => 'clock_in',
                'latitude' => 9.0882,
                'longitude' => 7.4641,
                'timestamp' => '2026-09-08 07:45:00',
                'fingerprint' => 'fp_offline_device',
                'notes' => 'Offline recorded due to network drop'
            ],
            [
                'action' => 'clock_out',
                'latitude' => 9.0882,
                'longitude' => 7.4641,
                'timestamp' => '2026-09-08 15:45:00',
                'fingerprint' => 'fp_offline_device',
                'notes' => 'Duty concluded'
            ]
        ];

        $syncRes = $this->attendanceService->syncOfflineRecords(
            userId: $this->teacherUserId,
            records: $offlineQueue,
            ip: '127.0.0.1',
            userAgent: 'OfflineSyncAgent'
        );

        $this->assertCount(2, $syncRes['synced']);
        $this->assertCount(0, $syncRes['failed']);

        $record = $this->attendanceRepo->getTodayAttendanceForUser($this->teacherUserId, '2026-09-08');
        $this->assertNotNull($record);
        $this->assertTrue($record->isOfflineSync);
        $this->assertSame('present', $record->status);
    }

    public function testAdminGeofenceSettingsUpdate(): void
    {
        $newConfig = [
            'latitude' => 9.0900,
            'longitude' => 7.4700,
            'radius_meters' => 450,
            'workday_start_time' => '07:30',
            'workday_late_threshold' => '08:00',
            'workday_end_time' => '16:00',
            'enforced' => 1,
        ];

        $this->attendanceService->updateGeofenceConfig($newConfig, $this->adminUserId);

        $loaded = $this->attendanceService->getGeofenceConfig();
        $this->assertSame(9.09, $loaded['latitude']);
        $this->assertSame(7.47, $loaded['longitude']);
        $this->assertSame(450, $loaded['radius_meters']);
        $this->assertSame('08:00', $loaded['workday_late_threshold']);
    }

    public function testDailySummaryAndRegisterFilters(): void
    {
        $today = date('Y-m-d');
        $this->attendanceRepo->recordClockIn([
            'user_id' => $this->teacherUserId,
            'session_id' => 1,
            'term_id' => 1,
            'attendance_date' => $today,
            'clock_in_at' => "{$today} 08:05:00",
            'clock_in_latitude' => 9.0882,
            'clock_in_longitude' => 7.4641,
            'clock_in_distance_meters' => 15,
            'status' => 'present',
            'is_late' => 0,
        ]);

        $summary = $this->attendanceRepo->getDailySummary($today);
        $this->assertGreaterThanOrEqual(1, $summary['clocked_in']);

        $register = $this->attendanceRepo->getDailyRegister($today, 'present', 'Okonkwo');
        $this->assertCount(1, $register);
        $this->assertSame('Okonkwo Chukwuma', $register[0]->staffName);
    }
}
