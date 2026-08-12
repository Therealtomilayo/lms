<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Exceptions\ValidationException;
use App\Models\FileRecord;
use App\Repositories\ContentRepository;
use App\Repositories\FileRepository;
use App\Services\FileStorageService;
use PHPUnit\Framework\TestCase;

final class FileStorageServiceTest extends TestCase
{
    private string $tempDir;
    private FileStorageService $service;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/lms_test_uploads_' . uniqid();
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }

        $fileRepo = $this->createMock(FileRepository::class);
        $contentRepo = $this->createMock(ContentRepository::class);

        $this->service = new FileStorageService(
            fileRepository: $fileRepo,
            contentRepository: $contentRepo,
            uploadDir: $this->tempDir,
            maxSizeBytes: 10485760 // 10 MB
        );
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
            rmdir($this->tempDir);
        }
    }

    public function testRejectsProhibitedExtension(): void
    {
        $this->expectException(ValidationException::class);

        $tmpFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tmpFile, '<?php echo "evil"; ?>');

        try {
            $this->service->validateUploadedFile([
                'name' => 'malicious.php',
                'type' => 'text/php',
                'tmp_name' => $tmpFile,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($tmpFile),
            ]);
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }

    public function testRejectsExecutableExtension(): void
    {
        $this->expectException(ValidationException::class);

        $tmpFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tmpFile, 'test');

        try {
            $this->service->validateUploadedFile([
                'name' => 'trojan.exe',
                'type' => 'application/octet-stream',
                'tmp_name' => $tmpFile,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($tmpFile),
            ]);
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }

    public function testRejectsOversizedFile(): void
    {
        $this->expectException(ValidationException::class);

        $tmpFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tmpFile, 'dummy content');

        try {
            $this->service->validateUploadedFile([
                'name' => 'large.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $tmpFile,
                'error' => UPLOAD_ERR_OK,
                'size' => 20000000, // 20 MB (limit is 10 MB)
            ]);
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }

    public function testAcceptsValidPdfFile(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tmpFile, '%PDF-1.4 dummy pdf header and text');

        try {
            // Should not throw
            $this->service->validateUploadedFile([
                'name' => 'Chemistry_Lecture.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $tmpFile,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($tmpFile),
            ]);
            $this->assertTrue(true);
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }
}
