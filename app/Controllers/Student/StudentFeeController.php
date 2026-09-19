<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Repositories\FeeRepository;
use App\Repositories\StudentRepository;

class StudentFeeController extends Controller
{
    private FeeRepository $feeRepo;
    private StudentRepository $studentRepo;

    public function __construct(
        ?FeeRepository $feeRepo = null,
        ?StudentRepository $studentRepo = null
    ) {
        $this->feeRepo = $feeRepo ?? new FeeRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
    }

    public function index(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context') ?? $this->getUserContext($request);
        if (!$userContext instanceof UserContext || !$userContext->hasRole('student')) {
            return $this->forbidden('Access denied. Student credentials required.');
        }

        $student = $this->studentRepo->findByUserId($userContext->getUserId());
        if (!$student) {
            return $this->redirectWithFlash('/student/dashboard', 'error', 'Student profile not found.');
        }

        $invoices = $this->feeRepo->getInvoicesForStudent($student->id);

        return $this->view('student/fees/index', [
            'student' => $student,
            'invoices' => $invoices,
        ], 200, 'layouts/student');
    }

    public function show(Request $request, string $id): Response
    {
        $userContext = $request->getAttribute('user_context') ?? $this->getUserContext($request);
        if (!$userContext instanceof UserContext || !$userContext->hasRole('student')) {
            return $this->forbidden('Access denied.');
        }

        $student = $this->studentRepo->findByUserId($userContext->getUserId());
        if (!$student) {
            return $this->redirectWithFlash('/student/dashboard', 'error', 'Student profile not found.');
        }

        $invoice = $this->feeRepo->findInvoiceById((int)$id);
        if (!$invoice || $invoice->studentId !== $student->id) {
            return $this->forbidden('Access denied.');
        }

        return $this->view('parent/fees/show', [
            'invoice' => $invoice,
            'isStudent' => true,
        ], 200, 'layouts/student');
    }
}
