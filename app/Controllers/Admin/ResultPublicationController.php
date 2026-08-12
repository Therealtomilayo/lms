<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Policies\ResultPolicy;
use App\Services\ResultPublicationService;

/**
 * Controller for Admin Result Publication and Unpublishing
 */
class ResultPublicationController extends Controller
{
    private ResultPublicationService $publicationService;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ResultPublicationService $publicationService = null
    ) {
        parent::__construct($authenticator);
        $this->publicationService = $publicationService ?? new ResultPublicationService();
    }

    public function publish(Request $request, int|string|null $termId = null): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !ResultPolicy::canPublish($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $tId = $termId !== null ? (int)$termId : (int)($request->input('term_id') ?? $request->get('term_id') ?? 0);
        $rawClassId = $request->input('class_id') ?? $request->get('class_id');
        $classId = ($rawClassId !== null && $rawClassId !== '') ? (int)$rawClassId : null;
        $reason = $request->input('reason');

        $result = $this->publicationService->publishResults(
            $tId,
            $classId,
            $userContext->getUserId(),
            $reason ? (string)$reason : null
        );

        $redirectUrl = $classId
            ? "/admin/results/review?term_id={$tId}&class_id={$classId}"
            : "/admin/results/review?term_id={$tId}";

        return $this->redirectWithSuccess($redirectUrl, $result->message);
    }

    public function unpublish(Request $request, int|string|null $termId = null): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !ResultPolicy::canUnpublish($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $tId = $termId !== null ? (int)$termId : (int)($request->input('term_id') ?? $request->get('term_id') ?? 0);
        $rawClassId = $request->input('class_id') ?? $request->get('class_id');
        $classId = ($rawClassId !== null && $rawClassId !== '') ? (int)$rawClassId : null;
        $reason = (string)($request->input('reason') ?? 'Administrative unpublish');

        $result = $this->publicationService->unpublishResults($tId, $classId, $reason);

        $redirectUrl = $classId
            ? "/admin/results/review?term_id={$tId}&class_id={$classId}"
            : "/admin/results/review?term_id={$tId}";

        return $this->redirectWithSuccess($redirectUrl, $result->message);
    }
}
