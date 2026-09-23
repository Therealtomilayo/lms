<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\DTO\ServiceResult;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;

/**
 * Application Service for User Account Management and RBAC Role Coordination
 */
class UserService
{
    private UserRepository $userRepository;
    private StudentRepository $studentRepository;
    private TeacherRepository $teacherRepository;
    private ParentRepository $parentRepository;
    private AcademicRepository $academicRepository;
    private EnrollmentService $enrollmentService;

    public function __construct(
        ?UserRepository $userRepository = null,
        ?StudentRepository $studentRepository = null,
        ?TeacherRepository $teacherRepository = null,
        ?ParentRepository $parentRepository = null,
        \App\Repositories\AcademicRepository|\PDO|null $academicRepository = null,
        ?EnrollmentService $enrollmentService = null
    ) {
        $this->userRepository = $userRepository ?? new UserRepository();
        $this->studentRepository = $studentRepository ?? new StudentRepository();
        $this->teacherRepository = $teacherRepository ?? new TeacherRepository();
        $this->parentRepository = $parentRepository ?? new ParentRepository();
        if ($academicRepository instanceof \PDO) {
            $this->academicRepository = new AcademicRepository($academicRepository);
        } else {
            $this->academicRepository = $academicRepository ?? new AcademicRepository();
        }
        $this->enrollmentService = $enrollmentService ?? new EnrollmentService();
    }

    /**
     * Create a new user account with role assignments.
     */
    public function createUser(array $data, UserContext $actor): ServiceResult
    {
        $name = trim($data['name'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $phone = !empty($data['phone']) ? trim($data['phone']) : null;
        $password = $data['password'] ?? '';
        $roles = $data['roles'] ?? [];
        $status = $data['status'] ?? 'active';

        $isStudentOnly = count($roles) === 1 && in_array('student', $roles, true);

        // Pre-resolve student admission number if needed for fallback email
        $admNo = !empty($data['admission_number']) 
            ? trim($data['admission_number']) 
            : ($isStudentOnly ? $this->studentRepository->generateAdmissionNumber() : '');

        // Fallback institutional email for students if left blank
        if ($isStudentOnly && $email === '') {
            $studentDomain = (string)\App\Core\Config::get('app.student_domain', 'claret.edu');
            $cleanAdm = strtolower(str_replace(['/', '-', ' '], '', $admNo));
            $email = "{$cleanAdm}.student@{$studentDomain}";
        }

        $errors = [];
        if ($name === '') {
            $errors['name'] = ['Full name is required.'];
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = ['A valid email address is required.'];
        }
        if (strlen($password) < 8) {
            $errors['password'] = ['Password must be at least 8 characters.'];
        }
        if (empty($roles)) {
            $errors['roles'] = ['At least one role must be assigned.'];
        } elseif (in_array('student', $roles, true) && count($roles) > 1) {
            $errors['roles'] = ['Student accounts cannot be combined with faculty, administrative, or parent roles.'];
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Enforce RBAC user policy
        if (!UserPolicy::canCreateUser($actor, $roles)) {
            throw new DomainRuleException('You do not have permission to assign the specified roles (e.g. Super Admin).');
        }

        // Email uniqueness
        if ($this->userRepository->findByEmail($email)) {
            throw new ValidationException(['email' => ['A user with this email address already exists.']]);
        }

        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );

        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $user = $this->userRepository->create([
            'uuid' => $uuid,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => $passwordHash,
            'status' => $status,
            'must_change_password' => !empty($data['must_change_password']) ? 1 : 0,
        ], $roles);

        // If 'student' role, create student profile and auto-enroll in class & subjects
        if (in_array('student', $roles, true)) {
            $studentAdm = !empty($admNo) ? $admNo : $this->studentRepository->generateAdmissionNumber();
            $classId = !empty($data['current_class_id']) ? (int)$data['current_class_id'] : null;

            $student = $this->studentRepository->create(
                userId: $user->id,
                admissionNumber: $studentAdm,
                dateOfBirth: $data['date_of_birth'] ?? null,
                gender: $data['gender'] ?? null,
                currentClassId: $classId,
                stateOfOrigin: $data['state_of_origin'] ?? null,
                lga: $data['lga'] ?? null,
                nationality: $data['nationality'] ?? 'Nigerian',
                religion: $data['religion'] ?? null,
                admissionDate: $data['admission_date'] ?? null
            );

            if ($classId !== null && $classId > 0) {
                $activeSession = $this->academicRepository->findActiveSession() ?? ($this->academicRepository->getAllSessions()[0] ?? null);
                if ($activeSession) {
                    $this->enrollmentService->enrollStudentInClass($student->id, $classId, $activeSession->id);
                }
            }
        }

        // If 'teacher' role, create teacher profile with unique staff ID
        if (in_array('teacher', $roles, true)) {
            $staffId = !empty($data['staff_id']) ? trim($data['staff_id']) : $this->teacherRepository->generateStaffId();
            $this->teacherRepository->createTeacher($user->id, $staffId);
        }

        // If 'parent' role, create parent profile
        if (in_array('parent', $roles, true)) {
            $this->parentRepository->create($user->id);
        }

        return ServiceResult::success($user);
    }

    /**
     * Update an existing user.
     */
    public function updateUser(int $userId, array $data, UserContext $actor): ServiceResult
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new ResourceNotFoundException("User #{$userId} not found.");
        }

        $roles = isset($data['roles']) ? (array)$data['roles'] : $user->roles;
        if (isset($data['roles'])) {
            if (empty($roles)) {
                throw new ValidationException(['roles' => ['At least one role must be assigned.']]);
            }
            if (in_array('student', $roles, true) && count($roles) > 1) {
                throw new ValidationException(['roles' => ['Student accounts cannot be combined with faculty, administrative, or parent roles.']]);
            }
        }

        if (!UserPolicy::canEditUser($actor, $user, $roles)) {
            throw new DomainRuleException('You do not have permission to modify this user or grant Super Admin privileges.');
        }

        $updateData = [];
        if (isset($data['name'])) {
            $name = trim($data['name']);
            if ($name === '') {
                throw new ValidationException(['name' => ['Name cannot be empty.']]);
            }
            $updateData['name'] = $name;
        }

        if (isset($data['email'])) {
            $email = strtolower(trim($data['email']));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new ValidationException(['email' => ['A valid email address is required.']]);
            }
            $existing = $this->userRepository->findByEmail($email);
            if ($existing && $existing->id !== $userId) {
                throw new ValidationException(['email' => ['Email is already in use by another account.']]);
            }
            $updateData['email'] = $email;
        }

