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

        // Dispatch multi-channel broadcast (SMS & Email) to parents of students in published classes
        try {
            $academicRepo = new \App\Repositories\AcademicRepository();
            $term = $academicRepo->findTermById($tId);
            $session = $term ? $academicRepo->findSessionById($term->sessionId) : null;
            $termName = $term?->name ?? 'Current Term';
            $sessionName = $session?->name ?? date('Y');

            $classes = [];
            if ($classId) {
                $c = $academicRepo->findClassById($classId);
                if ($c) {
                    $classes[] = $c;
                }
            } else {
                $classes = $academicRepo->getAllClasses();
            }

            $parentRepo = new \App\Repositories\ParentRepository();
            $studentRepo = new \App\Repositories\StudentRepository();
            $notificationService = new \App\Services\NotificationService();
            $notifiedParentIds = [];

            foreach ($classes as $targetClass) {
                $className = $targetClass->getFullName();
                $students = $studentRepo->getAll(limit: 1000, offset: 0, classId: $targetClass->id);

                foreach ($students as $student) {
                    $guardians = $parentRepo->getGuardiansForStudent($student->id);
                    foreach ($guardians as $g) {
                        $pId = $g->userId;
                        if (in_array($pId, $notifiedParentIds, true)) {
                            continue;
                        }
                        $notifiedParentIds[] = $pId;

                        $phone = $g->phone ?? $g->user?->phone;
                        $email = $g->email ?? $g->user?->email;
                        if (!empty($phone) || !empty($email)) {
                            $notificationService->sendResultReleaseBroadcast(
                                phone: $phone,
                                email: $email,
                                className: $className,
                                termName: $termName,
                                sessionName: $sessionName,
                                userId: $pId
                            );
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log("Failed to broadcast result release notifications: " . $e->getMessage());
        }

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
