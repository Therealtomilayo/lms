<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\UserContext;
use App\Policies\UserPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\UserRepository;
use App\Services\UserService;

/**
 * Controller for Admin User Management
 */
class UserController extends Controller
{
    private UserService $userService;
    private UserRepository $userRepository;
    private AcademicRepository $academicRepository;
    private \App\Services\ApprovalService $approvalService;
    private \App\Repositories\StudentRepository $studentRepository;
    private \App\Repositories\TeacherRepository $teacherRepository;

    public function __construct(
        ?UserService $userService = null,
        ?UserRepository $userRepository = null,
        ?AcademicRepository $academicRepository = null,
        ?\App\Services\ApprovalService $approvalService = null,
        ?\App\Repositories\StudentRepository $studentRepository = null,
        ?\App\Repositories\TeacherRepository $teacherRepository = null
    ) {
        $this->userService = $userService ?? new UserService();
        $this->userRepository = $userRepository ?? new UserRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->approvalService = $approvalService ?? new \App\Services\ApprovalService();
        $this->studentRepository = $studentRepository ?? new \App\Repositories\StudentRepository();
        $this->teacherRepository = $teacherRepository ?? new \App\Repositories\TeacherRepository();
    }

    public function index(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canListUsers($userContext)) {
            return $this->forbidden('You are not authorized to access user management.');
        }

