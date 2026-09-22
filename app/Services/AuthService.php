<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Session;
use App\DTO\ServiceResult;
use App\Models\User;
use App\Repositories\UserRepository;
use Throwable;

/**
 * Business Workflows for Authentication, Multi-Role Resolution, and Password Management
 */
class AuthService
{
    private UserRepository $userRepository;

    public function __construct(?UserRepository $userRepository = null)
    {
        $this->userRepository = $userRepository ?? new UserRepository();
    }

    public function login(string $email, string $password, ?string $ipAddress = null, ?string $userAgent = null): ServiceResult
    {
        $email = trim($email);

        if ($email === '' || $password === '') {
            return ServiceResult::failure(['general' => ['Email or admission number and password are required.']], 'INVALID_CREDENTIALS');
        }

        $user = $this->userRepository->findByEmailOrAdmissionNumber($email);
        if (!$user) {
            return ServiceResult::failure(['general' => ['Invalid email, admission number, or password.']], 'INVALID_CREDENTIALS');
        }

        if (!$user->isActive()) {
            if ($user->isSuspended()) {
                return ServiceResult::failure(['general' => ['Your account has been suspended. Please contact the administrator.']], 'ACCOUNT_SUSPENDED');
            }
            return ServiceResult::failure(['general' => ['Your account is inactive. Please contact the administrator.']], 'ACCOUNT_INACTIVE');
        }

        if (!password_verify($password, $user->passwordHash)) {
            return ServiceResult::failure(['general' => ['Invalid email or password.']], 'INVALID_CREDENTIALS');
        }

        $redirectUrl = $this->establishUserSession($user, $ipAddress, $userAgent);

        return ServiceResult::success([
            'user' => $user,
            'redirect' => $redirectUrl,
            'must_change_password' => $user->mustChangePassword,
            'roles' => $user->roles,
        ]);
    }

    /**
     * Establish an authenticated session in session store and database
     */
    public function establishUserSession(User $user, ?string $ipAddress = null, ?string $userAgent = null): string
    {
        Session::start();
        Session::regenerate();

        $sessionLifetime = (int)Config::get('session.lifetime', 7200);
        $expiresAt = date('Y-m-d H:i:s', time() + $sessionLifetime);
        $rawSessionToken = bin2hex(random_bytes(32));
        $sessionHash = hash('sha256', $rawSessionToken);

        $userAgentHash = $userAgent ? hash('sha256', $userAgent) : null;
        $ipHash = $ipAddress ? hash('sha256', $ipAddress) : null;

        $this->userRepository->createSession(
            userId: $user->id,
            sessionHash: $sessionHash,
            expiresAt: $expiresAt,
            userAgentHash: $userAgentHash,
            ipHash: $ipHash
        );

        Session::set('user_id', $user->id);
        Session::set('session_hash', $sessionHash);
        Session::set('user_name', $user->name);
        Session::set('user_email', $user->email);
        Session::set('user_roles', $user->roles);

        return $user->mustChangePassword
            ? '/profile/password'
            : $this->resolveDashboardUrl($user->roles);
    }

    /**
     * Verify credentials and issue a short-lived one-time SSO login ticket
     */
    public function createSsoTicket(string $email, string $password): ServiceResult
    {
        $email = trim($email);
        if ($email === '' || $password === '') {
            return ServiceResult::failure(['general' => ['Email or admission number and password are required.']], 'INVALID_CREDENTIALS');
        }

        $user = $this->userRepository->findByEmailOrAdmissionNumber($email);
        if (!$user) {
            return ServiceResult::failure(['general' => ['Invalid email, admission number, or password.']], 'INVALID_CREDENTIALS');
        }

        if (!$user->isActive()) {
            if ($user->isSuspended()) {
                return ServiceResult::failure(['general' => ['Your account has been suspended. Please contact the administrator.']], 'ACCOUNT_SUSPENDED');
            }
            return ServiceResult::failure(['general' => ['Your account is inactive. Please contact the administrator.']], 'ACCOUNT_INACTIVE');
        }

        if (!password_verify($password, $user->passwordHash)) {
            return ServiceResult::failure(['general' => ['Invalid email, admission number, or password.']], 'INVALID_CREDENTIALS');
        }

        $ticket = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $ticket);

        $created = $this->userRepository->createSsoTicket($user->id, $tokenHash, 120);
        if (!$created) {
            return ServiceResult::failure(['general' => ['Unable to generate authentication ticket. Please try again.']], 'TICKET_CREATION_FAILED');
        }

        $targetUrl = $user->mustChangePassword
            ? '/profile/password'
            : $this->resolveDashboardUrl($user->roles);

