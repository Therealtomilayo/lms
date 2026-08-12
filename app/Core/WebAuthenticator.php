<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\UserRepository;

/**
 * Session-based Web Authenticator
 */
class WebAuthenticator implements AuthenticatorInterface
{
    private UserRepository $userRepository;

    public function __construct(?UserRepository $userRepository = null)
    {
        $this->userRepository = $userRepository ?? new UserRepository();
    }

    public function authenticate(Request $request): ?UserContext
    {
        // Check if already authenticated on this request instance
        $cached = $request->getAttribute('_user_context');
        if ($cached instanceof UserContext) {
            return $cached;
        }

        Session::start();

        $userId = Session::get('user_id');
        $sessionHash = Session::get('session_hash');

        if (!$userId || !$sessionHash) {
            return null;
        }

        // Verify session against database
        $sessionRecord = $this->userRepository->findSession((string)$sessionHash);
        if (!$sessionRecord || (int)$sessionRecord['user_id'] !== (int)$userId) {
            // Invalidate stale or revoked session
            Session::destroy();
            return null;
        }

        // Verify user account status
        $user = $this->userRepository->findById((int)$userId);
        if (!$user || !$user->isActive()) {
            Session::destroy();
            return null;
        }

        // Update session last seen timestamp periodically
        $this->userRepository->updateSessionLastSeen((string)$sessionHash);

        $context = UserContext::fromUser($user);
        $request->setAttribute('_user_context', $context);

        return $context;
    }

    public function check(Request $request): bool
    {
        return $this->authenticate($request) !== null;
    }

    public function user(Request $request): ?UserContext
    {
        return $this->authenticate($request);
    }
}
