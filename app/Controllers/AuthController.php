<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
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
        $returnUrl = (string)($request->input('return_url') ?? '');
        $isTrustedReturn = !empty($returnUrl) && $this->isTrustedDomain($returnUrl);

        try {
            $validated = $this->validate($request, [
                'email' => 'required',
                'password' => 'required',
            ]);
        } catch (ValidationException $e) {
            if ($isTrustedReturn) {
                return $this->redirect($this->appendQueryParam($returnUrl, 'error', 'Email or admission number and password are required.'));
            }
            return $this->redirectWithErrors('/login', $e->getErrors(), $request->all());
        }

        $result = $this->authService->login(
            email: $validated['email'],
            password: $validated['password'],
            ipAddress: $request->clientIp(),
            userAgent: $request->userAgent()
        );

        if ($result->isFailure()) {
            $errorMsg = $result->errors['general'][0] ?? 'Invalid email, admission number, or password.';
            if ($isTrustedReturn) {
                $redirectUrl = $this->appendQueryParam($returnUrl, 'error', $errorMsg);
                $redirectUrl = $this->appendQueryParam($redirectUrl, 'email', $validated['email']);
                return $this->redirect($redirectUrl);
            }
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

    /**
     * Cross-domain external login endpoint (e.g. for claretschools.xo.je/portal.php)
     * Issues a secure, single-use SSO ticket to navigate first-party cookies safely.
     */
    public function externalLogin(Request $request): Response
    {
        $origin = $request->header('Origin') ?? $request->header('Referer') ?? '*';
        $corsHeaders = [
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
        ];

        if ($request->getMethod() === 'OPTIONS') {
            return new Response('', 204, $corsHeaders);
        }

        // Support both JSON body and standard POST form fields
        $rawEmail = $request->input('email') ?? $request->getBodyParam('email') ?? '';
        $rawPassword = $request->input('password') ?? $request->getBodyParam('password') ?? '';

        $email = trim((string)$rawEmail);
        $password = (string)$rawPassword;

        if ($email === '' || $password === '') {
            return Response::json([
                'success' => false,
                'message' => 'Email address or admission number and password are required.',
            ], 422, $corsHeaders);
        }

        $result = $this->authService->createSsoTicket($email, $password);

        if ($result->isFailure()) {
            $msg = $result->errors['general'][0] ?? 'Invalid email, admission number, or password.';
            return Response::json([
                'success' => false,
                'message' => $msg,
            ], 401, $corsHeaders);
        }

        $data = $result->getData();
        $ticket = $data['ticket'];
        $redirectTarget = $data['redirect'];

        $appUrl = (string)Config::get('app.url', 'https://portal-claretschools.xo.je');
        $ssoUrl = rtrim($appUrl, '/') . '/auth/sso?ticket=' . urlencode($ticket) . '&target=' . urlencode($redirectTarget);

        return Response::json([
            'success' => true,
            'message' => 'Authenticated successfully. Redirecting to your dashboard...',
            'redirect_url' => $ssoUrl,
            'ticket' => $ticket,
            'redirect' => $redirectTarget,
            'user' => [
                'name' => $data['user']->name,
                'email' => $data['user']->email,
            ],
        ], 200, $corsHeaders);
    }

    /**
     * Consume an SSO ticket issued by external login, establish first-party session, and redirect.
     */
    public function consumeSsoTicket(Request $request): Response
    {
        $ticket = (string)($request->input('ticket') ?? $request->get('ticket') ?? '');
        $target = (string)($request->input('target') ?? $request->get('target') ?? '');

        if (empty($ticket)) {
            return $this->redirectWithErrors('/login', ['ticket' => ['Authentication ticket missing.']]);
        }

        $result = $this->authService->consumeSsoTicket(
            ticket: $ticket,
            ipAddress: $request->clientIp(),
            userAgent: $request->userAgent()
        );

        if ($result->isFailure()) {
            $err = $result->errors['ticket'][0] ?? $result->errors['general'][0] ?? 'Login ticket has expired. Please sign in again.';
            return $this->redirectWithErrors('/login', ['general' => [$err]]);
        }

        $redirectUrl = !empty($target) ? $target : $result->data['redirect'];

        return $this->redirect($redirectUrl);
    }

    private function isTrustedDomain(string $url): bool
    {
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        if (empty($host)) {
            return false;
        }

        $trusted = [
            'claretschools.xo.je',
            'www.claretschools.xo.je',
            'portal-claretschools.xo.je',
            'lms.test',
            'localhost',
            '127.0.0.1',
        ];

        foreach ($trusted as $t) {
            if ($host === $t || str_ends_with($host, '.' . $t)) {
                return true;
            }
        }

        return false;
    }

    private function appendQueryParam(string $url, string $key, string $value): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . urlencode($key) . '=' . urlencode($value);
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
