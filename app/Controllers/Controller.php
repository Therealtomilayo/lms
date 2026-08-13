<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\UserContext;
use App\Core\Validator;
use App\Core\View;
use App\Core\WebAuthenticator;

/**
 * Base Controller with HTTP Helpers and Input Validation
 */
abstract class Controller
{
    protected AuthenticatorInterface $authenticator;

    public function __construct(?AuthenticatorInterface $authenticator = null)
    {
        $this->authenticator = $authenticator ?? new WebAuthenticator();
    }

    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        $html = View::render($template, $data);
        return Response::html($html, $status);
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function forbidden(string $message = 'Access Denied'): Response
    {
        return Response::html("<h1>403 Forbidden</h1><p>{$message}</p>", 403);
    }

    protected function notFound(string $message = 'Resource Not Found'): Response
    {
        return Response::html("<h1>404 Not Found</h1><p>{$message}</p>", 404);
    }

    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }

    protected function redirectWithErrors(string $url, array $errors, array $oldInput = []): Response
    {
        Session::start();
        Session::setFlash('errors', $errors);
        foreach ($oldInput as $key => $val) {
            // Never preserve passwords or sensitive tokens
            if (!str_contains($key, 'password') && !str_contains($key, 'token')) {
                Session::setFlash('_old_' . $key, $val);
            }
        }

        return Response::redirect($url);
    }

    protected function redirectWithSuccess(string $url, string $message): Response
    {
        Session::start();
        Session::setFlash('success', $message);

        return Response::redirect($url);
    }

    protected function redirectWithError(string $url, string $message): Response
    {
        Session::start();
        Session::setFlash('error', $message);

        return Response::redirect($url);
    }

    protected function redirectWithWarning(string $url, string $message): Response
    {
        Session::start();
        Session::setFlash('warning', $message);

        return Response::redirect($url);
    }

    protected function redirectWithFlash(string $url, string $message, string $type = 'success'): Response
    {
        Session::start();
        Session::setFlash($type, $message);

        return Response::redirect($url);
    }

    protected function validate(Request $request, array $rules, array $messages = []): array
    {
        $data = $request->all();
        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        return $validator->validated();
    }

    protected function user(Request $request): ?UserContext
    {
        if (!isset($this->authenticator)) {
            $this->authenticator = new WebAuthenticator();
        }
        return $this->authenticator->user($request);
    }

    protected function getUserContext(Request $request): ?UserContext
    {
        return $this->user($request);
    }

    protected function setFlash(Request $request, string $type, string $message): void
    {
        Session::start();
        Session::setFlash($type, $message);
    }

    protected function render(string $template, array $data = [], ?string $layout = null): string
    {
        $view = new View();
        if ($layout !== null) {
            $view->layout($layout);
        }
        return $view->renderTemplate($template, $data);
    }
}
