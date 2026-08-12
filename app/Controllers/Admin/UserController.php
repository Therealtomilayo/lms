<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
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

    public function __construct(
        ?UserService $userService = null,
        ?UserRepository $userRepository = null,
        ?AcademicRepository $academicRepository = null
    ) {
        $this->userService = $userService ?? new UserService();
        $this->userRepository = $userRepository ?? new UserRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
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

        return $this->view('admin/users/create', [
            'title' => 'Create User — Claret LMS',
            'headerTitle' => 'Create New User Account',
            'classes' => $classes,
            'actor' => $userContext,
        ]);
    }

    public function store(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canListUsers($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $roles = (array)($request->post('roles', []));

        try {
            $this->userService->createUser([
                'name' => $request->post('name'),
                'email' => $request->post('email'),
                'phone' => $request->post('phone'),
                'password' => $request->post('password'),
                'roles' => $roles,
                'status' => $request->post('status', 'active'),
                'must_change_password' => $request->post('must_change_password', 1),
                'admission_number' => $request->post('admission_number'),
                'staff_id' => $request->post('staff_id'),
                'date_of_birth' => $request->post('date_of_birth'),
                'gender' => $request->post('gender'),
                'current_class_id' => $request->post('current_class_id'),
            ], $userContext);

            return $this->redirectWithSuccess('/admin/users', 'User account created successfully.');
        } catch (ValidationException|DomainRuleException $e) {
            return $this->redirectWithError('/admin/users/create', $e->getMessage());
        }
    }

    public function edit(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canListUsers($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $user = $this->userRepository->findById((int)$id);
        if (!$user) {
            return Response::html('User not found', 404);
        }

        return $this->view('admin/users/edit', [
            'title' => "Edit {$user->name} — Claret LMS",
            'headerTitle' => "Edit User: {$user->name}",
            'user' => $user,
            'actor' => $userContext,
        ]);
    }

    public function update(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !UserPolicy::canListUsers($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $userId = (int)$id;
        $roles = $request->post('roles') !== null ? (array)$request->post('roles') : null;

        try {
            $data = [
                'name' => $request->post('name'),
                'email' => $request->post('email'),
                'phone' => $request->post('phone'),
                'status' => $request->post('status'),
            ];

            if ($roles !== null) {
                $data['roles'] = $roles;
            }

            if (!empty($request->post('password'))) {
                $data['password'] = $request->post('password');
            }

            $this->userService->updateUser($userId, $data, $userContext);

            return $this->redirectWithSuccess('/admin/users', 'User updated successfully.');
        } catch (ValidationException|DomainRuleException $e) {
            return $this->redirectWithError("/admin/users/{$userId}/edit", $e->getMessage());
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        }
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
        $password = (string)$request->post('password', 'Password123!');

        try {
            $this->userService->adminResetPassword($userId, $password, $userContext);
            return $this->redirectWithSuccess('/admin/users', 'Password reset successfully. Sessions revoked.');
        } catch (DomainRuleException|ValidationException $e) {
            return $this->redirectWithError('/admin/users', $e->getMessage());
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        }
    }
}
