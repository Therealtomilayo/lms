<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Entity for an Individual Prospective Ward within an Application
 */
final class AdmissionWard
{
    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';

    public function __construct(
        public readonly int $id,
        public readonly int $applicationId,
        public readonly string $firstName,
        public readonly ?string $middleName,
        public readonly string $lastName,
        public readonly string $dateOfBirth,
        public readonly string $gender,
        public readonly ?string $stateOfOrigin = null,
        public readonly ?string $lga = null,
        public readonly string $nationality = 'Nigerian',
        public readonly ?string $religion = null,
        public readonly int $applyingForLevelId,
        public readonly string $classGrade,
        public readonly ?string $curriculumChoice = null,
        public readonly bool $useSchoolBus = false,
        public readonly ?string $previousSchool = null,
        public readonly ?string $lastGradePassed = null,
        public readonly ?string $medicalNotes = null,
        public readonly ?int $passportPhotoFileId = null,
        public readonly ?int $birthCertificateFileId = null,
        public readonly ?int $previousReportFileId = null,
        public readonly string $paymentStatus = self::PAYMENT_UNPAID,
        public readonly ?int $convertedStudentId = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?string $academicLevelName = null,
        public readonly ?string $studentAdmissionNumber = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data['id'],
            applicationId: (int)$data['application_id'],
            firstName: (string)$data['first_name'],
            middleName: isset($data['middle_name']) && $data['middle_name'] !== '' ? (string)$data['middle_name'] : null,
            lastName: (string)$data['last_name'],
            dateOfBirth: (string)$data['date_of_birth'],
            gender: (string)$data['gender'],
            stateOfOrigin: $data['state_of_origin'] ?? null,
            lga: $data['lga'] ?? null,
            nationality: (string)($data['nationality'] ?? 'Nigerian'),
            religion: $data['religion'] ?? null,
            applyingForLevelId: (int)$data['applying_for_level_id'],
            classGrade: (string)$data['class_grade'],
            curriculumChoice: $data['curriculum_choice'] ?? null,
            useSchoolBus: (bool)($data['use_school_bus'] ?? false),
            previousSchool: $data['previous_school'] ?? null,
            lastGradePassed: $data['last_grade_passed'] ?? null,
            medicalNotes: $data['medical_notes'] ?? null,
            passportPhotoFileId: !empty($data['passport_photo_file_id']) ? (int)$data['passport_photo_file_id'] : null,
            birthCertificateFileId: !empty($data['birth_certificate_file_id']) ? (int)$data['birth_certificate_file_id'] : null,
            previousReportFileId: !empty($data['previous_report_file_id']) ? (int)$data['previous_report_file_id'] : null,
            paymentStatus: (string)($data['payment_status'] ?? self::PAYMENT_UNPAID),
            convertedStudentId: !empty($data['converted_student_id']) ? (int)$data['converted_student_id'] : null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            academicLevelName: $data['academic_level_name'] ?? null,
            studentAdmissionNumber: $data['student_admission_number'] ?? null
        );
    }

    public function getFullName(): string
    {
        return trim("{$this->firstName} " . ($this->middleName ? "{$this->middleName} " : "") . $this->lastName);
    }

    public function isPaid(): bool
    {
        return $this->paymentStatus === self::PAYMENT_PAID;
    }

    public function isConverted(): bool
    {
        return $this->convertedStudentId !== null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->applicationId,
            'first_name' => $this->firstName,
            'middle_name' => $this->middleName,
            'last_name' => $this->lastName,
            'full_name' => $this->getFullName(),
            'date_of_birth' => $this->dateOfBirth,
            'gender' => $this->gender,
            'state_of_origin' => $this->stateOfOrigin,
            'lga' => $this->lga,
            'nationality' => $this->nationality,
            'religion' => $this->religion,
            'applying_for_level_id' => $this->applyingForLevelId,
            'class_grade' => $this->classGrade,
            'curriculum_choice' => $this->curriculumChoice,
            'use_school_bus' => $this->useSchoolBus,
            'previous_school' => $this->previousSchool,
            'last_grade_passed' => $this->lastGradePassed,
            'medical_notes' => $this->medicalNotes,
            'passport_photo_file_id' => $this->passportPhotoFileId,
            'birth_certificate_file_id' => $this->birthCertificateFileId,
            'previous_report_file_id' => $this->previousReportFileId,
            'payment_status' => $this->paymentStatus,
            'converted_student_id' => $this->convertedStudentId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
