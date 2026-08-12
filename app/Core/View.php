<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Server-Side Template Rendering Engine
 */
class View
{
    private static string $viewsPath = '';
    private ?string $layout = null;
    private array $layoutData = [];
    private array $sections = [];
    private ?string $currentSection = null;

    public static function setViewsPath(string $path): void
    {
        self::$viewsPath = rtrim($path, '/\\');
    }

    public static function getViewsPath(): string
    {
        if (self::$viewsPath === '') {
            self::$viewsPath = dirname(__DIR__) . '/Views';
        }
        return self::$viewsPath;
    }

    public static function render(string $template, array $data = []): string
    {
        $view = new self();
        return $view->renderTemplate($template, $data);
    }

    public function renderTemplate(string $template, array $data = []): string
    {
        $templatePath = self::getViewsPath() . '/' . str_replace('.', '/', $template) . '.php';

        if (!file_exists($templatePath)) {
            throw new RuntimeException("View template not found: {$templatePath}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $templatePath;
        $content = ob_get_clean();

        if ($this->layout !== null) {
            $layoutPath = self::getViewsPath() . '/' . str_replace('.', '/', $this->layout) . '.php';
            if (!file_exists($layoutPath)) {
                throw new RuntimeException("Layout template not found: {$layoutPath}");
            }

            $layoutData = array_merge($data, $this->layoutData, ['content' => $content]);
            extract($layoutData, EXTR_SKIP);

            ob_start();
            include $layoutPath;
            return ob_get_clean();
        }

        return $content ?: '';
    }

    public function layout(string $layout, array $data = []): void
    {
        $this->layout = $layout;
        $this->layoutData = $data;
    }

    public function startSection(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    public function endSection(): void
    {
        if ($this->currentSection === null) {
            throw new RuntimeException('No section started.');
        }

        $this->sections[$this->currentSection] = ob_get_clean() ?: '';
        $this->currentSection = null;
    }

    public function yield(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function include(string $partial, array $data = []): void
    {
        $partialPath = self::getViewsPath() . '/' . str_replace('.', '/', $partial) . '.php';
        if (!file_exists($partialPath)) {
            throw new RuntimeException("Partial view not found: {$partialPath}");
        }

        extract($data, EXTR_SKIP);
        include $partialPath;
    }
}

// Global View Helpers
if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::getToken();
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return Session::getFlash('_old_' . $key, $default);
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $default = null): mixed
    {
        return Session::getFlash($key, $default);
    }
}

if (!function_exists('has_flash')) {
    function has_flash(string $key): bool
    {
        return Session::hasFlash($key);
    }
}
