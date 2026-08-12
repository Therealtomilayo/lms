<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Policies\ResultPolicy;
use App\Services\ReportCardService;

/**
 * Controller for Admin Report Card PDF Generation
 */
class ReportController extends Controller
{
    private ReportCardService $reportCardService;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ReportCardService $reportCardService = null
    ) {
        parent::__construct($authenticator);
        $this->reportCardService = $reportCardService ?? new ReportCardService();
    }

    public function pdf(Request $request, int|string $studentId, int|string $termId): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !ResultPolicy::canReview($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $sId = (int)$studentId;
        $tId = (int)$termId;

        $reportData = $this->reportCardService->getReportCardData($sId, $tId);
        $reportData['isPdf'] = true;

        return $this->view('student/grades/report_card', $reportData);
    }
}