        $role = $request->query('role');
        $status = $request->query('status');
        $search = $request->query('q');
        $page = max(1, (int)$request->query('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $users = $this->userRepository->getAllUsers($limit, $offset, $role ?: null, $status ?: null, $search ?: null);
        $totalUsers = $this->userRepository->countUsers($role ?: null, $status ?: null, $search ?: null);
        $totalPages = (int)ceil($totalUsers / $limit);

        return $this->view('admin/users/index', [
            'title' => 'User Management — Claret LMS',
            'headerTitle' => 'User Management',
            'users' => $users,
            'totalUsers' => $totalUsers,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'selectedRole' => $role,
            'selectedStatus' => $status,
            'search' => $search,
            'actor' => $userContext,
        ]);
    }

    public function create(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canListUsers($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $classes = $this->academicRepository->getAllClasses();
        $suggestedAdmissionNumber = $this->studentRepository->generateAdmissionNumber();
        $suggestedStaffId = $this->teacherRepository->generateStaffId();

        return $this->view('admin/users/create', [
            'title' => 'Create User — Claret LMS',
            'headerTitle' => 'Create New User Account',
            'classes' => $classes,
            'suggestedAdmissionNumber' => $suggestedAdmissionNumber,
            'suggestedStaffId' => $suggestedStaffId,
            'actor' => $userContext,
            'errors' => Session::getFlash('errors', []),
        ]);
    }

    public function store(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canListUsers($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $roles = (array)($request->post('roles', []));
        $name = trim((string)$request->post('name', ''));
        $gender = trim((string)$request->post('gender', ''));
        $password = (string)$request->post('password', '');
        $email = trim((string)$request->post('email', ''));
        $classId = $request->post('current_class_id');

        $validationErrors = [];
        if ($name === '') {
            $validationErrors['name'] = ['Full name is required.'];
        }
        if (empty($roles)) {
            $validationErrors['roles'] = ['At least one user role must be selected.'];
        }
        if ($password === '') {
            $validationErrors['password'] = ['Initial password is required.'];
        } elseif (strlen($password) < 8) {
            $validationErrors['password'] = ['Password must be at least 8 characters.'];
        }

        if (!empty($validationErrors)) {
            return $this->redirectWithErrors('/admin/users/create', $validationErrors, $request->all());
        }

        try {
            $isSuperAdmin = $userContext->hasRole('super_admin');
            $requestedAdminRole = in_array('admin', $roles, true);
            $stageAdminRole = !$isSuperAdmin && $requestedAdminRole;

            // If a non-super-admin requested admin role, strip it from initial creation
            $assigningRoles = $roles;
            if ($stageAdminRole) {
                $assigningRoles = array_values(array_diff($roles, ['admin']));
                if (empty($assigningRoles)) {
                    $assigningRoles = ['student'];
                }
            }

            // Normal user creation is active immediately
            $status = $request->post('status', 'active');
            $avatarUrl = $this->handlePassportUpload($request);

            $result = $this->userService->createUser([
                'name' => $request->post('name'),
                'email' => $request->post('email'),
                'phone' => $request->post('phone'),
                'avatar_url' => $avatarUrl,
                'password' => $request->post('password'),
                'roles' => $assigningRoles,
                'status' => $status,
                'must_change_password' => $request->post('must_change_password', 1),
                'admission_number' => $request->post('admission_number'),
                'staff_id' => $request->post('staff_id'),
                'date_of_birth' => $request->post('date_of_birth'),
                'gender' => $request->post('gender'),
                'current_class_id' => $request->post('current_class_id'),
                'state_of_origin' => $request->post('state_of_origin'),
                'lga' => $request->post('lga'),
                'nationality' => $request->post('nationality') ?: 'Nigerian',
                'religion' => $request->post('religion'),
                'admission_date' => $request->post('admission_date'),
                'use_school_bus' => !empty($request->post('use_school_bus')),
            ], $userContext);

            $createdUser = $result->data;

            if ($stageAdminRole && $createdUser) {
                $this->approvalService->stageAdminRoleAssignment(
                    $createdUser->id,
                    $userContext->getUserId(),
                    'Admin role requested during user creation by ' . $userContext->name,
                    [
                        'name' => $createdUser->name,
                        'email' => $createdUser->email,
                    ]
                );

                return $this->redirectWithSuccess(
                    '/admin/users',
                    "Account for {$createdUser->name} created. Request to grant Admin role submitted to Super Admin for approval."
                );
            }

            return $this->redirectWithSuccess('/admin/users', 'User account created successfully.');
        } catch (ValidationException $e) {
            return $this->redirectWithErrors('/admin/users/create', $e->getErrors(), $request->all());
        } catch (DomainRuleException $e) {
            return $this->redirectWithErrors('/admin/users/create', ['general' => [$e->getMessage()]], $request->all());
        }
    }

    public function edit(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canListUsers($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $userId = (int)$id;
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            return Response::html('User not found', 404);
        }

        if (!UserPolicy::canEditUser($userContext, $user)) {
            return $this->forbidden('You do not have permission to edit this user.');
        }

        $student = $this->studentRepository->findByUserId($userId);
        $teacher = $this->teacherRepository->findTeacherByUserId($userId);
        $suggestedAdmissionNumber = $student ? $student->admissionNumber : $this->studentRepository->generateAdmissionNumber();
        $suggestedStaffId = $teacher ? $teacher->staffId : $this->teacherRepository->generateStaffId();
        $classes = $this->academicRepository->getAllClasses();

        return $this->view('admin/users/edit', [
            'title' => "Edit {$user->name} — Claret LMS",
            'headerTitle' => "Edit User: {$user->name}",
            'user' => $user,
            'student' => $student,
            'teacher' => $teacher,
            'suggestedAdmissionNumber' => $suggestedAdmissionNumber,
            'suggestedStaffId' => $suggestedStaffId,
            'classes' => $classes,
            'actor' => $userContext,
            'errors' => Session::getFlash('errors', []),
        ]);
    }

    public function update(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canListUsers($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $userId = (int)$id;
        $targetUser = $this->userRepository->findById($userId);
        if (!$targetUser) {
            return Response::html('User not found', 404);
        }

        $roles = $request->post('roles') !== null ? (array)$request->post('roles') : null;

        try {
            $isSuperAdmin = $userContext->hasRole('super_admin');
            $stageAdminRole = false;

            if ($roles !== null && in_array('admin', $roles, true) && !$isSuperAdmin && !$targetUser->hasRole('admin')) {
                $stageAdminRole = true;
                $roles = array_values(array_diff($roles, ['admin']));
                if (empty($roles)) {
                    $roles = $targetUser->roles;
                }
            }

            $data = [
                'name' => $request->post('name'),
                'email' => $request->post('email'),
                'phone' => $request->post('phone'),
                'status' => $request->post('status'),
                'gender' => $request->post('gender'),
                'current_class_id' => $request->post('current_class_id'),
                'admission_number' => $request->post('admission_number'),
                'staff_id' => $request->post('staff_id'),
                'date_of_birth' => $request->post('date_of_birth'),
                'state_of_origin' => $request->post('state_of_origin'),
                'lga' => $request->post('lga'),
                'nationality' => $request->post('nationality') ?: 'Nigerian',
                'religion' => $request->post('religion'),
                'admission_date' => $request->post('admission_date'),
                'use_school_bus' => !empty($request->post('use_school_bus')),
            ];

            if ($roles !== null) {
                $data['roles'] = $roles;
            }

            if (!empty($request->post('password'))) {
                $data['password'] = $request->post('password');
            }

            $avatarUrl = $this->handlePassportUpload($request);
            if ($avatarUrl !== null) {
                $data['avatar_url'] = $avatarUrl;
            }

            $this->userService->updateUser($userId, $data, $userContext);

            if ($stageAdminRole) {
                $this->approvalService->stageAdminRoleAssignment(
                    $userId,
                    $userContext->getUserId(),
                    'Admin role elevation requested by ' . $userContext->name,
                    [
                        'name' => $targetUser->name,
                        'email' => $targetUser->email,
                    ]
                );

                return $this->redirectWithSuccess(
                    '/admin/users',
                    "User updated. Request to grant Admin role submitted to Super Admin for approval."
                );
            }

            return $this->redirectWithSuccess('/admin/users', 'User updated successfully.');
        } catch (ValidationException $e) {
            return $this->redirectWithErrors("/admin/users/{$userId}/edit", $e->getErrors(), $request->all());
        } catch (DomainRuleException $e) {
            return $this->redirectWithErrors("/admin/users/{$userId}/edit", ['general' => [$e->getMessage()]], $request->all());
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        }
    }

    public function delete(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canListUsers($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $userId = (int)($id ?: $request->post('id', 0));
        $targetUser = $this->userRepository->findById($userId);
        if (!$targetUser) {
            return $this->redirectWithError('/admin/users', 'User not found.');
        }

        if (!UserPolicy::canDeleteUser($userContext, $targetUser)) {
            return $this->redirectWithError('/admin/users', 'You cannot delete this user account.');
        }

        if ($userContext->hasRole('super_admin')) {
            try {
                $this->userService->updateUserStatus($userId, 'inactive', $userContext);
                return $this->redirectWithSuccess('/admin/users', "User account for {$targetUser->name} deleted successfully.");
            } catch (\Throwable $e) {
                return $this->redirectWithError('/admin/users', $e->getMessage());
            }
        }

        // Standard Admin: Stage deletion request for Super Admin review
        $reason = trim((string)$request->post('reason', ''));
        if ($reason === '') {
            return $this->redirectWithError('/admin/users', 'A justification reason is required to submit a user deletion request.');
        }

        $this->approvalService->stageUserDeletion(
            $userId,
            $userContext->getUserId(),
            $reason,
            [
                'name' => $targetUser->name,
                'email' => $targetUser->email,
                'roles' => $targetUser->roles,
            ]
        );

        return $this->redirectWithSuccess(
            '/admin/users',
            "Deletion request for {$targetUser->name} submitted to Super Admin approval queue."
        );
    }

    public function status(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canListUsers($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $userId = (int)($id ?: $request->post('id', 0));
        $status = (string)$request->post('status', 'active');

        try {
            $this->userService->updateUserStatus($userId, $status, $userContext);
            return $this->redirectWithSuccess('/admin/users', 'User status updated.');
        } catch (DomainRuleException $e) {
            return $this->redirectWithError('/admin/users', $e->getMessage());
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        }
    }

    public function resetPassword(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canListUsers($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $userId = (int)($id ?: $request->post('id', 0));
        $password = trim((string)$request->post('password', ''));

        if ($password === '') {
            return $this->redirectWithErrors("/admin/users/{$userId}/edit", ['password' => ['New temporary password is required.']]);
        }
        if (strlen($password) < 8) {
            return $this->redirectWithErrors("/admin/users/{$userId}/edit", ['password' => ['Password must be at least 8 characters.']]);
        }

        try {
            $this->userService->adminResetPassword($userId, $password, $userContext);
            return $this->redirectWithSuccess("/admin/users/{$userId}/edit", 'Password reset successfully. Active sessions revoked.');
        } catch (ValidationException $e) {
            return $this->redirectWithErrors("/admin/users/{$userId}/edit", $e->getErrors());
        } catch (DomainRuleException $e) {
            return $this->redirectWithErrors("/admin/users/{$userId}/edit", ['password' => [$e->getMessage()]]);
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        }
    }

    /**
     * Process uploaded passport photograph file into public storage.
     */
    private function handlePassportUpload(Request $request): ?string
    {
        $file = $request->file('passport');
        if (!$file || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        $tmpName = (string)($file['tmp_name'] ?? '');
        if ($tmpName === '' || !file_exists($tmpName)) {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpName);
        if (!in_array($mime, $allowedMimes, true)) {
            throw new ValidationException(['passport' => ['Passport must be a valid JPG, PNG, or WEBP image.']]);
        }

        if (($file['size'] ?? 0) > 5242880) { // 5MB limit
            throw new ValidationException(['passport' => ['Passport image size cannot exceed 5MB.']]);
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $targetDir = dirname(__DIR__, 3) . '/public/assets/uploads/passports';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $filename = 'passport_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
        $destPath = $targetDir . '/' . $filename;

        if (!move_uploaded_file($tmpName, $destPath)) {
            throw new DomainRuleException('Failed to save uploaded passport photo.');
        }

        return '/assets/uploads/passports/' . $filename;
    }
}
