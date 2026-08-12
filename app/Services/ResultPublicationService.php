<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\DomainRuleException;
use App\DTO\ServiceResult;
use App\Repositories\GradebookRepository;
use App\Repositories\ResultPublicationRepository;

/**
 * Service for Term Result Review, Publication, and Unpublishing
 */
final class ResultPublicationService
{
    private readonly ResultPublicationRepository $publicationRepo;
    private readonly GradebookRepository $gradebookRepo;

    public function __construct(
        ?ResultPublicationRepository $publicationRepo = null,
        ?GradebookRepository $gradebookRepo = null
    ) {
        $this->publicationRepo = $publicationRepo ?? new ResultPublicationRepository();
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository();
    }

    public function isPublished(int $termId, ?int $classId = null): bool
    {
        return $this->publicationRepo->isPublished($termId, $classId);
    }

    public function publishResults(
        int $termId,
        ?int $classId,
        int $publishedBy,
        ?string $reason = null
    ): ServiceResult {
        $publicationId = $this->publicationRepo->publish($termId, $classId, $publishedBy, $reason);

        return ServiceResult::success([
            'publication_id' => $publicationId,
            'term_id' => $termId,
            'class_id' => $classId,
            'status' => 'published',
        ], 'Results published successfully.');
    }

    public function unpublishResults(
        int $termId,
        ?int $classId,
        ?string $reason = null
    ): ServiceResult {
        if (empty($reason)) {
            throw new DomainRuleException('A reason is required to unpublish results.');
        }

        $this->publicationRepo->unpublish($termId, $classId, $reason);

        return ServiceResult::success([
            'term_id' => $termId,
            'class_id' => $classId,
            'status' => 'unpublished',
        ], 'Results have been unpublished.');
    }
}
