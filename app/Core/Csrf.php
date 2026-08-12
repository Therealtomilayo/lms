<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Cross-Site Request Forgery (CSRF) Protection
 */
class Csrf
{
    private const TOKEN_KEY = '_csrf_token';

    public static function generate(): string
    {
        return self::generateToken();
    }

    public static function generateToken(): string
    {
        Session::start();

        $token = Session::get(self::TOKEN_KEY);
        if ($token === null || !is_string($token)) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::TOKEN_KEY, $token);
        }

        return $token;
    }

    public static function getToken(): string
    {
        return self::generateToken();
    }

    public static function validate(?string $token): bool
    {
        Session::start();

        $sessionToken = Session::get(self::TOKEN_KEY);
        if ($sessionToken === null || !is_string($sessionToken) || $token === null) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    public static function field(): string
    {
        $token = htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }

    public static function regenerateToken(): string
    {
        Session::start();

        $token = bin2hex(random_bytes(32));
        Session::set(self::TOKEN_KEY, $token);

        return $token;
    }
}
