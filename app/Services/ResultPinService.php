<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\ServiceResult;
use App\Models\ResultAccessPin;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\ResultPinRepository;
use App\Repositories\StudentRepository;

/**
 * Service for Result Access Scratch-Card PINs, Cryptographic Generation & Usage Verification
 */
class ResultPinService
{
    private ResultPinRepository $pinRepository;
    private StudentRepository $studentRepository;
    private AcademicRepository $academicRepository;
    private EnrollmentRepository $enrollmentRepository;

    /**
     * Unambiguous uppercase alphanumeric charset excluding confusing characters: 0, O, 1, I, L
     */
    private const PIN_CHARSET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public function __construct(
        ?ResultPinRepository $pinRepository = null,
        ?StudentRepository $studentRepository = null,
        ?AcademicRepository $academicRepository = null,
        ?EnrollmentRepository $enrollmentRepository = null
    ) {
        $this->pinRepository = $pinRepository ?? new ResultPinRepository();
        $this->studentRepository = $studentRepository ?? new StudentRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->enrollmentRepository = $enrollmentRepository ?? new EnrollmentRepository();
    }

    public function generatePinCode(): string
    {
        $maxIndex = strlen(self::PIN_CHARSET) - 1;
        $chars = '';
        for ($i = 0; $i < 12; $i++) {
            $chars .= self::PIN_CHARSET[random_int(0, $maxIndex)];
        }
        return substr($chars, 0, 4) . '-' . substr($chars, 4, 4) . '-' . substr($chars, 8, 4);
    }

    public function generateSerial(): string
    {
        $prefix = 'SN' . date('ym');
        $randomHex = strtoupper(bin2hex(random_bytes(3)));
        return $prefix . $randomHex;
    }

    public function normalizePin(string $pinCode): string
    {
        return strtoupper(str_replace(['-', ' '], '', trim($pinCode)));
    }

    public function hashPin(string $pinCode): string
    {
        return hash('sha256', $this->normalizePin($pinCode));
    }

    /**
     * Generate bulk unassigned PINs for card printing.
     * @return ResultAccessPin[]
     */
    public function generateBulkPins(
        int $qty,
        int $maxUses = 5,
        ?int $sessionId = null,
        ?int $termId = null,
        int $adminId = 1
    ): array {
        $qty = max(1, min(1000, $qty));
        $pinsData = [];

        for ($i = 0; $i < $qty; $i++) {
            $rawPin = $this->generatePinCode();
            $serial = $this->generateSerial() . str_pad((string)$i, 2, '0', STR_PAD_LEFT);
            $hash = $this->hashPin($rawPin);

            $pinsData[] = [
                'serial_number' => $serial,
                'pin_code' => $rawPin,
                'pin_hash' => $hash,
                'payment_id' => null,
                'student_id' => null,
                'session_id' => $sessionId,
                'term_id' => $termId,
                'created_by' => $adminId,
                'max_uses' => $maxUses,
            ];
        }

        $this->pinRepository->createBatch($pinsData);
        return $this->pinRepository->getPagedPins(limit: $qty);
    }

    /**
     * Generate 1 PIN for every student enrolled in a class cohort.
     */
    public function generateForClassCohort(
        int $classId,
        int $maxUses = 5,
        ?int $sessionId = null,
        ?int $termId = null,
        int $adminId = 1
    ): int {
        $activeSession = $sessionId ? $this->academicRepository->findSessionById($sessionId) : $this->academicRepository->getCurrentSession();
        $targetSessionId = $activeSession?->id ?? 1;

        $enrollments = $this->enrollmentRepository->getClassRoster($classId, $targetSessionId, 'active');
        if (empty($enrollments)) {
            return 0;
        }

        $pinsData = [];
        $idx = 0;
        foreach ($enrollments as $enr) {
            $rawPin = $this->generatePinCode();
            $serial = $this->generateSerial() . str_pad((string)$idx, 2, '0', STR_PAD_LEFT);
            $hash = $this->hashPin($rawPin);

            $pinsData[] = [
                'serial_number' => $serial,
                'pin_code' => $rawPin,
                'pin_hash' => $hash,
                'payment_id' => null,
                'student_id' => $enr->studentId,
                'session_id' => $targetSessionId,
                'term_id' => $termId,
                'created_by' => $adminId,
                'max_uses' => $maxUses,
            ];
            $idx++;
        }

        return $this->pinRepository->createBatch($pinsData);
    }

