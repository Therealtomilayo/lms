<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Pluggable Authenticator Contract
 */
interface AuthenticatorInterface
{
    /**
     * Authenticate request and resolve active UserContext
     */
    public function authenticate(Request $request): ?UserContext;

    /**
     * Check if request is authenticated
     */
    public function check(Request $request): bool;

    /**
     * Get currently authenticated user context
     */
    public function user(Request $request): ?UserContext;
}
