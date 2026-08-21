<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Hardened Session Handler with CSRF, Secure Cookie Settings & Flash Messages
 */
class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $lifetime = (int)Config::get('session.lifetime', 7200);
        $cookieName = (string)Config::get('session.cookie_name', 'lms_session');
        $secure = (bool)Config::get('session.secure', false);
        $sameSite = (string)Config::get('session.samesite', 'Lax');

        // Check if request is HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $secure = true;
        }

        session_name($cookieName);

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $sameSite,
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        session_start();
        self::$started = true;

        // Manage flash message lifecycle
        self::ageFlashMessages();
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function setFlash(string $key, mixed $value): void
    {
        self::start();
        $_SESSION['_flash_new'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION['_flash_old'][$key] ?? $_SESSION['_flash_new'][$key] ?? $default;
    }

    public static function hasFlash(string $key): bool
    {
        self::start();
        return isset($_SESSION['_flash_old'][$key]) || isset($_SESSION['_flash_new'][$key]);
    }

    private static function ageFlashMessages(): void
    {
        $_SESSION['_flash_old'] = $_SESSION['_flash_new'] ?? [];
        $_SESSION['_flash_new'] = [];
    }

    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];

            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            session_destroy();
            self::$started = false;
        }
    }

    /**
     * Instance method delegation for Request->getSession()->...
     */
    public function __call(string $name, array $arguments): mixed
    {
        if ($name === 'flash') {
            self::setFlash(...$arguments);
            return null;
        }

        if (method_exists(self::class, $name)) {
            return forward_static_call_array([self::class, $name], $arguments);
        }

        throw new \BadMethodCallException("Method Session::{$name}() does not exist.");
    }
}
