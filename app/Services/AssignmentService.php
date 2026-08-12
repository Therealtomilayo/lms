<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\DTO\ServiceResult;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Policies\AssignmentPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\AssignmentRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\FileRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;

/**
 * Application Service for Coursework Assignments and Grading Lifecycle
 * Implements teacher authoring, student submission intake, server-authoritative late detection,
 * and teacher grading with score boundary validation.
 */
class AssignmentService
{
    private AssignmentRepository $assignmentRepository;
    private AcademicRepository $academicRepository;
    private TeacherRepository $teacherRepository;
    private StudentRepository $studentRepository;
    private EnrollmentRepository $enrollmentRepository;
    private ParentRepository $parentRepository;
    private FileRepository $fileRepository;
    private FileStorageService $fileStorageService;

    public function __construct(
        ?AssignmentRepository $assignmentRepository = null,
        ?AcademicRepository $academicRepository = null,
        ?TeacherRepository $teacherRepository = null,
        ?StudentRepository $studentRepository = null,
        ?EnrollmentRepository $enrollmentRepository = null,
        ?ParentRepository $parentRepository = null,
        ?FileRepository $fileRepository = null,
        ?FileStorageService $fileStorageService = null
    ) {
        $this->assignmentRepository = $assignmentRepository ?? new AssignmentRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->teacherRepository = $teacherRepository ?? new TeacherRepository();
        $this->studentRepository = $studentRepository ?? new StudentRepository();
        $this->enrollmentRepository = $enrollmentRepository ?? new EnrollmentRepository();
        $this->parentRepository = $parentRepository ?? new ParentRepository();
        $this->fileRepository = $fileRepository ?? new FileRepository();
        $this->fileStorageService = $fileStorageService ?? new FileStorageService(
            fileRepository: $this->fileRepository,
            academicRepository: $this->academicRepository,
            teacherRepository: $this->teacherRepository,
            studentRepository: $this->studentRepository,
            enrollmentRepository: $this->enrollmentRepository,
            parentRepository: $this->parentRepository,
            assignmentRepository: $this->assignmentRepository
        );
    }

