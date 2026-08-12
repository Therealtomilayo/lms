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
            return ServiceResult::failure(['general' => ['Email and password are required.']], 'INVALID_CREDENTIALS');
        }

        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            return ServiceResult::failure(['general' => ['Invalid email or password.']], 'INVALID_CREDENTIALS');
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

        // Establish secure session
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

        $redirectUrl = $user->mustChangePassword
            ? '/profile/password'
            : $this->resolveDashboardUrl($user->roles);

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

        return '/dashboard';
    }
}
