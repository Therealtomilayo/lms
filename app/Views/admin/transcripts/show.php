<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Academic Transcript — Claret LMS',
    'headerTitle' => 'Student Academic Transcript'
]);

$st = $transcript['student'];
$cum = $transcript['cumulative_stats'];
$ver = $transcript['verification'];
$sch = $transcript['school'];
$sessions = $transcript['sessions'];
?>

<!-- Document Action Toolbar (Hidden during print) -->
<div class="print:hidden space-y-4 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-slate-200 rounded-xl p-4 shadow-xs">
        <div class="flex items-center gap-3">
            <a href="/admin/transcripts" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Directory
            </a>
            <div class="h-4 w-px bg-slate-200"></div>
            <div>
                <span class="text-xs text-slate-500">Student Dossier:</span>
                <span class="text-sm font-bold text-slate-900 ml-1"><?= e($st['name']) ?></span>
                <span class="text-xs font-mono text-slate-500">(<?= e($st['admission_number']) ?>)</span>
            </div>
        </div>

        <!-- Scope & Control Tools -->
        <div class="flex items-center gap-2.5 flex-wrap">
            <form method="GET" action="/admin/transcripts/<?= $st['id'] ?>" class="flex items-center gap-2">
                <label for="scope_select" class="text-xs font-semibold text-slate-600">Curricular Scope:</label>
                <select name="scope" id="scope_select" onchange="this.form.submit()" 
                        class="text-xs font-medium py-1.5 px-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="all" <?= ($scope ?? 'all') === 'all' ? 'selected' : '' ?>>Full Career (All Sessions)</option>
                    <option value="junior_secondary" <?= ($scope ?? '') === 'junior_secondary' ? 'selected' : '' ?>>Junior Secondary (JSS 1–3)</option>
                    <option value="senior_secondary" <?= ($scope ?? '') === 'senior_secondary' ? 'selected' : '' ?>>Senior Secondary (SS 1–3)</option>
                    <option value="primary" <?= ($scope ?? '') === 'primary' ? 'selected' : '' ?>>Primary School (Basic 1–5)</option>
                </select>
                <input type="hidden" name="watermark" value="<?= $showWatermark ? '1' : '0' ?>">
                <input type="hidden" name="remarks" value="<?= $showRemarks ? '1' : '0' ?>">
            </form>

            <button type="button" onclick="window.print()" 
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-lg bg-brand-600 text-white hover:bg-brand-700 shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print / Save as PDF
            </button>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- OFFICIAL A4 ACADEMIC TRANSCRIPT DOSSIER SHEET (Print & Screen Optimized)   -->
