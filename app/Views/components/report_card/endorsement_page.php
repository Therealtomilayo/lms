<?php
/**
 * Claret International School Report Card Dossier — Page 3 (Endorsement & Promotion Page)
 * Exact replica of Claret report card sample.pdf Page 3 with dynamic institutional parameters
 */

$studentUser = $student->user ?? null;
$studentFullName = strtoupper($studentUser?->name ?? $student->name ?? 'STUDENT');
$resolvedClass = $class_full_name ?? ($class ? (method_exists($class, 'getFullName') ? $class->getFullName() : ($class->name . (!empty($class->sectionArm) ? ' (' . $class->sectionArm . ')' : ''))) : 'Class Assigned');
$homeroom = $resolvedClass;
$resolvedTeacher = $formTeacher ?? $form_teacher ?? null;
$teacherName = !empty($resolvedTeacher?->name) ? $resolvedTeacher->name : 'Class Teacher';

$teacherComment = !empty($summary?->teacherComment) 
    ? $summary->teacherComment 
    : 'A very brilliant, hardworking and well-behaved pupil. Shows great enthusiasm in classroom participation and exhibits high moral and intellectual standard.';

$principalComment = !empty($summary?->principalComment) 
    ? $summary->principalComment 
    : 'An outstanding and commendable performance. Keep maintaining this exemplary standard of excellence!';

$resumptionDate = $next_resumption_date ?? '7th SEPTEMBER, 2026';

// Calculate aggregate summary metrics
$totalScore = (float)($summary?->totalScore ?? 0.0);
$averageScore = (float)($summary?->averageScore ?? 0.0);
$classPosition = $summary?->classPosition ?? '1st';
$overallGrade = $summary?->gradeLetter ?? 'A';

// Calculate totals from subjects if summary is 0
if ($totalScore <= 0 && !empty($subject_analytics)) {
    foreach ($subject_analytics as $sub) {
        $totalScore += (float)($sub['pupil_score'] ?? 0);
    }
    $subCount = count($subject_analytics);
    if ($subCount > 0) {
        $averageScore = round($totalScore / $subCount, 1);
    }
}
$maxPossible = count($subject_analytics ?? []) * 100;
if ($maxPossible === 0) {
    $maxPossible = 1000;
}

$promotionStatus = $promotion['status'] ?? 'promoted';
$promotionBadge = $promotion['badge_text'] ?? 'PROMOTED';
$promotionNote = $promotion['note'] ?? 'Successfully satisfied promotion standards. Promoted to next class level.';
$isPromoVisible = !empty($is_promotion_visible) && !empty($is_final_term);
?>

