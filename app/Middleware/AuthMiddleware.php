<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\WebAuthenticator;

/**
 * Enforces Authenticated Session and must_change_password Workflow
 */
class AuthMiddleware
{
    private AuthenticatorInterface $authenticator;

    public function __construct(?AuthenticatorInterface $authenticator = null)
    {
        $this->authenticator = $authenticator ?? new WebAuthenticator();
    }

    public function handle(Request $request, callable $next): Response
    {
        $userContext = $this->authenticator->authenticate($request);

        if (!$userContext) {
            if ($request->isJson() || $request->isAjax()) {
                return Response::json(['error' => 'Unauthenticated.'], 401);
            }

            Session::start();
            Session::set('_intended_url', $request->getPath());
            Session::setFlash('error', 'Please log in to continue.');

            return Response::redirect('/login');
        }

        // Enforce password change before any other route (except password change itself and logout)
        $currentPath = $request->getPath();
        $allowedWhenPasswordChangeRequired = ['/profile/password', '/logout', '/password/change'];

        if ($userContext->mustChangePassword && !in_array($currentPath, $allowedWhenPasswordChangeRequired, true)) {
            if ($request->isJson() || $request->isAjax()) {
                return Response::json([
                    'error' => 'You must change your password before proceeding.',
                    'code' => 'MUST_CHANGE_PASSWORD',
                ], 403);
            }

            Session::setFlash('warning', 'You must change your default password before accessing your account.');
            return Response::redirect('/profile/password');
        }

        $request->setAttribute('user_context', $userContext);

        return $next($request);
    }
}