        return ServiceResult::success([
            'ticket' => $ticket,
            'user' => $user,
            'redirect' => $targetUrl,
        ]);
    }

    /**
     * Consume a one-time SSO login ticket and establish authenticated session
     */
    public function consumeSsoTicket(string $ticket, ?string $ipAddress = null, ?string $userAgent = null): ServiceResult
    {
        $trimmedTicket = trim($ticket);
        if ($trimmedTicket === '') {
            return ServiceResult::failure(['ticket' => ['Authentication ticket missing or invalid.']], 'INVALID_TICKET');
        }

        $tokenHash = hash('sha256', $trimmedTicket);
        $userId = $this->userRepository->consumeSsoTicket($tokenHash);

        if (!$userId) {
            return ServiceResult::failure(['ticket' => ['Login ticket has expired or already been used. Please log in again.']], 'EXPIRED_TICKET');
        }

        $user = $this->userRepository->findById($userId);
        if (!$user || !$user->isActive()) {
            return ServiceResult::failure(['general' => ['User account is invalid or suspended.']], 'ACCOUNT_INVALID');
        }

        $redirectUrl = $this->establishUserSession($user, $ipAddress, $userAgent);

        return ServiceResult::success([
            'user' => $user,
            'redirect' => $redirectUrl,
            'must_change_password' => $user->mustChangePassword,
            'roles' => $user->roles,
        ]);
    }

    public function logout(?string $sessionHash = null): ServiceResult
    {
        Session::start();
        $hash = $sessionHash ?? (string)Session::get('session_hash', '');

        if ($hash !== '') {
            $this->userRepository->revokeSession($hash);
        }

        Session::destroy();

        return ServiceResult::success(['message' => 'Logged out successfully.']);
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): ServiceResult
    {
        if (mb_strlen($newPassword) < 8) {
            return ServiceResult::failure(['password' => ['New password must be at least 8 characters.']], 'VALIDATION_FAILED');
        }

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            return ServiceResult::failure(['general' => ['User account not found.']], 'USER_NOT_FOUND');
        }

        // Only check current password if user is not in forced-change status or current password is provided
        if (!password_verify($currentPassword, $user->passwordHash)) {
            return ServiceResult::failure(['current_password' => ['Current password is incorrect.']], 'INVALID_CURRENT_PASSWORD');
        }

        if (password_verify($newPassword, $user->passwordHash)) {
            return ServiceResult::failure(['password' => ['New password cannot be the same as the current password.']], 'PASSWORD_REUSED');
        }

        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        return Database::transaction(function () use ($userId, $newPasswordHash) {
            $this->userRepository->updatePassword($userId, $newPasswordHash, mustChangePassword: false);
            return ServiceResult::success(['message' => 'Password updated successfully.']);
        });
    }

    public function requestPasswordReset(string $email, ?string $ipAddress = null): ServiceResult
    {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ServiceResult::failure(['email' => ['Please enter a valid email address.']], 'VALIDATION_FAILED');
        }

        $user = $this->userRepository->findByEmail($email);
        $plainToken = null;

        if ($user && $user->isActive()) {
            $plainToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $plainToken);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour validity

            $this->userRepository->createPasswordResetToken(
                userId: $user->id,
                tokenHash: $tokenHash,
                expiresAt: $expiresAt,
                requestedIp: $ipAddress
            );

            // Dispatch external password reset email
            try {
                $notificationService = new NotificationService();
                $notificationService->sendPasswordResetEmail(
                    email: $user->email,
                    name: $user->name,
                    resetToken: $plainToken,
                    userId: $user->id
                );
            } catch (Throwable $e) {
                error_log("Failed to dispatch password reset email: " . $e->getMessage());
            }
        }

        // Generic response to avoid email enumeration
        return ServiceResult::success([
            'message' => 'If an account exists with this email, password reset instructions have been sent.',
            'token' => $plainToken, // Useful for testing or local development logging
        ]);
    }

    public function validatePasswordResetToken(string $plainToken): ServiceResult
    {
        if (trim($plainToken) === '') {
            return ServiceResult::failure(['token' => ['Invalid password reset token.']], 'INVALID_TOKEN');
        }

        $tokenHash = hash('sha256', $plainToken);
        $record = $this->userRepository->findValidPasswordResetToken($tokenHash);

        if (!$record) {
            return ServiceResult::failure(['token' => ['This password reset link is invalid or has expired.']], 'INVALID_TOKEN');
        }

        return ServiceResult::success([
            'token_id' => (int)$record['id'],
            'user_id' => (int)$record['user_id'],
            'email' => (string)$record['email'],
            'name' => (string)$record['name'],
        ]);
    }

    public function resetPassword(string $plainToken, string $newPassword): ServiceResult
    {
        if (mb_strlen($newPassword) < 8) {
            return ServiceResult::failure(['password' => ['Password must be at least 8 characters.']], 'VALIDATION_FAILED');
        }

        $validation = $this->validatePasswordResetToken($plainToken);
        if ($validation->isFailure()) {
            return $validation;
        }

        $tokenData = $validation->data;
        $userId = (int)$tokenData['user_id'];
        $tokenId = (int)$tokenData['token_id'];

        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        return Database::transaction(function () use ($userId, $tokenId, $newPasswordHash) {
            $this->userRepository->updatePassword($userId, $newPasswordHash, mustChangePassword: false);
            $this->userRepository->markPasswordResetTokenUsed($tokenId);
            $this->userRepository->revokeAllSessionsForUser($userId);

            return ServiceResult::success(['message' => 'Your password has been reset successfully. Please log in with your new password.']);
        });
    }

    public function resolveDashboardUrl(array $roles): string
    {
        if (in_array('super_admin', $roles, true) || in_array('admin', $roles, true)) {
            return '/admin/dashboard';
        }
        if (in_array('teacher', $roles, true)) {
            return '/teacher/dashboard';
        }
        if (in_array('student', $roles, true)) {
            return '/student/dashboard';
        }
        if (in_array('parent', $roles, true)) {
            return '/parent/dashboard';
        }
        if (in_array('applicant', $roles, true)) {
            return '/applicant/dashboard';
        }

        return '/dashboard';
    }
}
