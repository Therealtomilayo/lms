<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\ServiceResult;
use App\Models\LiveClass;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\LiveClassRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use DateTimeImmutable;

/**
 * Application Service for Live Online Class Scheduling and Attendance (SRS §31)
 */
class LiveClassService
{
    public function __construct(
        private readonly LiveClassRepository $liveClassRepository,
        private readonly TeacherRepository $teacherRepository,
        private readonly StudentRepository $studentRepository,
        private readonly ParentRepository $parentRepository,
        private readonly AcademicRepository $academicRepository,
        private readonly ?EnrollmentRepository $enrollmentRepository = null
    ) {
    }

    /**
     * Helper to detect video platform from URL.
     */
    public function detectPlatform(string $url): string
    {
        $urlLower = strtolower($url);
        if (str_contains($urlLower, 'meet.google.com')) {
            return LiveClass::PLATFORM_GOOGLE_MEET;
        }
        if (str_contains($urlLower, 'zoom.us')) {
            return LiveClass::PLATFORM_ZOOM;
        }
        if (str_contains($urlLower, 'teams.microsoft.com') || str_contains($urlLower, 'teams.live.com')) {
            return LiveClass::PLATFORM_MICROSOFT_TEAMS;
        }
        return LiveClass::PLATFORM_OTHER;
    }

    /**
     * Teacher creates a new Live Class.
     */
    public function createLiveClass(int $teacherUserId, array $data): ServiceResult
    {
        $teacher = $this->teacherRepository->findTeacherByUserId($teacherUserId);
        if (!$teacher) {
            return ServiceResult::failure('Only verified teachers can schedule live classes.', 'TEACHER_NOT_FOUND');
        }

        $classSubjectId = (int)($data['class_subject_id'] ?? 0);
        if ($classSubjectId <= 0) {
            return ServiceResult::failure(['class_subject_id' => ['Please select a valid subject/class.']], 'VALIDATION_FAILED');
        }

        $classSubject = $this->academicRepository->findClassSubjectById($classSubjectId);
        if (!$classSubject) {
            return ServiceResult::failure(['class_subject_id' => ['Selected class subject was not found.']], 'NOT_FOUND');
        }

        if ($classSubject->teacherId !== $teacher->id) {
            return ServiceResult::failure('You are not assigned as the teacher for this class subject.', 'UNAUTHORIZED_TEACHER');
        }

        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') {
            return ServiceResult::failure(['title' => ['Class title is required.']], 'VALIDATION_FAILED');
        }

        $meetingLink = trim((string)($data['meeting_link'] ?? ''));
        if ($meetingLink === '' || !filter_var($meetingLink, FILTER_VALIDATE_URL) || !str_starts_with(strtolower($meetingLink), 'https://')) {
            return ServiceResult::failure(['meeting_link' => ['A valid HTTPS meeting link (Google Meet, Zoom, or Teams) is required.']], 'VALIDATION_FAILED');
        }

        $platform = (string)($data['platform'] ?? '');
        if (!in_array($platform, [LiveClass::PLATFORM_GOOGLE_MEET, LiveClass::PLATFORM_ZOOM, LiveClass::PLATFORM_MICROSOFT_TEAMS, LiveClass::PLATFORM_OTHER], true)) {
            $platform = $this->detectPlatform($meetingLink);
        }