<div class="report-page page-3 relative flex flex-col justify-between overflow-hidden bg-white text-slate-800 p-8 print:p-6">
    
    <!-- Watermark Background -->
    <div class="watermark-bg"></div>

    <div class="relative z-10 flex flex-col gap-4">
        
        <!-- Top Institutional Header -->
        <div class="flex items-center justify-between border-b-2 border-[#7B3046] pb-2">
            <div class="flex items-center gap-3">
                <img src="<?= htmlspecialchars(!empty($school['logo']) ? $school['logo'] : '/assets/img/logo.png') ?>" alt="Claret Logo" class="h-12 w-auto object-contain">
                <div>
                    <h2 class="text-sm font-black tracking-wider text-[#7B3046] uppercase font-sans leading-none">
                        <?= htmlspecialchars($school['name'] ?? 'Claret International School') ?>
                    </h2>
                    <p class="text-[9.5px] font-bold text-slate-600 uppercase tracking-tight mt-0.5">
                        Attestation, Appraisal & Cumulative Annual Performance Record
                    </p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-[9.5px] font-black text-slate-900 uppercase">
                    <?= $studentFullName ?>
                </div>
                <div class="text-[8.5px] font-semibold text-slate-500">
                    Adm: <span class="font-mono text-slate-800 font-bold"><?= htmlspecialchars($admissionNo) ?></span> &bull; <?= htmlspecialchars($homeroom) ?>
                </div>
            </div>
        </div>

        <!-- Academic Performance Summary Ribbon -->
        <div class="grid grid-cols-4 gap-2.5 bg-gradient-to-r from-pink-50/50 via-slate-50 to-pink-50/50 border border-[#E28BE2] rounded-lg p-3 text-center shadow-xs">
            <div class="border-r border-pink-100 pr-2">
                <span class="block text-[8px] font-bold text-slate-500 uppercase tracking-wider">Total Score</span>
                <span class="text-base font-black text-slate-900 font-mono"><?= number_format($totalScore, 1) ?></span>
                <span class="text-[8px] text-slate-400 block">/ <?= $maxPossible ?></span>
            </div>
            <div class="border-r border-pink-100 pr-2">
                <span class="block text-[8px] font-bold text-slate-500 uppercase tracking-wider">Average Score</span>
                <span class="text-base font-black text-[#7B3046] font-mono"><?= number_format($averageScore, 1) ?>%</span>
                <span class="text-[8px] font-semibold text-emerald-600 block">Grade <?= $overallGrade ?></span>
            </div>
            <div class="border-r border-pink-100 pr-2">
                <span class="block text-[8px] font-bold text-slate-500 uppercase tracking-wider">Class Standing</span>
                <span class="text-base font-black text-slate-900"><?= htmlspecialchars($classPosition) ?></span>
                <span class="text-[8px] text-slate-400 block">Out of <?= $demographics['total'] ?? 13 ?></span>
            </div>
            <div>
                <span class="block text-[8px] font-bold text-slate-500 uppercase tracking-wider">Academic Standing</span>
                <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">
                    EXCELLENT
                </span>
            </div>
        </div>

        <!-- Section 1: Form Teacher's Appraisal Card -->
        <div class="border border-slate-300 rounded-lg overflow-hidden bg-white/95 shadow-xs">
            <div class="bg-[#FFABFF] text-[#2A0845] font-black text-[9px] uppercase px-3 py-1 tracking-wider flex items-center justify-between border-b border-[#E28BE2]">
                <span>Form Teacher's General Remarks & Appraisal</span>
                <span class="text-[8px] text-[#4A154B] font-normal italic">Rapport de l'enseignant</span>
            </div>
            <div class="p-4 flex flex-col justify-between min-h-[110px]">
                <div class="handwriting-text text-base text-blue-900 leading-relaxed tracking-wide italic">
                    &ldquo;<?= htmlspecialchars($teacherComment) ?>&rdquo;
                </div>
                <div class="mt-4 pt-2 border-t border-dashed border-slate-200 flex items-end justify-between">
                    <div>
                        <div class="text-[8px] font-bold text-slate-500 uppercase">Form Teacher:</div>
                        <div class="text-[9.5px] font-extrabold text-slate-900"><?= htmlspecialchars($teacherName) ?></div>
                    </div>
                    <div class="text-center">
                        <div class="border-b border-slate-400 w-32 pb-0.5 font-serif italic text-xs text-blue-900 font-bold"><?= htmlspecialchars($teacherName) ?></div>
                        <div class="text-[7.5px] font-semibold text-slate-500 pt-0.5">Signature</div>
                    </div>
                    <div class="text-right">
                        <div class="text-[8px] font-bold text-slate-500 uppercase">Date:</div>
                        <div class="text-[9px] font-bold text-slate-800 font-mono"><?= date('d/m/Y') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Head Teacher's Appraisal & Official Promotion Decision -->
        <div class="border-2 border-pink-300 rounded-lg overflow-hidden bg-white/95 shadow-xs relative">
            <div class="bg-[#FFABFF] text-[#2A0845] font-black text-[9px] uppercase px-3 py-1 tracking-wider flex items-center justify-between border-b border-[#E28BE2]">
                <span>Head Teacher's Endorsement & Institutional Seal</span>
                <span class="text-[8px] text-[#4A154B] font-normal italic">Décision de la Direction</span>
            </div>
            
            <div class="p-4 flex flex-col gap-3.5 relative">
                
                <!-- Comment -->
                <div>
                    <div class="text-[8px] font-bold text-slate-500 uppercase tracking-wider mb-1">Administrative Evaluation:</div>
                    <div class="handwriting-text text-base text-blue-950 leading-relaxed italic">
                        &ldquo;<?= htmlspecialchars($principalComment) ?>&rdquo;
                    </div>
                </div>

                <!-- Promotion Decision Banner (Only shown on 3rd Term / Final Term) -->
                <?php if ($isPromoVisible): ?>
                    <div class="rounded-md border-2 border-emerald-500/70 bg-gradient-to-r from-emerald-50 via-teal-50 to-emerald-50 p-3 shadow-xs">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-black text-sm flex-shrink-0 shadow-xs">
                                &#10003;
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-[9px] font-bold text-emerald-800 uppercase tracking-widest">Official Decision:</span>
                                    <span class="text-xs font-black uppercase tracking-wider px-2 py-0.5 bg-emerald-600 text-white rounded">
                                        <?= htmlspecialchars($promotionBadge) ?>
                                    </span>
                                </div>
                                <p class="text-[8.5px] font-semibold text-emerald-950 mt-0.5">
                                    <?= htmlspecialchars($promotionNote) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Next Term Resumption Date Box -->
                <div class="rounded border border-amber-300 bg-amber-50/80 p-2.5 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-sm">&#128197;</span>
                        <div>
                            <span class="text-[8px] font-bold text-amber-800 uppercase tracking-wider block">Next Term Resumption Date</span>
                            <span class="text-xs font-black text-amber-950 uppercase tracking-wide">
                                <?= htmlspecialchars($resumptionDate) ?>
                            </span>
                        </div>
                    </div>
                    <span class="text-[8px] font-bold text-amber-800 uppercase tracking-widest bg-amber-200/70 px-2 py-1 rounded">
                        Mandatory Return
                    </span>
                </div>

                <!-- Signatures & Stamp Layer (Realistic positioning) -->
                <div class="mt-2 pt-2 border-t border-dashed border-slate-200 relative flex items-end justify-between">
                    <div>
                        <div class="text-[8px] font-bold text-slate-500 uppercase">Head of School:</div>
                        <div class="text-[10px] font-black text-slate-900">
                            <?= htmlspecialchars($school['head_teacher_name'] ?? 'Head of School') ?> 
                            <?php if (!empty($school['head_teacher_title'])): ?>
                                &bull; <?= htmlspecialchars($school['head_teacher_title']) ?>
                            <?php endif; ?>
                        </div>
                        <div class="text-[7.5px] font-medium text-slate-500">
                            <?= htmlspecialchars($school['name'] ?? 'Claret International School') ?>
                        </div>
                    </div>

                    <!-- Official Stamp Overlay -->
                    <?php if (!empty($school['school_stamp_url'])): ?>
                        <div class="absolute left-1/2 -top-6 -translate-x-1/2 pointer-events-none z-20">
                            <img src="<?= htmlspecialchars($school['school_stamp_url']) ?>" 
                                 alt="Official School Seal" 
                                 class="h-28 w-28 object-contain rotate-[-6deg] drop-shadow-md mix-blend-multiply opacity-95">
                        </div>
                    <?php endif; ?>

                    <div class="text-right">
                        <?php if (!empty($school['head_teacher_signature_url'])): ?>
                            <div class="h-10 w-32 flex items-end justify-center mx-auto mb-0.5">
                                <img src="<?= htmlspecialchars($school['head_teacher_signature_url']) ?>" alt="Head Teacher Signature" class="max-h-10 max-w-32 object-contain mix-blend-multiply">
                            </div>
                        <?php else: ?>
                            <div class="border-b border-slate-500 w-32 pb-0.5 text-center font-serif italic text-xs text-blue-900 font-bold">
                                <?= htmlspecialchars($school['head_teacher_name'] ?? 'Authorized Signatory') ?>
                            </div>
                        <?php endif; ?>
                        <div class="border-t border-slate-300 text-[7.5px] font-semibold text-slate-500 pt-0.5 text-center">Head Teacher Signature</div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Institutional Footer & Verification Notice -->
    <div class="relative z-10 text-center border-t border-slate-300 pt-2 text-[7.5px] text-slate-500">
        <p class="font-bold text-slate-700 uppercase tracking-wider">
            <?= htmlspecialchars($school['name'] ?? 'Claret International School') ?> &bull; <?= htmlspecialchars($school['address'] ?? 'Plot 700 Gitto Street, Mabushi, Abuja, Nigeria.') ?>
        </p>
        <p class="mt-0.5 leading-relaxed text-[7px] text-slate-400">
            This document is a certified official academic dossier issued by <?= htmlspecialchars($school['name'] ?? 'Claret International School') ?>. Any unauthorized alteration, erasure or mutilation renders this result void.
        </p>
        <div class="flex items-center justify-between mt-1 text-[7px] text-slate-400">
            <span>Generated via Claret Secure Portal</span>
            <span class="font-mono">Page 3 of 3</span>
        </div>
    </div>

</div>
