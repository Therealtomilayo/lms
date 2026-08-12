<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\FileRecord;
use PHPUnit\Framework\TestCase;

final class FileRecordModelTest extends TestCase
{
    public function testHydrateFileRecordFromArray(): void
    {
        $data = [
            'id' => 10,
            'uuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'storage_key' => 'a1b2c3d4e5f6.pdf',
            'original_name' => 'Physics_Lesson_1.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1048576, // 1 MB
            'sha256' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
            'uploaded_by' => 5,
            'owner_type' => 'content_item',
            'owner_id' => 42,
            'deleted_at' => null,
            'created_at' => '2026-08-12 10:00:00',
        ];

        $file = FileRecord::fromArray($data);

        $this->assertSame(10, $file->id);
        $this->assertSame('f47ac10b-58cc-4372-a567-0e02b2c3d479', $file->uuid);
        $this->assertSame('a1b2c3d4e5f6.pdf', $file->storageKey);
        $this->assertSame('Physics_Lesson_1.pdf', $file->originalName);
        $this->assertSame('application/pdf', $file->mimeType);
        $this->assertSame(1048576, $file->sizeBytes);
        $this->assertSame(5, $file->uploadedBy);
        $this->assertSame('content_item', $file->ownerType);
        $this->assertSame(42, $file->ownerId);
        $this->assertFalse($file->isDeleted());
        $this->assertTrue($file->isPdf());
        $this->assertFalse($file->isImage());
        $this->assertFalse($file->isVideo());
        $this->assertSame('1.00 MB', $file->getFormattedSize());
    }

    public function testMimeTypeHelpers(): void
    {
        $imageFile = FileRecord::fromArray([
            'id' => 1,
            'uuid' => 'u1',
            'storage_key' => 'img.png',
            'original_name' => 'diagram.png',
            'mime_type' => 'image/png',
            'size_bytes' => 512000,
            'sha256' => 'hash',
            'uploaded_by' => 1,
            'created_at' => '2026-08-12 10:00:00',
        ]);

        $this->assertTrue($imageFile->isImage());
        $this->assertFalse($imageFile->isPdf());
        $this->assertSame('500.0 KB', $imageFile->getFormattedSize());

        $videoFile = FileRecord::fromArray([
            'id' => 2,
            'uuid' => 'u2',
            'storage_key' => 'vid.mp4',
            'original_name' => 'lecture.mp4',
            'mime_type' => 'video/mp4',
            'size_bytes' => 52428800,
            'sha256' => 'hash',
            'uploaded_by' => 1,
            'created_at' => '2026-08-12 10:00:00',
        ]);

        $this->assertTrue($videoFile->isVideo());
        $this->assertFalse($videoFile->isAudio());
        $this->assertSame('50.00 MB', $videoFile->getFormattedSize());
    }

    public function testSoftDeletedFile(): void
    {
        $deletedFile = FileRecord::fromArray([
            'id' => 3,
            'uuid' => 'u3',
            'storage_key' => 'del.txt',
            'original_name' => 'note.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 100,
            'sha256' => 'hash',
            'uploaded_by' => 1,
            'deleted_at' => '2026-08-12 10:30:00',
            'created_at' => '2026-08-12 10:00:00',
        ]);

        $this->assertTrue($deletedFile->isDeleted());
    }
}
