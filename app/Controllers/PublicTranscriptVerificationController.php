<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\TranscriptService;

/**
 * Public Portal Controller for Verifying Student Transcripts via Security Reference / QR Code
 */
class PublicTranscriptVerificationController extends Controller
{
    private TranscriptService $transcriptService;

    public function __construct(?TranscriptService $transcriptService = null)
    {
        $this->transcriptService = $transcriptService ?? new TranscriptService();
    }

    public function verify(Request $request, string|int $reference = ''): Response
    {
        $ref = is_array($reference) ? (string)($reference['reference'] ?? '') : (string)$reference;
        $ref = strtoupper(trim($ref));

        $verification = $this->transcriptService->verifyTranscriptReference($ref);

        if (!$verification) {
            return $this->view('public/transcript_verify', [
                'title' => 'Transcript Verification — Claret LMS',
                'isValid' => false,
                'reference' => $ref,
                'verification' => null,
            ]);
        }

        return $this->view('public/transcript_verify', [
            'title' => "Transcript Verified — {$verification['student_name']} — Claret LMS",
            'isValid' => true,
            'reference' => $ref,
            'verification' => $verification,
        ]);
    }
}
