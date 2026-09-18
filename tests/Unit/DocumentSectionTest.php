<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\DocumentSection;
use PHPUnit\Framework\TestCase;

final class DocumentSectionTest extends TestCase
{
    public function testModelInstantiationAndRangeHelpers(): void
    {
        $section = new DocumentSection(
            id: 1,
            contentItemId: 10,
            title: 'Cell Structure',
            startPage: 5,
            endPage: 12,
            sequenceOrder: 2,
            createdAt: '2026-09-10 12:00:00',
            updatedAt: '2026-09-10 12:00:00'
        );

        $this->assertSame(1, $section->id);
        $this->assertSame(10, $section->contentItemId);
        $this->assertSame('Cell Structure', $section->title);
        $this->assertSame(5, $section->startPage);
        $this->assertSame(12, $section->endPage);
        $this->assertSame(2, $section->sequenceOrder);
        $this->assertSame(8, $section->getTotalPages());
        $this->assertSame([5, 6, 7, 8, 9, 10, 11, 12], $section->getPageRange());

        $this->assertTrue($section->containsPage(5));
        $this->assertTrue($section->containsPage(8));
        $this->assertTrue($section->containsPage(12));
        $this->assertFalse($section->containsPage(4));
        $this->assertFalse($section->containsPage(13));
    }

    public function testCalculateProgressZeroPagesRead(): void
    {
        $section = new DocumentSection(1, 10, 'Introduction', 1, 5, 1);
        $calc = $section->calculateProgress([]);

        $this->assertSame(0.0, $calc['progress_percent']);
        $this->assertFalse($calc['is_completed']);
        $this->assertSame([], $calc['pages_read']);
        $this->assertSame(0, $calc['unique_pages_count']);
        $this->assertSame(5, $calc['total_pages']);
    }

    public function testCalculateProgressIntersectionOnlyCountsPagesWithinRange(): void
    {
        $section = new DocumentSection(1, 10, 'Section 2', 6, 12, 2);
        // Student read pages 1, 2, 3, 6, 7, 20
        $calc = $section->calculateProgress([1, 2, 3, 6, 7, 20]);

        // Intersecting pages should only be [6, 7]
        $this->assertSame([6, 7], $calc['pages_read']);
        $this->assertSame(2, $calc['unique_pages_count']);
        $this->assertSame(7, $calc['total_pages']);
        // 2 / 7 * 100 = 28.57%
        $this->assertSame(28.57, $calc['progress_percent']);
        $this->assertFalse($calc['is_completed']);
    }

    public function testCalculateProgressDeduplicatesDocumentPages(): void
    {
        $section = new DocumentSection(1, 10, 'Section 1', 1, 4, 1);
        // Duplicate pages reported: [1, 1, 2, 2, 2]
        $calc = $section->calculateProgress([1, 1, 2, 2, 2]);

        $this->assertSame([1, 2], $calc['pages_read']);
        $this->assertSame(2, $calc['unique_pages_count']);
        $this->assertSame(4, $calc['total_pages']);
        $this->assertSame(50.0, $calc['progress_percent']);
        $this->assertFalse($calc['is_completed']);
    }

    public function testCompletionThresholdStrictlyAtNinetyPercent(): void
    {
        // 10-page section: pages 1 to 10
        $section = new DocumentSection(1, 10, 'Chapter 1', 1, 10, 1);

        // 8 pages = 80% -> incomplete
        $calc8 = $section->calculateProgress(range(1, 8));
        $this->assertSame(80.0, $calc8['progress_percent']);
        $this->assertFalse($calc8['is_completed']);

        // 9 pages = 90% -> completed!
        $calc9 = $section->calculateProgress(range(1, 9));
        $this->assertSame(90.0, $calc9['progress_percent']);
        $this->assertTrue($calc9['is_completed']);

        // 10 pages = 100% -> completed!
        $calc10 = $section->calculateProgress(range(1, 10));
        $this->assertSame(100.0, $calc10['progress_percent']);
        $this->assertTrue($calc10['is_completed']);
    }

    public function testSinglePageSectionReachesHundredPercentOnSingleRead(): void
    {
        $section = new DocumentSection(1, 10, 'Epilogue', 20, 20, 3);
        $this->assertSame(1, $section->getTotalPages());

        $calc = $section->calculateProgress([20]);
        $this->assertSame(100.0, $calc['progress_percent']);
        $this->assertTrue($calc['is_completed']);
    }

    public function testOverlapsWithMethod(): void
    {
        $secA = new DocumentSection(1, 10, 'Sec A', 1, 5, 1);

        // Overlapping ranges
        $this->assertTrue($secA->overlapsWith(1, 5)); // Exact
        $this->assertTrue($secA->overlapsWith(5, 10)); // Boundary overlap on page 5
        $this->assertTrue($secA->overlapsWith(2, 4)); // Nested
        $this->assertTrue($secA->overlapsWith(0, 1)); // Boundary overlap on page 1

        // Non-overlapping ranges
        $this->assertFalse($secA->overlapsWith(6, 10)); // Adjacent after
        $this->assertFalse($secA->overlapsWith(8, 12)); // Gap after
    }

    public function testToArrayAndFromArray(): void
    {
        $section = new DocumentSection(5, 20, 'Introduction', 1, 10, 1, '2026-09-10 10:00:00', '2026-09-10 10:00:00');
        $array = $section->toArray();

        $this->assertSame(5, $array['id']);
        $this->assertSame(20, $array['content_item_id']);
        $this->assertSame('Introduction', $array['title']);
        $this->assertSame(1, $array['start_page']);
        $this->assertSame(10, $array['end_page']);
        $this->assertSame(1, $array['sequence_order']);
        $this->assertSame(10, $array['total_pages']);

        $restored = DocumentSection::fromArray($array);
        $this->assertSame($section->id, $restored->id);
        $this->assertSame($section->title, $restored->title);
        $this->assertSame($section->startPage, $restored->startPage);
        $this->assertSame($section->endPage, $restored->endPage);
    }
}