    /**
     * Provision an instant PIN upon successful online checkout.
     */
    public function createOnlinePurchasedPin(
        int $paymentId,
        int $studentId,
        int $sessionId,
        int $termId,
        int $userId
    ): ResultAccessPin {
        $rawPin = $this->generatePinCode();
        $serial = $this->generateSerial() . 'ON';
        $hash = $this->hashPin($rawPin);

        return $this->pinRepository->createSinglePin([
            'serial_number' => $serial,
            'pin_code' => $rawPin,
            'pin_hash' => $hash,
            'payment_id' => $paymentId,
            'student_id' => $studentId,
            'session_id' => $sessionId,
            'term_id' => $termId,
            'created_by' => $userId,
            'max_uses' => 5,
        ]);
    }

    /**
     * Verify a submitted scratch-card PIN code against a student admission number.
     * Decrements usage and binds unassigned PIN on first use.
     */
    public function verifyAndConsumePin(
        string $admissionNo,
        string $pinCode,
        ?int $sessionId = null,
        ?int $termId = null
    ): ServiceResult {
        $student = $this->studentRepository->findByAdmissionNumber(trim($admissionNo));
        if (!$student) {
            return ServiceResult::error('No student account found with the provided Admission Number / Student ID.');
        }

        $pin = $this->pinRepository->findByPinCode($pinCode);
        if (!$pin) {
            return ServiceResult::error('Invalid Scratch-Card PIN. Please verify the code and try again.');
        }

        if ($pin->status === ResultAccessPin::STATUS_REVOKED) {
            return ServiceResult::error('This Scratch-Card PIN has been revoked by administration.');
        }

        if ($pin->status === ResultAccessPin::STATUS_DEPLETED || $pin->timesUsed >= $pin->maxUses) {
            return ServiceResult::error("This Scratch-Card PIN has exceeded its maximum allowed views ({$pin->maxUses} uses). Please obtain a new card.");
        }

        // If PIN is already bound to another student, reject!
        if ($pin->studentId !== null && $pin->studentId !== $student->id) {
            return ServiceResult::error('This Scratch-Card PIN is already bound to another student account and cannot be reused.');
        }

        // If PIN is locked to a specific term, verify term match
        if ($pin->termId !== null && $termId !== null && $pin->termId !== $termId) {
            return ServiceResult::error('This Scratch-Card PIN is locked to a different academic term.');
        }

        // Consume one view attempt and bind to student + term if not already bound
        $this->pinRepository->incrementUsage($pin->id, $student->id, $sessionId ?? $pin->sessionId, $termId ?? $pin->termId);

        // Fetch refreshed record
        $refreshedPin = $this->pinRepository->findById($pin->id);

        return ServiceResult::success([
            'pin' => $refreshedPin,
            'student' => $student,
            'remaining_uses' => $refreshedPin->getRemainingUses(),
        ]);
    }

    /**
     * Check if a student already has an active cleared PIN for the session & term.
     */
    public function checkStudentAccess(int $studentId, int $sessionId, int $termId): ?ResultAccessPin
    {
        return $this->pinRepository->findActivePinForStudentAndTerm($studentId, $sessionId, $termId);
    }

    /**
     * Retrieve a PIN entity by its primary ID.
     */
    public function getPinById(int $id): ?ResultAccessPin
    {
        return $this->pinRepository->findById($id);
    }

    /**
     * Consume 1 view for an already-bound active student PIN without re-verifying hash.
     */
    public function consumeBoundPin(int $pinId, int $studentId, ?int $sessionId = null, ?int $termId = null): ?ResultAccessPin
    {
        $this->pinRepository->incrementUsage($pinId, $studentId, $sessionId, $termId);
        return $this->pinRepository->findById($pinId);
    }
}