<!-- ========================================================================= -->
<div class="transcript-dossier-container max-w-5xl mx-auto bg-white border border-slate-300 rounded-2xl shadow-xl overflow-hidden print:border-none print:shadow-none print:max-w-none print:w-full print:rounded-none">
    
    <div class="relative p-8 md:p-12 print:p-6 text-slate-900 overflow-hidden font-sans">
        
        <!-- Institutional Crest Watermark Seal -->
        <?php if ($showWatermark): ?>
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-[0.035] select-none z-0">
                <img src="<?= e($sch['logo']) ?>" alt="Watermark Seal" class="w-[500px] h-[500px] object-contain grayscale">
            </div>
        <?php endif; ?>

        <div class="relative z-10 space-y-6">

            <!-- 1. Header Section: Crest, School Identity, and Verification QR -->
            <div class="flex items-start justify-between border-b-2 border-slate-900 pb-5 gap-6">
                <!-- School Crest -->
                <div class="w-24 h-24 shrink-0 flex items-center justify-center">
                    <img src="<?= e($sch['logo']) ?>" alt="School Crest" class="w-20 h-20 object-contain drop-shadow-xs">
                </div>

                <!-- School Info Center -->
                <div class="text-center flex-1 space-y-1">
                    <h1 class="text-2xl md:text-3xl font-black uppercase tracking-wide text-slate-900">
                        <?= e($sch['name']) ?>
                    </h1>
                    <p class="text-xs font-bold uppercase tracking-widest text-brand-700 italic">
                        &ldquo;<?= e($sch['motto']) ?>&rdquo;
                    </p>
                    <p class="text-[11px] text-slate-600 leading-relaxed max-w-xl mx-auto">
                        <?= e($sch['address']) ?>
                    </p>
                    <div class="flex flex-wrap items-center justify-center gap-x-4 text-[10px] text-slate-500 font-medium pt-1">
                        <span>Tel: <?= e($sch['phone']) ?></span>
                        <span>&bull;</span>
                        <span>Email: <?= e($sch['email']) ?></span>
                        <span>&bull;</span>
                        <span>Web: <?= e($sch['website']) ?></span>
                    </div>
                </div>

                <!-- Verification QR Code & Reference -->
                <div class="w-28 text-center shrink-0 flex flex-col items-center">
                    <div class="p-1 bg-white border border-slate-200 rounded-md shadow-2xs">
                        <?= $ver['qr_code_svg'] ?>
                    </div>
                    <span class="text-[8px] font-mono font-bold text-slate-600 uppercase tracking-tighter mt-1 block">
                        <?= e($ver['reference']) ?>
                    </span>
                    <span class="text-[7.5px] text-slate-400 block uppercase">Official QR Verify</span>
                </div>
            </div>

            <!-- 2. Document Title Banner -->
            <div class="text-center space-y-1">
                <div class="inline-block bg-slate-900 text-white px-6 py-1.5 rounded-sm">
                    <h2 class="text-base md:text-lg font-black uppercase tracking-wider">
                        Official Cumulative Academic Transcript
                    </h2>
                </div>
                <div class="flex items-center justify-center gap-2 text-xs font-semibold text-slate-600 pt-0.5">
                    <span>Scope: <strong class="text-slate-900 uppercase"><?= e(str_replace('_', ' ', $scope ?? 'All Sessions')) ?></strong></span>
                    <span>&bull;</span>
                    <span>Issue Date: <strong class="text-slate-900"><?= e($ver['issued_at']) ?></strong></span>
                </div>
            </div>

            <!-- 3. Student Bio-Data Matrix -->
            <div class="border border-slate-300 rounded-lg overflow-hidden text-xs bg-slate-50/40">
                <div class="bg-slate-100/80 px-4 py-1.5 border-b border-slate-200 font-bold uppercase text-[10px] tracking-wider text-slate-700">
                    Student Identification & Career Record
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 divide-y sm:divide-y-0 divide-x divide-slate-200">
                    <div class="p-3">
                        <span class="text-[10px] text-slate-500 uppercase block font-semibold">Student Full Name</span>
                        <span class="font-bold text-slate-900 text-sm"><?= e($st['name']) ?></span>
                    </div>
                    <div class="p-3">
                        <span class="text-[10px] text-slate-500 uppercase block font-semibold">Admission / Reg. No.</span>
                        <span class="font-bold font-mono text-slate-900 text-sm"><?= e($st['admission_number']) ?></span>
                    </div>
                    <div class="p-3">
                        <span class="text-[10px] text-slate-500 uppercase block font-semibold">Gender / Sex</span>
                        <span class="font-semibold text-slate-800"><?= e($st['gender']) ?></span>
                    </div>
                    <div class="p-3">
                        <span class="text-[10px] text-slate-500 uppercase block font-semibold">Date of Birth</span>
                        <span class="font-semibold text-slate-800"><?= e($st['date_of_birth']) ?></span>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 divide-y sm:divide-y-0 divide-x divide-slate-200 border-t border-slate-200">
                    <div class="p-3">
                        <span class="text-[10px] text-slate-500 uppercase block font-semibold">State of Origin</span>
                        <span class="font-semibold text-slate-800"><?= e($st['state_of_origin']) ?></span>
                    </div>
                    <div class="p-3">
                        <span class="text-[10px] text-slate-500 uppercase block font-semibold">Date of Admission</span>
                        <span class="font-semibold text-slate-800"><?= e($st['admission_date']) ?></span>
                    </div>
                    <div class="p-3">
                        <span class="text-[10px] text-slate-500 uppercase block font-semibold">Last Cohort Attended</span>
                        <span class="font-semibold text-slate-800"><?= e($st['current_class']) ?></span>
                    </div>
                    <div class="p-3">
                        <span class="text-[10px] text-slate-500 uppercase block font-semibold">Academic Status</span>
                        <span class="font-bold text-emerald-700 uppercase"><?= e($st['status']) ?></span>
                    </div>
                </div>
            </div>

            <!-- 4. Historical Academic Sessions Progression -->
            <?php if (empty($sessions)): ?>
                <div class="border border-slate-200 rounded-lg p-8 text-center text-slate-500 text-sm">
                    No academic session score records available for the selected scope.
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($sessions as $sessIdx => $sess): ?>
                        <div class="border border-slate-300 rounded-lg overflow-hidden break-inside-avoid shadow-2xs">
                            <!-- Session Header -->
                            <div class="bg-slate-800 text-white px-4 py-2 flex items-center justify-between flex-wrap gap-2 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-sm uppercase tracking-wider"><?= e($sess['session_name']) ?> Academic Session</span>
                                    <span class="bg-slate-700 text-slate-200 px-2 py-0.5 rounded text-[10px] uppercase font-bold">
                                        <?= e($sess['class_name']) ?> &bull; <?= e(ucwords(str_replace('_', ' ', $sess['stage']))) ?>
                                    </span>
                                </div>
                                <div class="text-[11px] font-medium text-slate-300">
                                    Session Average: <strong class="text-white font-mono"><?= number_format($sess['session_average'], 1) ?>%</strong>
                                </div>
                            </div>

                            <!-- Terms in this session -->
                            <div class="divide-y divide-slate-200">
                                <?php foreach ($sess['terms'] as $term): ?>
                                    <div class="p-4 space-y-2.5">
                                        <div class="flex items-center justify-between text-xs border-b border-slate-200 pb-1.5">
                                            <h4 class="font-bold text-slate-800 uppercase tracking-tight flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                                                <?= e($term['term_name']) ?>
                                            </h4>
                                            <div class="flex items-center gap-3 text-[11px] text-slate-600 font-medium">
                                                <?php if (!empty($term['class_rank'])): ?>
                                                    <span>Cohort Rank: <strong class="text-slate-900 font-mono">#<?= e((string)$term['class_rank']) ?></strong></span>
                                                <?php endif; ?>
                                                <?php if (!empty($term['attendance'])): ?>
                                                    <span>Attendance: <strong class="text-slate-900 font-mono"><?= e($term['attendance']) ?></strong></span>
                                                <?php endif; ?>
                                                <span>Term Average: <strong class="text-brand-700 font-mono font-bold"><?= number_format($term['term_average'], 1) ?>%</strong></span>
                                            </div>
                                        </div>

                                        <!-- Term Subjects Table -->
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-left text-xs border-collapse">
                                                <thead>
                                                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase text-[10px]">
                                                        <th class="py-1.5 px-3 w-1/2">Subject Title</th>
                                                        <th class="py-1.5 px-3 text-center">Marks Obtained (/100)</th>
                                                        <th class="py-1.5 px-3 text-center">Letter Grade</th>
                                                        <th class="py-1.5 px-3">Academic Remark</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100 text-slate-800">
                                                    <?php foreach ($term['subjects'] as $sub): ?>
                                                        <tr class="hover:bg-slate-50/50">
                                                            <td class="py-1.5 px-3 font-medium text-slate-900">
                                                                <?= e($sub['name']) ?>
                                                            </td>
                                                            <td class="py-1.5 px-3 text-center font-mono font-bold">
                                                                <?= number_format($sub['score'], 1) ?>
                                                            </td>
                                                            <td class="py-1.5 px-3 text-center font-black">
                                                                <span class="inline-block px-2 py-0.5 rounded text-[11px] bg-slate-100 text-slate-800">
                                                                    <?= e($sub['grade']) ?>
                                                                </span>
                                                            </td>
                                                            <td class="py-1.5 px-3 text-[11px] font-medium text-slate-600">
                                                                <?= e($sub['remark']) ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="bg-slate-50/80 font-bold border-t border-slate-200 text-slate-700 text-[11px]">
                                                        <td class="py-1.5 px-3">Term Total Marks Obtained:</td>
                                                        <td class="py-1.5 px-3 text-center font-mono font-bold text-slate-900">
                                                            <?= number_format($term['term_total_marks'], 1) ?> / <?= number_format($term['term_max_marks'], 0) ?>
                                                        </td>
                                                        <td class="py-1.5 px-3 text-center text-slate-500 font-normal text-[10px]">Term Average:</td>
                                                        <td class="py-1.5 px-3 font-mono text-brand-700">
                                                            <?= number_format($term['term_average'], 1) ?>%
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>

                                        <?php if ($showRemarks && (!empty($term['teacher_remark']) || !empty($term['principal_remark']))): ?>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 text-[11px] border-t border-slate-100">
                                                <?php if (!empty($term['teacher_remark'])): ?>
                                                    <p class="text-slate-600 italic">
                                                        <strong class="font-semibold not-italic text-slate-700">Homeroom Remark:</strong> <?= e($term['teacher_remark']) ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if (!empty($term['principal_remark'])): ?>
                                                    <p class="text-slate-600 italic">
                                                        <strong class="font-semibold not-italic text-slate-700">Principal's Note:</strong> <?= e($term['principal_remark']) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- 5. Cumulative Career Performance Summary Ribbon -->
            <div class="border-2 border-slate-900 rounded-lg overflow-hidden break-inside-avoid">
                <div class="bg-slate-900 text-white px-4 py-2 font-black uppercase text-xs tracking-wider flex items-center justify-between">
                    <span>Cumulative Academic Career Standing (SRS §26, §51)</span>
                    <span class="text-[10px] font-medium text-slate-300">Certified Official Metric</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-5 divide-y sm:divide-y-0 divide-x divide-slate-200 bg-slate-50/60 text-center">
                    <div class="p-3">
                        <span class="text-[10px] uppercase font-bold text-slate-500 block">Total Sessions</span>
                        <span class="text-lg font-black font-mono text-slate-900"><?= $cum['total_sessions'] ?></span>
                    </div>
                    <div class="p-3">
                        <span class="text-[10px] uppercase font-bold text-slate-500 block">Terms Evaluated</span>
                        <span class="text-lg font-black font-mono text-slate-900"><?= $cum['total_terms'] ?></span>
                    </div>
                    <div class="p-3">
                        <span class="text-[10px] uppercase font-bold text-slate-500 block">Total Subjects</span>
                        <span class="text-lg font-black font-mono text-slate-900"><?= $cum['total_subjects'] ?></span>
                    </div>
                    <div class="p-3">
                        <span class="text-[10px] uppercase font-bold text-slate-500 block">Marks Obtained</span>
                        <span class="text-base font-black font-mono text-slate-900">
                            <?= number_format($cum['total_marks_obtained'], 1) ?> <span class="text-xs text-slate-500">/ <?= number_format($cum['total_max_marks'], 0) ?></span>
                        </span>
                    </div>
                    <div class="p-3 bg-brand-50/50">
                        <span class="text-[10px] uppercase font-bold text-brand-800 block">Cumulative Average</span>
                        <span class="text-xl font-black font-mono text-brand-700">
                            <?= number_format($cum['cumulative_average'], 2) ?>%
                        </span>
                    </div>
                </div>
                <div class="bg-white px-4 py-2.5 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-700 uppercase">Final Academic Honors / Standing:</span>
                        <span class="px-3 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 uppercase tracking-wide">
                            <?= e($cum['honors_classification']) ?>
                        </span>
                    </div>
                    <div class="text-[11px] text-slate-500 italic">
                        *Cumulative assessment standard excludes Grade Points in accordance with institutional policy.
                    </div>
                </div>
            </div>

            <!-- 6. Institutional Certification & Security Verification Sign-offs -->
            <div class="border border-slate-300 rounded-lg p-5 break-inside-avoid space-y-4 bg-slate-50/30">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 items-end">
                    <!-- Head of School -->
                    <div class="text-center space-y-1">
                        <div class="h-12 flex items-center justify-center">
                            <?php if (!empty($sch['head_teacher_signature_url'])): ?>
                                <img src="<?= e($sch['head_teacher_signature_url']) ?>" alt="Signature" class="max-h-12 object-contain">
                            <?php else: ?>
                                <div class="border-b border-slate-400 w-36 mx-auto"></div>
                            <?php endif; ?>
                        </div>
                        <p class="font-bold text-xs text-slate-900 uppercase"><?= e($sch['head_teacher_name']) ?></p>
                        <p class="text-[10px] text-slate-500 uppercase tracking-wider"><?= e($sch['head_teacher_title']) ?></p>
                    </div>

                    <!-- Registrar / Stamp Overlay -->
                    <div class="text-center space-y-1">
                        <div class="h-12 flex items-center justify-center relative">
                            <?php if (!empty($sch['school_stamp_url'])): ?>
                                <img src="<?= e($sch['school_stamp_url']) ?>" alt="Stamp" class="max-h-14 object-contain opacity-80">
                            <?php else: ?>
                                <div class="w-14 h-14 rounded-full border-2 border-dashed border-slate-400 flex items-center justify-center text-[8px] font-bold uppercase text-slate-400">
                                    Official Stamp
                                </div>
                            <?php endif; ?>
                        </div>
                        <p class="font-bold text-xs text-slate-900 uppercase">Office of the Registrar</p>
                        <p class="text-[10px] text-slate-500 uppercase tracking-wider">Certification of Record</p>
                    </div>

                    <!-- Digital Verification Reference -->
                    <div class="text-center sm:text-right space-y-1">
                        <div class="text-[10px] font-mono text-slate-500 uppercase">Verification Reference:</div>
                        <div class="text-xs font-mono font-black text-slate-900"><?= e($ver['reference']) ?></div>
                        <div class="text-[9px] text-slate-400">
                            Validate via: <a href="<?= e($ver['url']) ?>" class="underline text-brand-600" target="_blank"><?= e($ver['url']) ?></a>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-200 text-center">
                    <p class="text-[9px] text-slate-500 uppercase tracking-tight">
                        Security Notice: This document is an official academic transcript issued by Claret International School. Any alteration, erasure, or unauthorized issuance renders this document invalid. Authenticate authenticity by scanning the embedded QR code.
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    @media print {
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        body {
            background-color: #ffffff !important;
            color: #0f172a !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .transcript-dossier-container {
            border: none !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        .break-inside-avoid {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
    }
</style>