        if (array_key_exists('phone', $data)) {
            $updateData['phone'] = !empty($data['phone']) ? trim($data['phone']) : null;
        }

        if (isset($data['status'])) {
            if ($data['status'] !== $user->status && !UserPolicy::canChangeUserStatus($actor, $user)) {
                throw new DomainRuleException('You cannot change the status of this user account.');
            }
            $updateData['status'] = $data['status'];
        }

        if (isset($data['must_change_password'])) {
            $updateData['must_change_password'] = (bool)$data['must_change_password'];
        }

        if (!empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                throw new ValidationException(['password' => ['Password must be at least 8 characters.']]);
            }
            $newHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            $this->userRepository->updatePassword($userId, $newHash, !empty($data['must_change_password']));
            $this->userRepository->revokeAllSessionsForUser($userId);
        }

        if (!empty($updateData)) {
            $this->userRepository->update($userId, $updateData);
        }

        if (isset($data['roles'])) {
            $this->userRepository->syncRoles($userId, $roles);
        }

        // Manage Teacher Profile
        if (in_array('teacher', $roles, true)) {
            $existingTeacher = $this->teacherRepository->findTeacherByUserId($userId);
            if (!$existingTeacher) {
                $staffId = !empty($data['staff_id']) ? trim($data['staff_id']) : $this->teacherRepository->generateStaffId();
                $this->teacherRepository->createTeacher($userId, $staffId);
            } elseif (!empty($data['staff_id']) && trim($data['staff_id']) !== $existingTeacher->staffId) {
                $this->teacherRepository->updateTeacherStaffId($existingTeacher->id, trim($data['staff_id']));
            }
        }

        // Manage Parent Profile
        if (in_array('parent', $roles, true) && !$this->parentRepository->findByUserId($userId)) {
            $this->parentRepository->create($userId);
        }

        // Manage Student Profile & Class Placements
        if (in_array('student', $roles, true)) {
            $existingStudent = $this->studentRepository->findByUserId($userId);
            $classId = isset($data['current_class_id']) && (int)$data['current_class_id'] > 0 ? (int)$data['current_class_id'] : null;

            if (!$existingStudent) {
                $admNo = !empty($data['admission_number']) ? trim($data['admission_number']) : $this->studentRepository->generateAdmissionNumber();
                $student = $this->studentRepository->create(
                    userId: $userId,
                    admissionNumber: $admNo,
                    dateOfBirth: $data['date_of_birth'] ?? null,
                    gender: $data['gender'] ?? null,
                    currentClassId: $classId,
                    stateOfOrigin: $data['state_of_origin'] ?? null,
                    lga: $data['lga'] ?? null,
                    nationality: $data['nationality'] ?? 'Nigerian',
                    religion: $data['religion'] ?? null,
                    admissionDate: $data['admission_date'] ?? null
                );

                if ($classId !== null && $classId > 0) {
                    $activeSession = $this->academicRepository->findActiveSession() ?? ($this->academicRepository->getAllSessions()[0] ?? null);
                    if ($activeSession) {
                        $this->enrollmentService->enrollStudentInClass($student->id, $classId, $activeSession->id);
                    }
                }
            } else {
                $admNo = !empty($data['admission_number']) ? trim($data['admission_number']) : $existingStudent->admissionNumber;
                $gender = $data['gender'] ?? $existingStudent->gender;
                $dob = $data['date_of_birth'] ?? $existingStudent->dateOfBirth;

                $this->studentRepository->update(
                    studentId: $existingStudent->id,
                    admissionNumber: $admNo,
                    dateOfBirth: $dob,
                    gender: $gender,
                    currentClassId: $classId,
                    stateOfOrigin: array_key_exists('state_of_origin', $data) ? $data['state_of_origin'] : $existingStudent->stateOfOrigin,
                    lga: array_key_exists('lga', $data) ? $data['lga'] : $existingStudent->lga,
                    nationality: array_key_exists('nationality', $data) ? $data['nationality'] : $existingStudent->nationality,
                    religion: array_key_exists('religion', $data) ? $data['religion'] : $existingStudent->religion,
                    admissionDate: array_key_exists('admission_date', $data) ? $data['admission_date'] : $existingStudent->admissionDate
                );

                if ($classId !== null && $classId > 0 && $classId !== (int)$existingStudent->currentClassId) {
                    $activeSession = $this->academicRepository->findActiveSession() ?? ($this->academicRepository->getAllSessions()[0] ?? null);
                    if ($activeSession) {
                        $this->enrollmentService->enrollStudentInClass($existingStudent->id, $classId, $activeSession->id);
                    }
                }
            }
        }

        return ServiceResult::success($this->userRepository->findById($userId));
    }

    /**
     * Toggle or update user account status.
     */
    public function updateUserStatus(int $userId, string $status, UserContext $actor): ServiceResult
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new ResourceNotFoundException("User #{$userId} not found.");
        }

        if (!UserPolicy::canChangeUserStatus($actor, $user)) {
            throw new DomainRuleException('You cannot change the status of this user account (self-deactivation or super admin protection).');
        }

        if (!in_array($status, ['active', 'inactive', 'suspended', 'locked'], true)) {
            throw new DomainRuleException("Invalid user status '{$status}'.");
        }

        $this->userRepository->updateStatus($userId, $status);

        if ($status !== 'active') {
            $this->userRepository->revokeAllSessionsForUser($userId);
        }

        return ServiceResult::success($this->userRepository->findById($userId));
    }

    /**
     * Issue an administrative password reset.
     */
    public function adminResetPassword(int $userId, string $newPassword, UserContext $actor): ServiceResult
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new ResourceNotFoundException("User #{$userId} not found.");
        }

        if (!UserPolicy::canResetUserPassword($actor, $user)) {
            throw new DomainRuleException('You cannot reset the password of this user account.');
        }

        if (strlen($newPassword) < 8) {
            throw new ValidationException(['password' => ['Password must be at least 8 characters.']]);
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->userRepository->updatePassword($userId, $newHash, true);
        $this->userRepository->revokeAllSessionsForUser($userId);

        return ServiceResult::success(['message' => 'Password reset successfully. Sessions revoked.']);
    }
}
