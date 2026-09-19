<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AdmissionService;

/**
 * Public Controller for Admission Entry Point, Eligibility Checks, and Applicant Onboarding
 */
class AdmissionController extends Controller
{
    private AdmissionService $admissionService;

    public function __construct(?AdmissionService $admissionService = null)
    {
        parent::__construct();
        $this->admissionService = $admissionService ?? new AdmissionService();
    }

    /**
     * Primary Portal Entry Point: /apply
     */
    public function showApply(Request $request): Response
    {
        // If already logged in as applicant, direct to applicant dashboard
        if ($this->authenticator->check($request)) {
            $user = $this->user($request);
            if ($user->isApplicant()) {
                return $this->redirect('/applicant/dashboard');
            }
        }

        $session = $this->admissionService->getActiveSession();
        $isOpen = $this->admissionService->isAdmissionOpen();

        return $this->view('public/admissions/apply', [
            'title' => 'Online Admissions — Claret International School',
            'session' => $session,
            'isOpen' => $isOpen,
        ]);
    }

    /**
     * Applicant Registration Form: /apply/register
     */
    public function showRegister(Request $request): Response
    {
        if ($this->authenticator->check($request)) {
            $user = $this->user($request);
            if ($user->isApplicant()) {
                return $this->redirect('/applicant/dashboard');
            }
        }

        $session = $this->admissionService->getActiveSession();
        $isOpen = $this->admissionService->isAdmissionOpen();

        if (!$isOpen) {
            return $this->redirectWithErrors('/apply', ['general' => ['Admissions are currently closed. New applications cannot be initiated.']]);
        }

        return $this->view('public/admissions/register', [
            'title' => 'Start Admission Application — Claret International School',
            'session' => $session,
            'errors' => Session::getFlash('errors', []),
        ]);
    }

    /**
     * Process Applicant Registration: POST /apply/register
     */
    public function register(Request $request): Response
    {
        try {
            $validated = $this->validate($request, [
                'name' => 'required|min:3|max:150',
                'email' => 'required|email|max:150',
                'password' => 'required|min:8',
                'phone' => 'max:30',
            ]);
        } catch (ValidationException $e) {
            return $this->redirectWithErrors('/apply/register', $e->getErrors(), $request->all());
        }

        $result = $this->admissionService->registerApplicant(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
            phone: $validated['phone'] ?? null,
            ipAddress: $request->clientIp(),
            userAgent: $request->userAgent()
        );

        if ($result->isFailure()) {
            return $this->redirectWithErrors('/apply/register', $result->errors, $request->all());
        }

        return $this->redirectWithSuccess(
            '/applicant/dashboard',
            'Welcome! Your admission account has been created. Follow the steps below to complete your application.'
        );
    }
}
