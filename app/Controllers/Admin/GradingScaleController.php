<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Policies\GradebookPolicy;
use App\Repositories\GradingScaleRepository;

/**
 * Controller for Admin Grading Scale & Grade Boundary Management
 */
class GradingScaleController extends Controller
{
    private GradingScaleRepository $gradingScaleRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?GradingScaleRepository $gradingScaleRepo = null
    ) {
        parent::__construct($authenticator);
        $this->gradingScaleRepo = $gradingScaleRepo ?? new GradingScaleRepository();
    }

    public function index(Request $request): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !GradebookPolicy::canManageGradingScales($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $scales = $this->gradingScaleRepo->getAll();

        return $this->view('admin/grading_scales/index', [
            'scales' => $scales,
        ]);
    }

    public function store(Request $request): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !GradebookPolicy::canManageGradingScales($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $scaleId = $this->gradingScaleRepo->createScale([
            'name' => (string)$request->input('name'),
            'description' => (string)$request->input('description'),
            'is_default' => (bool)$request->input('is_default'),
        ]);

        $boundariesInput = $request->input('boundaries', []);
        if (is_array($boundariesInput) && !empty($boundariesInput)) {
            $this->gradingScaleRepo->syncBoundaries($scaleId, $boundariesInput);
        }

        return $this->redirectWithSuccess('/admin/grading-scales', 'Grading scale created successfully.');
    }
}
