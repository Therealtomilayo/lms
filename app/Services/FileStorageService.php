<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\Models\FileRecord;
use App\Policies\FilePolicy;
use App\Repositories\ContentRepository;
use App\Repositories\FileRepository;

/**
 * Application Service for Protected File Storage & Delivery
 * Implements strict upload validation, MIME sniffing, opaque storage keys, and authorized streaming.
 */
class FileStorageService
{
    private FileRepository $fileRepository;
    private ContentRepository $contentRepository;
    private ?\App\Repositories\AcademicRepository $academicRepository;
    private ?\App\Repositories\TeacherRepository $teacherRepository;
    private ?\App\Repositories\StudentRepository $studentRepository;
    private ?\App\Repositories\EnrollmentRepository $enrollmentRepository;
    private ?\App\Repositories\ParentRepository $parentRepository;
    private ?\App\Repositories\AssignmentRepository $assignmentRepository;
    private string $uploadDir;
    private int $maxSizeBytes;

    public const DEFAULT_MAX_SIZE_BYTES = 26214400; // 25 MB

    public const ALLOWED_EXTENSIONS = [
        // PDF & Documents
        'pdf', 'doc', 'docx', 'odt', 'rtf', 'txt', 'csv',
        // Spreadsheets & Presentations
        'xls', 'xlsx', 'ods', 'ppt', 'pptx', 'odp',
        // Images
        'jpg', 'jpeg', 'png', 'webp', 'gif',
        // Media (Audio / Video)
        'mp3', 'wav', 'm4a', 'ogg', 'mp4', 'webm', 'mov',
        // Archives
        'zip', 'rar', '7z',
    ];

