<?php

declare(strict_types=1);

namespace App\Controllers\Dev;

use App\Controllers\Controller;
use App\Core\Config;
use App\Core\Request;
use App\Core\Response;

/**
 * Developer Component Showcase Controller
 * ONLY accessible in non-production environments
 */
class ShowcaseController extends Controller
{
    public function index(Request $request): Response
    {
        // Strict guard: Showcase is disabled in production
        if (Config::get('app.env', 'production') === 'production') {
            return $this->forbidden('Access Denied: The UI Component Showcase is disabled in production.');
        }

        return $this->view('dev/showcase', [
            'title' => 'UI Component Showcase — Claret LMS',
            'headerTitle' => 'Component Showcase',
            'headerSubtitle' => 'Interactive catalog of shared UI primitives and layout structures'
        ]);
    }
}
