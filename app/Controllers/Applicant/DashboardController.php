<?php

declare(strict_types=1);

namespace App\Controllers\Applicant;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\AdmissionService;

/**
 * Controller for Applicant / Prospective Parent Dashboard & Overview
 */
class DashboardController extends Controller
{
    private AdmissionService $admissionService;

    public function __construct(?AdmissionService $admissionService = null)
    {
        parent::__construct();
        $this->admissionService = $admissionService ?? new AdmissionService();
    }

    public function index(Request $request): Response
    {
        $user = $this->user($request);
        $data = $this->admissionService->getApplicantDashboardData($user->id);

        return $this->view('applicant/dashboard', array_merge($data, [
            'title' => 'Applicant Dashboard — Claret Admissions',
            'user' => $user,
        ]));
    }
}
