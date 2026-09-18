<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Response;
use PHPUnit\Framework\TestCase;

final class ResponseStreamTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempFile = tempnam(sys_get_temp_dir(), 'pdf_test_');
        file_put_contents($this->tempFile, "0123456789ABCDEF"); // 16 bytes
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
        parent::tearDown();
    }

    public function testStreamFileFullContent(): void
    {
        $response = Response::streamFile($this->tempFile, 'sample.pdf', 'application/pdf');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('bytes', $response->getHeader('Accept-Ranges'));
        $this->assertSame('16', $response->getHeader('Content-Length'));
        $this->assertStringContainsString('inline; filename="sample.pdf"', $response->getHeader('Content-Disposition') ?? '');
        $this->assertSame(0, $response->getStreamStart());
        $this->assertSame(16, $response->getStreamLength());
        $this->assertSame($this->tempFile, $response->getStreamFilePath());
    }

    public function testStreamFilePartialContentWithExplicitRange(): void
    {
        $response = Response::streamFile($this->tempFile, 'sample.pdf', 'application/pdf', 'bytes=0-4');

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('bytes', $response->getHeader('Accept-Ranges'));
        $this->assertSame('5', $response->getHeader('Content-Length'));
        $this->assertSame('bytes 0-4/16', $response->getHeader('Content-Range'));
        $this->assertSame(0, $response->getStreamStart());
        $this->assertSame(5, $response->getStreamLength());
    }

    public function testStreamFilePartialContentWithOpenEndedRange(): void
    {
        $response = Response::streamFile($this->tempFile, 'sample.pdf', 'application/pdf', 'bytes=10-');

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('bytes', $response->getHeader('Accept-Ranges'));
        $this->assertSame('6', $response->getHeader('Content-Length'));
        $this->assertSame('bytes 10-15/16', $response->getHeader('Content-Range'));
        $this->assertSame(10, $response->getStreamStart());
        $this->assertSame(6, $response->getStreamLength());
    }

    public function testStreamFilePartialContentWithSuffixRange(): void
    {
        $response = Response::streamFile($this->tempFile, 'sample.pdf', 'application/pdf', 'bytes=-4');

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('bytes', $response->getHeader('Accept-Ranges'));
        $this->assertSame('4', $response->getHeader('Content-Length'));
        $this->assertSame('bytes 12-15/16', $response->getHeader('Content-Range'));
        $this->assertSame(12, $response->getStreamStart());
        $this->assertSame(4, $response->getStreamLength());
    }

    public function testStreamFileInvalidRangeReturns416(): void
    {
        $response = Response::streamFile($this->tempFile, 'sample.pdf', 'application/pdf', 'bytes=20-30');

        $this->assertSame(416, $response->getStatusCode());
        $this->assertSame('bytes */16', $response->getHeader('Content-Range'));
    }

    public function testStreamFileNotFoundReturns404(): void
    {
        $response = Response::streamFile('/non/existent/file.pdf', 'sample.pdf', 'application/pdf');

        $this->assertSame(404, $response->getStatusCode());
    }
}