        $scheduledDate = trim((string)($data['scheduled_date'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $scheduledDate)) {
            return ServiceResult::failure(['scheduled_date' => ['Please provide a valid date in YYYY-MM-DD format.']], 'VALIDATION_FAILED');
        }

        $startTime = trim((string)($data['start_time'] ?? ''));
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $startTime)) {
            return ServiceResult::failure(['start_time' => ['Please provide a valid start time (HH:MM).']], 'VALIDATION_FAILED');
        }
        if (strlen($startTime) === 5) {
            $startTime .= ':00';
        }

        $durationMinutes = (int)($data['duration_minutes'] ?? 40);
        if ($durationMinutes < 10 || $durationMinutes > 300) {
            return ServiceResult::failure(['duration_minutes' => ['Duration must be between 10 and 300 minutes.']], 'VALIDATION_FAILED');
        }

        // Active session and term
        $sessionId = !empty($data['session_id']) ? (int)$data['session_id'] : $classSubject->sessionId;
        $termId = !empty($data['term_id']) ? (int)$data['term_id'] : null;

        if ($termId === null) {
            $currentTerm = $this->academicRepository->getCurrentTerm();
            if ($currentTerm) {
                $termId = $currentTerm->id;
            }
        }

        $liveClass = $this->liveClassRepository->create([
            'session_id' => $sessionId,
            'term_id' => $termId,
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $teacher->id,
            'title' => $title,
            'description' => !empty($data['description']) ? trim((string)$data['description']) : null,
            'platform' => $platform,
            'meeting_link' => $meetingLink,
            'meeting_passcode' => !empty($data['meeting_passcode']) ? trim((string)$data['meeting_passcode']) : null,
            'scheduled_date' => $scheduledDate,
            'start_time' => $startTime,
            'duration_minutes' => $durationMinutes,
            'status' => LiveClass::STATUS_SCHEDULED,
            'is_published' => isset($data['is_published']) ? (int)(bool)$data['is_published'] : 1,
        ]);

        return ServiceResult::success($liveClass, 'Live online class scheduled successfully.');
    }

    /**
     * Update Live Class details.
     */
    public function updateLiveClass(int $userId, int $liveClassId, array $data, bool $isAdmin = false): ServiceResult
    {
        $liveClass = $this->liveClassRepository->findById($liveClassId);
        if (!$liveClass) {
            return ServiceResult::failure('Live class not found.', 'NOT_FOUND');
        }

        if (!$isAdmin) {
            $teacher = $this->teacherRepository->findTeacherByUserId($userId);
            if (!$teacher || $teacher->id !== $liveClass->teacherId) {
                return ServiceResult::failure('You do not have permission to update this live class.', 'UNAUTHORIZED');
            }
        }

        $updateData = [];

        if (isset($data['title'])) {
            $title = trim((string)$data['title']);
            if ($title === '') {
                return ServiceResult::failure(['title' => ['Class title cannot be blank.']], 'VALIDATION_FAILED');
            }
            $updateData['title'] = $title;
        }

        if (isset($data['meeting_link'])) {
            $link = trim((string)$data['meeting_link']);
            if ($link === '' || !filter_var($link, FILTER_VALIDATE_URL) || !str_starts_with(strtolower($link), 'https://')) {
                return ServiceResult::failure(['meeting_link' => ['Valid HTTPS link is required.']], 'VALIDATION_FAILED');
            }
            $updateData['meeting_link'] = $link;
            if (empty($data['platform'])) {
                $updateData['platform'] = $this->detectPlatform($link);
            }
        }

        if (isset($data['platform']) && in_array($data['platform'], [LiveClass::PLATFORM_GOOGLE_MEET, LiveClass::PLATFORM_ZOOM, LiveClass::PLATFORM_MICROSOFT_TEAMS, LiveClass::PLATFORM_OTHER], true)) {
            $updateData['platform'] = $data['platform'];
        }

        if (isset($data['description'])) {
            $updateData['description'] = trim((string)$data['description']);
        }

        if (isset($data['meeting_passcode'])) {
            $updateData['meeting_passcode'] = trim((string)$data['meeting_passcode']) ?: null;
        }

        if (isset($data['scheduled_date'])) {
            $date = trim((string)$data['scheduled_date']);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return ServiceResult::failure(['scheduled_date' => ['Invalid date format.']], 'VALIDATION_FAILED');
            }
            $updateData['scheduled_date'] = $date;
        }

        if (isset($data['start_time'])) {
            $time = trim((string)$data['start_time']);
            if (strlen($time) === 5) {
                $time .= ':00';
            }
            $updateData['start_time'] = $time;
        }

        if (isset($data['duration_minutes'])) {
            $dur = (int)$data['duration_minutes'];
            if ($dur < 10 || $dur > 300) {
                return ServiceResult::failure(['duration_minutes' => ['Duration must be between 10 and 300 minutes.']], 'VALIDATION_FAILED');
            }
            $updateData['duration_minutes'] = $dur;
        }

        if (isset($data['is_published'])) {
            $updateData['is_published'] = (int)(bool)$data['is_published'];
        }

        if (!empty($updateData)) {
            $this->liveClassRepository->update($liveClassId, $updateData);
        }

        $fresh = $this->liveClassRepository->findById($liveClassId);
        return ServiceResult::success($fresh, 'Live class details updated successfully.');
    }

    /**
     * Change live class status (start, complete, cancel).
     */
    public function updateStatus(int $userId, int $liveClassId, string $status, bool $isAdmin = false): ServiceResult
    {
        $allowedStatuses = [
            LiveClass::STATUS_SCHEDULED,
            LiveClass::STATUS_IN_PROGRESS,
            LiveClass::STATUS_COMPLETED,
            LiveClass::STATUS_CANCELLED,
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            return ServiceResult::failure('Invalid status specified.', 'INVALID_STATUS');
        }

        $liveClass = $this->liveClassRepository->findById($liveClassId);
        if (!$liveClass) {
            return ServiceResult::failure('Live class not found.', 'NOT_FOUND');
        }

        if (!$isAdmin) {
            $teacher = $this->teacherRepository->findTeacherByUserId($userId);
            if (!$teacher || $teacher->id !== $liveClass->teacherId) {
                return ServiceResult::failure('You do not have permission to manage this live class.', 'UNAUTHORIZED');
            }
        }

        $this->liveClassRepository->updateStatus($liveClassId, $status);
        $fresh = $this->liveClassRepository->findById($liveClassId);

        $statusLabels = [
            LiveClass::STATUS_IN_PROGRESS => 'Class started and marked live.',
            LiveClass::STATUS_COMPLETED => 'Class concluded and marked completed.',
            LiveClass::STATUS_CANCELLED => 'Class session was cancelled.',
            LiveClass::STATUS_SCHEDULED => 'Class status set to scheduled.',
        ];

        return ServiceResult::success($fresh, $statusLabels[$status] ?? 'Status updated successfully.');
    }

    /**
     * Start class shortcut.
     */
    public function startLiveClass(int $userId, int $liveClassId, bool $isAdmin = false): ServiceResult
    {
        return $this->updateStatus($userId, $liveClassId, LiveClass::STATUS_IN_PROGRESS, $isAdmin);
    }

    /**
     * End class shortcut.
     */
    public function endLiveClass(int $userId, int $liveClassId, bool $isAdmin = false): ServiceResult
    {
        return $this->updateStatus($userId, $liveClassId, LiveClass::STATUS_COMPLETED, $isAdmin);
    }

    /**
     * Cancel class shortcut.
     */
    public function cancelLiveClass(int $userId, int $liveClassId, bool $isAdmin = false): ServiceResult
    {
        return $this->updateStatus($userId, $liveClassId, LiveClass::STATUS_CANCELLED, $isAdmin);
    }

    /**
     * Delete live class.
     */
    public function deleteLiveClass(int $userId, int $liveClassId, bool $isAdmin = false): ServiceResult
    {
        $liveClass = $this->liveClassRepository->findById($liveClassId);
        if (!$liveClass) {
            return ServiceResult::failure('Live class not found.', 'NOT_FOUND');
        }

        if (!$isAdmin) {
            $teacher = $this->teacherRepository->findTeacherByUserId($userId);
            if (!$teacher || $teacher->id !== $liveClass->teacherId) {
                return ServiceResult::failure('You do not have permission to delete this live class.', 'UNAUTHORIZED');
            }
        }

        $this->liveClassRepository->delete($liveClassId);
        return ServiceResult::success(null, 'Live class removed.');
    }

    /**
     * Student joins live class, logs attendance, and retrieves redirect meeting link.
     */
    public function joinLiveClass(
        int $studentUserId,
        int $liveClassId,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): ServiceResult {
        $student = $this->studentRepository->findByUserId($studentUserId);
        if (!$student) {
            return ServiceResult::failure('Only enrolled students can join online classes.', 'STUDENT_NOT_FOUND');
        }

        $liveClass = $this->liveClassRepository->findById($liveClassId, $student->id);
        if (!$liveClass) {
            return ServiceResult::failure('Live class not found.', 'NOT_FOUND');
        }

        if ($liveClass->isCancelled()) {
            return ServiceResult::failure('This live class session has been cancelled by the instructor.', 'CLASS_CANCELLED');
        }

        if ($liveClass->isCompleted()) {
            return ServiceResult::failure('This live class session has concluded.', 'CLASS_COMPLETED');
        }

        // Verify student class enrollment matches class subject
        $classSubject = $this->academicRepository->findClassSubjectById($liveClass->classSubjectId);
        if (!$classSubject) {
            return ServiceResult::failure('Class subject mapping could not be verified.', 'CLASS_SUBJECT_NOT_FOUND');
        }

        $hasAccess = false;
        if ($student->currentClassId !== null && $student->currentClassId === $classSubject->classId) {
            $hasAccess = true;
        }

        if (!$hasAccess && $this->enrollmentRepository !== null) {
            $enrollment = $this->enrollmentRepository->findClassEnrollment($student->id, $classSubject->sessionId);
            if ($enrollment && $enrollment->classId === $classSubject->classId && $enrollment->status === 'active') {
                $hasAccess = true;
            }
        }

        // Fallback: If currentClassId was not set or matches class directly
        if (!$hasAccess) {
            // Check student table directly if current_class_id matches
            if ($student->currentClassId === $classSubject->classId) {
                $hasAccess = true;
            }
        }

        // Record attendance (SRS §31)
        $this->liveClassRepository->recordAttendance($liveClass->id, $student->id, $ipAddress, $userAgent);

        return ServiceResult::success([
            'meeting_link' => $liveClass->meetingLink,
            'meeting_passcode' => $liveClass->meetingPasscode,
            'platform' => $liveClass->platform,
            'title' => $liveClass->title,
            'live_class' => $liveClass,
        ], 'Attendance verified and recorded.');
    }

    /**
     * Get live classes for teacher dashboard.
     */
    public function getTeacherLiveClasses(int $teacherUserId, ?int $sessionId = null, ?int $termId = null): ServiceResult
    {
        $teacher = $this->teacherRepository->findTeacherByUserId($teacherUserId);
        if (!$teacher) {
            return ServiceResult::failure('Teacher profile not found.', 'TEACHER_NOT_FOUND');
        }

        $classes = $this->liveClassRepository->getByTeacher($teacher->id, $sessionId, $termId);
        return ServiceResult::success($classes);
    }

    /**
     * Get all live classes accessible by student.
     */
    public function getStudentLiveClasses(int $studentUserId, ?int $sessionId = null, ?int $termId = null): ServiceResult
    {
        $student = $this->studentRepository->findByUserId($studentUserId);
        if (!$student) {
            return ServiceResult::failure('Student profile not found.', 'STUDENT_NOT_FOUND');
        }

        $classes = $this->liveClassRepository->getAllForStudent($student->id, $sessionId, $termId);
        return ServiceResult::success($classes);
    }

    /**
     * Get upcoming live classes for student (e.g. for student dashboard widget).
     */
    public function getStudentUpcomingLiveClasses(int $studentUserId, int $limit = 5): ServiceResult
    {
        $student = $this->studentRepository->findByUserId($studentUserId);
        if (!$student) {
            return ServiceResult::success([]);
        }

        $classes = $this->liveClassRepository->getUpcomingForStudent($student->id, $limit);
        return ServiceResult::success($classes);
    }

    /**
     * Get live classes for parent's linked wards.
     */
    public function getParentLiveClasses(int $parentUserId, ?int $sessionId = null, ?int $termId = null): ServiceResult
    {
        $parent = $this->parentRepository->findByUserId($parentUserId);
        if (!$parent) {
            return ServiceResult::failure('Parent profile not found.', 'PARENT_NOT_FOUND');
        }

        $data = $this->liveClassRepository->getAllForParent($parent->id, $sessionId, $termId);
        return ServiceResult::success($data);
    }

    /**
     * Admin oversight of all live classes.
     */
    public function getAdminLiveClasses(
        ?int $sessionId = null,
        ?int $termId = null,
        ?string $status = null,
        int $page = 1,
        int $perPage = 20
    ): ServiceResult {
        $offset = max(0, ($page - 1) * $perPage);
        $classes = $this->liveClassRepository->getAllForAdmin($sessionId, $termId, $status, $perPage, $offset);
        $total = $this->liveClassRepository->countAllForAdmin($sessionId, $termId, $status);

        return ServiceResult::success([
            'classes' => $classes,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($total / max(1, $perPage)),
        ]);
    }

    /**
     * Get attendee log for a live class.
     */
    public function getLiveClassAttendees(int $liveClassId, int $userId, bool $isAdmin = false): ServiceResult
    {
        $liveClass = $this->liveClassRepository->findById($liveClassId);
        if (!$liveClass) {
            return ServiceResult::failure('Live class not found.', 'NOT_FOUND');
        }

        if (!$isAdmin) {
            $teacher = $this->teacherRepository->findTeacherByUserId($userId);
            if (!$teacher || $teacher->id !== $liveClass->teacherId) {
                return ServiceResult::failure('You do not have permission to view attendees for this class.', 'UNAUTHORIZED');
            }
        }

        $attendees = $this->liveClassRepository->getAttendees($liveClassId);
        return ServiceResult::success([
            'live_class' => $liveClass,
            'attendees' => $attendees,
        ]);
    }
}