    public const PROHIBITED_EXTENSIONS = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'phar',
        'exe', 'bat', 'cmd', 'sh', 'bin', 'cgi', 'pl', 'py', 'js', 'vbs',
        'html', 'htm', 'xhtml', 'svg', 'htaccess', 'env',
    ];

    public function __construct(
        ?FileRepository $fileRepository = null,
        ?ContentRepository $contentRepository = null,
        ?string $uploadDir = null,
        ?int $maxSizeBytes = null,
        ?\App\Repositories\AcademicRepository $academicRepository = null,
        ?\App\Repositories\TeacherRepository $teacherRepository = null,
        ?\App\Repositories\StudentRepository $studentRepository = null,
        ?\App\Repositories\EnrollmentRepository $enrollmentRepository = null,
        ?\App\Repositories\ParentRepository $parentRepository = null,
        ?\App\Repositories\AssignmentRepository $assignmentRepository = null
    ) {
        $this->fileRepository = $fileRepository ?? new FileRepository();
        $this->contentRepository = $contentRepository ?? new ContentRepository();
        $this->academicRepository = $academicRepository;
        $this->teacherRepository = $teacherRepository;
        $this->studentRepository = $studentRepository;
        $this->enrollmentRepository = $enrollmentRepository;
        $this->parentRepository = $parentRepository;
        $this->assignmentRepository = $assignmentRepository;
        $this->uploadDir = $uploadDir ?? dirname(__DIR__, 2) . '/storage/uploads';
        $this->maxSizeBytes = $maxSizeBytes ?? (int)($_ENV['UPLOAD_MAX_BYTES'] ?? self::DEFAULT_MAX_SIZE_BYTES);

        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0755, true);
        }
    }

    public function getUploadDir(): string
    {
        return $this->uploadDir;
    }

    /**
     * Validate an uploaded file payload from $_FILES.
     */
    public function validateUploadedFile(array $file): void
    {
        $errors = [];

        if (!isset($file['error']) || is_array($file['error'])) {
            throw new ValidationException(['file' => ['Invalid upload parameters.']]);
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new ValidationException(['file' => ['No file was uploaded.']]);
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new ValidationException(['file' => ['File size exceeds server upload limit.']]);
            default:
                throw new ValidationException(['file' => ['Failed to upload file due to a system error.']]);
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0) {
            $errors['file'][] = 'Uploaded file is empty.';
        } elseif ($size > $this->maxSizeBytes) {
            $maxMb = round($this->maxSizeBytes / 1048576, 1);
            $errors['file'][] = "File size exceeds the allowed limit of {$maxMb}MB.";
        }

        $originalName = (string)($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($extension === '' || in_array($extension, self::PROHIBITED_EXTENSIONS, true)) {
            $errors['file'][] = 'This file extension is prohibited for security reasons.';
        } elseif (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $errors['file'][] = "File type .{$extension} is not supported.";
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        if ($tmpName === '' || !file_exists($tmpName)) {
            $errors['file'][] = 'Uploaded temporary file does not exist.';
        } else {
            // MIME sniffing via fileinfo
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $detectedMime = $finfo->file($tmpName);

            if ($detectedMime === false || $detectedMime === 'application/x-dosexec' || $detectedMime === 'text/x-php') {
                $errors['file'][] = 'File content is invalid or executable.';
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * Process, hash, securely move, and persist an uploaded file.
     */
    public function storeUploadedFile(
        array $file,
        int $uploadedBy,
        string $ownerType,
        int $ownerId
    ): FileRecord {
        $this->validateUploadedFile($file);

        $tmpName = (string)$file['tmp_name'];
        $originalName = basename((string)$file['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $sizeBytes = (int)$file['size'];

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = (string)$finfo->file($tmpName);
        if ($mimeType === '') {
            $mimeType = 'application/octet-stream';
        }

        $sha256 = hash_file('sha256', $tmpName);
        if ($sha256 === false) {
            throw new DomainRuleException('Failed to calculate file hash.');
        }

        // Generate opaque random storage key
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );

        $storageKey = bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . $extension : '');
        $destination = $this->uploadDir . DIRECTORY_SEPARATOR . $storageKey;

        // Move uploaded file (or copy/rename for CLI/testing environments)
        if (is_uploaded_file($tmpName)) {
            if (!move_uploaded_file($tmpName, $destination)) {
                throw new DomainRuleException('Failed to move uploaded file to protected storage.');
            }
        } else {
            if (!copy($tmpName, $destination)) {
                throw new DomainRuleException('Failed to store file in protected storage.');
            }
        }

        return $this->fileRepository->create(
            uuid: $uuid,
            storageKey: $storageKey,
            originalName: $originalName,
            mimeType: $mimeType,
            sizeBytes: $sizeBytes,
            sha256: $sha256,
            uploadedBy: $uploadedBy,
            ownerType: $ownerType,
            ownerId: $ownerId
        );
    }

    /**
     * Store raw content or an existing local path into protected storage.
     */
    public function storeRawFile(
        string $sourcePath,
        string $originalName,
        int $uploadedBy,
        string $ownerType,
        int $ownerId
    ): FileRecord {
        if (!file_exists($sourcePath)) {
            throw new ResourceNotFoundException('Source file not found.');
        }

        $sizeBytes = (int)filesize($sourcePath);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = (string)$finfo->file($sourcePath);
        if ($mimeType === '') {
            $mimeType = 'application/octet-stream';
        }

        $sha256 = hash_file('sha256', $sourcePath);
        if ($sha256 === false) {
            throw new DomainRuleException('Failed to calculate file hash.');
        }

        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );

        $storageKey = bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . $extension : '');
        $destination = $this->uploadDir . DIRECTORY_SEPARATOR . $storageKey;

        if (!copy($sourcePath, $destination)) {
            throw new DomainRuleException('Failed to copy file to protected storage.');
        }

        return $this->fileRepository->create(
            uuid: $uuid,
            storageKey: $storageKey,
            originalName: basename($originalName),
            mimeType: $mimeType,
            sizeBytes: $sizeBytes,
            sha256: $sha256,
            uploadedBy: $uploadedBy,
            ownerType: $ownerType,
            ownerId: $ownerId
        );
    }

    /**
     * Retrieve a file record and verified physical path for authorized download/streaming.
     * Enforces RBAC / ownership policy.
     *
     * @return array{file: FileRecord, path: string}
     */
    public function getFileForDownload(int|string $fileIdOrUuid, UserContext $actor): array
    {
        $file = is_numeric($fileIdOrUuid)
            ? $this->fileRepository->findById((int)$fileIdOrUuid)
            : $this->fileRepository->findByUuid((string)$fileIdOrUuid);

        if (!$file || $file->isDeleted()) {
            throw new ResourceNotFoundException('File not found or has been removed.');
        }

        // Evaluate Authorization Policy
        if (!FilePolicy::userCanAccessFile(
            $actor,
            $file,
            $this->contentRepository,
            $this->academicRepository,
            $this->teacherRepository,
            $this->studentRepository,
            $this->enrollmentRepository,
            $this->parentRepository,
            $this->assignmentRepository
        )) {
            // Masked denial as 404 for sensitive files per 06-rbac-permissions.md
            throw new ResourceNotFoundException('File not found.');
        }

        // Prevent path traversal on storage key
        $cleanKey = basename($file->storageKey);
        $absolutePath = $this->uploadDir . DIRECTORY_SEPARATOR . $cleanKey;

        if (!file_exists($absolutePath) || !is_file($absolutePath)) {
            throw new ResourceNotFoundException('Physical file not found on disk.');
        }

        return [
            'file' => $file,
            'path' => $absolutePath,
        ];
    }

    public function deleteFile(int $fileId, UserContext $actor): bool
    {
        $file = $this->fileRepository->findById($fileId);
        if (!$file) {
            throw new ResourceNotFoundException('File not found.');
        }

        if (!$actor->hasAnyRole(['super_admin', 'admin']) && $file->uploadedBy !== $actor->userId) {
            throw new AuthorizationException('You are not authorized to delete this file.');
        }

        return $this->fileRepository->softDelete($fileId);
    }
}
