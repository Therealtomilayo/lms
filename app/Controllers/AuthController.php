<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;

/**
 * Authentication and Password Management Controller
 */
class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct(?AuthService $authService = null)
    {
        parent::__construct();
        $this->authService = $authService ?? new AuthService();
    }

    public function showLogin(Request $request): Response
    {
        if ($this->authenticator->check($request)) {
            $user = $this->user($request);
            return $this->redirect($this->authService->resolveDashboardUrl($user->roles));
        }

        return $this->view('auth/login', [
            'title' => 'Sign In - Claret LMS',
            'errors' => Session::getFlash('errors', []),
        ]);
    }

    public function login(Request $request): Response
    {
        try {
            $validated = $this->validate($request, [
                'email' => 'required|email',
                'password' => 'required',
            ]);
        } catch (ValidationException $e) {
            return $this->redirectWithErrors('/login', $e->getErrors(), $request->all());
        }

        $result = $this->authService->login(
            email: $validated['email'],
            password: $validated['password'],
            ipAddress: $request->clientIp(),
            userAgent: $request->userAgent()
        );

        if ($result->isFailure()) {
            return $this->redirectWithErrors('/login', $result->errors, ['email' => $validated['email']]);
        }

        // Check if there was an intended URL prior to login
        $intended = Session::get('_intended_url');
        if ($intended) {
            Session::remove('_intended_url');
            return $this->redirect($intended);
        }

        return $this->redirect($result->data['redirect']);
    }

    public function logout(Request $request): Response
    {
        $this->authService->logout();
        return $this->redirectWithSuccess('/login', 'You have been successfully logged out.');
    }

    public function showForgotPassword(Request $request): Response
    {
        return $this->view('auth/forgot_password', [
            'title' => 'Forgot Password - Claret LMS',
            'errors' => Session::getFlash('errors', []),
        ]);
    }

    public function forgotPassword(Request $request): Response
    {
        try {
            $validated = $this->validate($request, [
                'email' => 'required|email',
            ]);
        } catch (ValidationException $e) {
            return $this->redirectWithErrors('/forgot-password', $e->getErrors(), $request->all());
        }

        $result = $this->authService->requestPasswordReset(
            email: $validated['email'],
            ipAddress: $request->clientIp()
        );

        return $this->redirectWithSuccess('/forgot-password', $result->data['message']);
    }

    public function showResetPassword(Request $request, string $token): Response
    {
        $validation = $this->authService->validatePasswordResetToken($token);

        if ($validation->isFailure()) {
            return $this->redirectWithErrors('/forgot-password', $validation->errors);
        }

        return $this->view('auth/reset_password', [
            'title' => 'Set New Password - Claret LMS',
            'token' => $token,
            'email' => $validation->data['email'],
            'errors' => Session::getFlash('errors', []),
        ]);
    }

    public function resetPassword(Request $request): Response
    {
        try {
            $validated = $this->validate($request, [
                'token' => 'required',
                'password' => 'required|min:8|confirmed',
            ]);
        } catch (ValidationException $e) {
            $token = (string)$request->input('token', '');
            return $this->redirectWithErrors('/reset-password/' . urlencode($token), $e->getErrors());
        }

        $result = $this->authService->resetPassword(
            plainToken: $validated['token'],
            newPassword: $validated['password']
        );

        if ($result->isFailure()) {
            return $this->redirectWithErrors('/reset-password/' . urlencode($validated['token']), $result->errors);
        }

        return $this->redirectWithSuccess('/login', $result->data['message']);
    }

    public function showChangePassword(Request $request): Response
    {
        $user = $this->user($request);
        if (!$user) {
            return $this->redirect('/login');
        }

        return $this->view('auth/change_password', [
            'title' => 'Change Password - Claret LMS',
            'user' => $user,
            'isForced' => $user->mustChangePassword,
            'errors' => Session::getFlash('errors', []),
        ]);
    }

    public function changePassword(Request $request): Response
    {
        $user = $this->user($request);
        if (!$user) {
            return $this->redirect('/login');
        }

        try {
            $validated = $this->validate($request, [
                'current_password' => 'required',
                'password' => 'required|min:8|confirmed',
            ]);
        } catch (ValidationException $e) {
            return $this->redirectWithErrors('/profile/password', $e->getErrors());
        }

        $result = $this->authService->changePassword(
            userId: $user->id,
            currentPassword: $validated['current_password'],
            newPassword: $validated['password']
        );

        if ($result->isFailure()) {
            return $this->redirectWithErrors('/profile/password', $result->errors);
        }

        // Re-authenticate session status with mustChangePassword = false
        $dashboardUrl = $this->authService->resolveDashboardUrl($user->roles);
        return $this->redirectWithSuccess($dashboardUrl, 'Your password was changed successfully.');
    }
}
