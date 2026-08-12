<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\ContentItem;
use PHPUnit\Framework\TestCase;

final class ContentItemModelTest extends TestCase
{
    public function testHydrateContentItemFromArray(): void
    {
        $data = [
            'id' => 1,
            'class_subject_id' => 5,
            'teacher_id' => 3,
            'topic' => 'Calculus Fundamentals',
            'title' => 'Introduction to Limits and Continuity',
            'description' => 'Detailed lecture notes on evaluating limits.',
            'type' => ContentItem::TYPE_NOTE,
            'file_id' => 12,
            'external_url' => null,
            'published_at' => '2026-08-12 09:00:00',
            'created_at' => '2026-08-12 08:30:00',
            'updated_at' => '2026-08-12 09:00:00',
        ];

        $item = ContentItem::fromArray($data);

        $this->assertSame(1, $item->id);
        $this->assertSame(5, $item->classSubjectId);
        $this->assertSame(3, $item->teacherId);
        $this->assertSame('Calculus Fundamentals', $item->topic);
        $this->assertSame('Introduction to Limits and Continuity', $item->title);
        $this->assertSame('Detailed lecture notes on evaluating limits.', $item->description);
        $this->assertSame(ContentItem::TYPE_NOTE, $item->type);
        $this->assertSame(12, $item->fileId);
        $this->assertNull($item->externalUrl);
        $this->assertTrue($item->isPublished());
        $this->assertFalse($item->isDraft());
    }

    public function testDraftContentItem(): void
    {
        $draft = ContentItem::fromArray([
            'id' => 2,
            'class_subject_id' => 5,
            'teacher_id' => 3,
            'title' => 'Upcoming Quiz Prep',
            'type' => ContentItem::TYPE_DOCUMENT,
            'published_at' => null,
            'created_at' => '2026-08-12 08:30:00',
            'updated_at' => '2026-08-12 08:30:00',
        ]);

        $this->assertFalse($draft->isPublished());
        $this->assertTrue($draft->isDraft());
    }
}