    /**
     * Create a new coursework assignment.
     */
    public function createAssignment(array $data, ?array $uploadedFile, UserContext $actor): ServiceResult
    {
        $errors = [];

        $classSubjectId = (int)($data['class_subject_id'] ?? 0);
        $termId = (int)($data['term_id'] ?? 0);
        $title = trim((string)($data['title'] ?? ''));
        $instructions = trim((string)($data['instructions'] ?? ''));
        $dueAt = trim((string)($data['due_at'] ?? ''));
        $maxScore = isset($data['max_score']) && $data['max_score'] !== '' ? (float)$data['max_score'] : 100.00;
        $topic = isset($data['topic']) && trim((string)$data['topic']) !== '' ? trim((string)$data['topic']) : null;
        $status = in_array($data['status'] ?? '', [Assignment::STATUS_DRAFT, Assignment::STATUS_PUBLISHED, Assignment::STATUS_ARCHIVED], true)
            ? $data['status']
            : Assignment::STATUS_PUBLISHED;

        if ($classSubjectId <= 0) {
            $errors['class_subject_id'][] = 'Please select a valid subject.';
        }

        if ($termId <= 0) {
            $errors['term_id'][] = 'Please select a valid term.';
        }

        if ($title === '') {
            $errors['title'][] = 'Assignment title is required.';
        } elseif (mb_strlen($title) > 200) {
            $errors['title'][] = 'Assignment title must not exceed 200 characters.';
        }

        if ($instructions === '') {
            $errors['instructions'][] = 'Instructions are required.';
        }

        if ($dueAt === '' || strtotime($dueAt) === false) {
            $errors['due_at'][] = 'A valid due date and time is required.';
        }

        if ($maxScore <= 0 || $maxScore > 1000) {
            $errors['max_score'][] = 'Maximum score must be greater than 0 and at most 1000.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Policy Check
        if (!AssignmentPolicy::canCreateAssignment(
            $actor,
            $classSubjectId,
            $termId,
            $this->academicRepository,
            $this->teacherRepository
        )) {
            throw new AuthorizationException('You are not authorized to create assignments for this class subject.');
        }

        $classSubject = $this->academicRepository->findClassSubjectById($classSubjectId);
        $term = $this->academicRepository->findTermById($termId);

        if (!$classSubject || !$term) {
            throw new ResourceNotFoundException('Class subject or academic term not found.');
        }

        // Cross-Session Invariant Check: term.session_id === class_subject.session_id
        if ($classSubject->sessionId !== $term->sessionId) {
            throw new DomainRuleException('The selected term does not belong to the same academic session as the class subject.');
        }

        $teacher = $this->teacherRepository->findTeacherByUserId($actor->id);
        $teacherId = $teacher ? $teacher->id : $classSubject->teacherId;

        // Process optional file attachment
        $fileId = null;
        $tempFileRecord = null;
        if ($uploadedFile && isset($uploadedFile['error']) && $uploadedFile['error'] !== UPLOAD_ERR_NO_FILE) {
            $tempFileRecord = $this->fileStorageService->storeUploadedFile(
                $uploadedFile,
                $actor->id,
                'assignment',
                0 // temporary owner id until assignment is created
            );
            $fileId = $tempFileRecord->id;
        }

        $assignment = $this->assignmentRepository->create(
            classSubjectId: $classSubjectId,
            termId: $termId,
            teacherId: $teacherId,
            title: $title,
            instructions: $instructions,
            dueAt: $dueAt,
            maxScore: $maxScore,
            topic: $topic,
            fileId: $fileId,
            status: $status
        );

        if ($tempFileRecord) {
            $this->fileRepository->updateOwner($tempFileRecord->id, 'assignment', $assignment->id);
        }

        return ServiceResult::success($assignment);
    }

    /**
     * Update an existing assignment.
     */
    public function updateAssignment(int $id, array $data, ?array $uploadedFile, UserContext $actor): ServiceResult
    {
        $assignment = $this->assignmentRepository->findById($id);
        if (!$assignment) {
            throw new ResourceNotFoundException('Assignment not found.');
        }

        if (!AssignmentPolicy::canEditAssignment(
            $actor,
            $assignment,
            $this->academicRepository,
            $this->teacherRepository
        )) {
            throw new AuthorizationException('You are not authorized to edit this assignment.');
        }

        $errors = [];
        $updateData = [];

        if (array_key_exists('title', $data)) {
            $title = trim((string)$data['title']);
            if ($title === '') {
                $errors['title'][] = 'Assignment title is required.';
            } elseif (mb_strlen($title) > 200) {
                $errors['title'][] = 'Assignment title must not exceed 200 characters.';
            } else {
                $updateData['title'] = $title;
            }
        }

        if (array_key_exists('instructions', $data)) {
            $instructions = trim((string)$data['instructions']);
            if ($instructions === '') {
                $errors['instructions'][] = 'Instructions are required.';
            } else {
                $updateData['instructions'] = $instructions;
            }
        }

        if (array_key_exists('topic', $data)) {
            $updateData['topic'] = trim((string)$data['topic']);
        }

        if (array_key_exists('due_at', $data)) {
            $dueAt = trim((string)$data['due_at']);
            if ($dueAt === '' || strtotime($dueAt) === false) {
                $errors['due_at'][] = 'A valid due date and time is required.';
            } else {
                $updateData['due_at'] = $dueAt;
            }
        }

        if (array_key_exists('max_score', $data)) {
            $maxScore = (float)$data['max_score'];
            if ($maxScore <= 0 || $maxScore > 1000) {
                $errors['max_score'][] = 'Maximum score must be greater than 0 and at most 1000.';
            } else {
                $updateData['max_score'] = $maxScore;
            }
        }

        if (array_key_exists('status', $data)) {
            if (in_array($data['status'], [Assignment::STATUS_DRAFT, Assignment::STATUS_PUBLISHED, Assignment::STATUS_ARCHIVED], true)) {
                $updateData['status'] = $data['status'];
            }
        }

        if (array_key_exists('term_id', $data)) {
            $termId = (int)$data['term_id'];
            $term = $this->academicRepository->findTermById($termId);
            $classSubject = $this->academicRepository->findClassSubjectById($assignment->classSubjectId);
            if (!$term || !$classSubject || $term->sessionId !== $classSubject->sessionId) {
                $errors['term_id'][] = 'Invalid term or cross-session mismatch.';
            } else {
                $updateData['term_id'] = $termId;
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Process optional attachment update
        if ($uploadedFile && isset($uploadedFile['error']) && $uploadedFile['error'] !== UPLOAD_ERR_NO_FILE) {
            $fileRecord = $this->fileStorageService->storeUploadedFile(
                $uploadedFile,
                $actor->id,
                'assignment',
                $assignment->id
            );
            $updateData['file_id'] = $fileRecord->id;
        }

        $this->assignmentRepository->update($id, $updateData);

        return ServiceResult::success($this->assignmentRepository->findById($id));
    }

    /**
     * Delete/archive an assignment following historical data preservation rules.
     */
    public function deleteAssignment(int $id, UserContext $actor): ServiceResult
    {
        $assignment = $this->assignmentRepository->findById($id);
        if (!$assignment) {
            throw new ResourceNotFoundException('Assignment not found.');
        }

        if (!AssignmentPolicy::canDeleteAssignment(
            $actor,
            $assignment,
            $this->academicRepository,
            $this->teacherRepository
        )) {
            throw new AuthorizationException('You are not authorized to delete this assignment.');
        }

        $submissionCount = $this->assignmentRepository->countSubmissions($id);
        if ($submissionCount > 0) {
            // Preserve historical submissions: archive instead of hard delete
            $this->assignmentRepository->update($id, ['status' => Assignment::STATUS_ARCHIVED]);
            return ServiceResult::success(null);
        }

        // No submissions exist, safe to hard delete
        $this->assignmentRepository->delete($id);

        return ServiceResult::success(null);
    }

    /**
     * Submit coursework on behalf of an enrolled student.
     */
    public function submitAssignment(
        int $assignmentId,
        array $data,
        ?array $uploadedFile,
        UserContext $actor
    ): ServiceResult {
        $assignment = $this->assignmentRepository->findById($assignmentId);
        if (!$assignment) {
            throw new ResourceNotFoundException('Assignment not found.');
        }

        if (!$actor->hasRole('student')) {
            throw new AuthorizationException('Only students can submit assignments.');
        }

        $student = $this->studentRepository->findByUserId($actor->id);
        if (!$student) {
            throw new AuthorizationException('Student profile not found.');
        }

        if (!AssignmentPolicy::canSubmitAssignment(
            $actor,
            $assignment,
            $this->academicRepository,
            $this->studentRepository,
            $this->enrollmentRepository
        )) {
            throw new AuthorizationException('You are not eligible to submit this assignment.');
        }

        $textResponse = isset($data['text_response']) && trim((string)$data['text_response']) !== ''
            ? trim((string)$data['text_response'])
            : null;

        $hasUpload = $uploadedFile && isset($uploadedFile['error']) && $uploadedFile['error'] !== UPLOAD_ERR_NO_FILE;

        if ($textResponse === null && !$hasUpload) {
            throw new ValidationException(['submission' => ['You must provide a text response or upload a file.']]);
        }

        $existing = $this->assignmentRepository->findSubmissionByAssignmentAndStudent($assignmentId, $student->id);
        if ($existing && $existing->isGraded()) {
            throw new DomainRuleException('This submission has already been graded and cannot be resubmitted.');
        }

        $now = date('Y-m-d H:i:s');
        $fileId = $existing?->fileId;

        if ($hasUpload) {
            $fileRecord = $this->fileStorageService->storeUploadedFile(
                $uploadedFile,
                $actor->id,
                'assignment_submission',
                $existing ? $existing->id : 0
            );
            $fileId = $fileRecord->id;
        }

        if ($existing) {
            $this->assignmentRepository->updateSubmission($existing->id, [
                'text_response' => $textResponse,
                'file_id' => $fileId,
                'submitted_at' => $now,
            ]);
            $submission = $this->assignmentRepository->findSubmissionById($existing->id);
        } else {
            $submission = $this->assignmentRepository->createSubmission(
                assignmentId: $assignmentId,
                studentId: $student->id,
                submittedAt: $now,
                fileId: $fileId,
                textResponse: $textResponse
            );

            if ($hasUpload && $fileId) {
                $this->fileRepository->updateOwner($fileId, 'assignment_submission', $submission->id);
            }
        }

        return ServiceResult::success($submission);
    }

    /**
     * Grade a student's submission.
     */
    public function gradeSubmission(
        int $submissionId,
        float $score,
        ?string $teacherComment,
        UserContext $actor
    ): ServiceResult {
        $submission = $this->assignmentRepository->findSubmissionById($submissionId);
        if (!$submission) {
            throw new ResourceNotFoundException('Submission not found.');
        }

        if (!AssignmentPolicy::canGradeSubmission(
            $actor,
            $submission,
            $this->assignmentRepository,
            $this->academicRepository,
            $this->teacherRepository
        )) {
            throw new AuthorizationException('You are not authorized to grade this submission.');
        }

        $maxScore = $submission->assignment ? $submission->assignment->maxScore : 100.00;

        if ($score < 0 || $score > $maxScore) {
            throw new ValidationException([
                'score' => ["Score must be between 0 and {$maxScore}."]
            ]);
        }

        $teacher = $this->teacherRepository->findTeacherByUserId($actor->id);
        $teacherId = $teacher ? $teacher->id : ($submission->assignment ? $submission->assignment->teacherId : 0);

        $this->assignmentRepository->gradeSubmission(
            submissionId: $submissionId,
            score: $score,
            teacherComment: $teacherComment,
            gradedByTeacherId: $teacherId
        );

        return ServiceResult::success($this->assignmentRepository->findSubmissionById($submissionId));
    }

    /**
     * Get all assignments created by a teacher.
     *
     * @return Assignment[]
     */
    public function getTeacherAssignments(UserContext $actor, ?int $sessionId = null): array
    {
        $teacher = $this->teacherRepository->findTeacherByUserId($actor->id);
        if (!$teacher) {
            return [];
        }

        return $this->assignmentRepository->findForTeacher($teacher->id, $sessionId);
    }

    /**
     * Get published assignments for a student's enrolled subjects.
     *
     * @return array<string, mixed>
     */
    public function getStudentAssignments(UserContext $actor, ?int $termId = null): array
    {
        $student = $this->studentRepository->findByUserId($actor->id);
        if (!$student) {
            return ['active' => [], 'past_due' => [], 'submissions' => []];
        }

        $session = $this->academicRepository->findActiveSession();
        if (!$session) {
            return ['active' => [], 'past_due' => [], 'submissions' => []];
        }

        $subjectEnrollments = $this->enrollmentRepository->getStudentSubjectEnrollments($student->id, $session->id);
        if (empty($subjectEnrollments)) {
            return ['active' => [], 'past_due' => [], 'submissions' => []];
        }

        $enrolledSubjectIds = array_map(fn($sse) => $sse->classSubjectId, $subjectEnrollments);
        $assignments = $this->assignmentRepository->findPublishedForMultipleClassSubjects($enrolledSubjectIds, $termId);

        $active = [];
        $pastDue = [];
        $submissions = [];

        foreach ($assignments as $assignment) {
            $sub = $this->assignmentRepository->findSubmissionByAssignmentAndStudent($assignment->id, $student->id);
            if ($sub) {
                $submissions[$assignment->id] = $sub;
            }

            if ($assignment->isPastDue()) {
                $pastDue[] = $assignment;
            } else {
                $active[] = $assignment;
            }
        }

        return [
            'active' => $active,
            'past_due' => $pastDue,
            'submissions' => $submissions,
        ];
    }

    /**
     * Get assignments for a parent's linked child.
     *
     * @return array<string, mixed>
     */
    public function getParentChildAssignments(int $studentId, UserContext $actor, ?int $termId = null): array
    {
        $parent = $this->parentRepository->findByUserId($actor->id);
        if (!$parent || !$this->parentRepository->isLinked($parent->id, $studentId)) {
            throw new AuthorizationException('You are not authorized to view coursework for this student.');
        }

        $student = $this->studentRepository->findById($studentId);
        if (!$student) {
            throw new ResourceNotFoundException('Student not found.');
        }

        $session = $this->academicRepository->findActiveSession();
        if (!$session) {
            return ['student' => $student, 'assignments' => [], 'submissions' => []];
        }

        $subjectEnrollments = $this->enrollmentRepository->getStudentSubjectEnrollments($student->id, $session->id);
        if (empty($subjectEnrollments)) {
            return ['student' => $student, 'assignments' => [], 'submissions' => []];
        }

        $enrolledSubjectIds = array_map(fn($sse) => $sse->classSubjectId, $subjectEnrollments);
        $assignments = $this->assignmentRepository->findPublishedForMultipleClassSubjects($enrolledSubjectIds, $termId);
        $submissions = [];

        foreach ($assignments as $assignment) {
            $sub = $this->assignmentRepository->findSubmissionByAssignmentAndStudent($assignment->id, $student->id);
            if ($sub) {
                $submissions[$assignment->id] = $sub;
            }
        }

        return [
            'student' => $student,
            'assignments' => $assignments,
            'submissions' => $submissions,
        ];
    }
}
