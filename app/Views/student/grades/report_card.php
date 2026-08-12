<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Terminal Report Card &mdash; <?= e($student->user?->name ?? 'Student') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }
        @media print {
            body {
                background-color: #ffffff;
                color: #000000;
            }
            .no-print {
                display: none !important;
            }
            .print-container {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
</head>
<body class="p-4 sm:p-8 flex flex-col items-center">

    <!-- Action Bar (No-Print) -->
    <div class="w-full max-w-4xl mb-6 flex items-center justify-between no-print">
        <button onclick="window.history.back()" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 text-xs font-semibold rounded-xl hover:bg-slate-50 transition shadow-sm">
            &larr; Back
        </button>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-600 hover:bg-brand-700 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print / Save as PDF
            </button>
        </div>
    </div>

    <!-- Official Report Card Sheet -->
    <div class="w-full max-w-4xl bg-white border border-slate-200 shadow-xl rounded-2xl p-8 sm:p-10 print-container space-y-6">
        
        <!-- School Header Banner -->
        <div class="text-center pb-6 border-b-2 border-slate-800 flex flex-col items-center">
            <div class="w-16 h-16 rounded-2xl bg-blue-900 text-white flex items-center justify-center font-extrabold text-2xl mb-3 shadow">
                CL
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 uppercase">
                CLARET ACADEMY SECONDARY SCHOOL
            </h1>
            <p class="text-xs text-slate-600 font-medium mt-0.5">
                Excellence, Integrity & Character &bull; Official Student Performance & Progress Report
            </p>
        </div>

        <!-- Student Particulars Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs">
            <div>
                <span class="text-slate-500 font-semibold uppercase text-[10px]">Student Name</span>
                <p class="font-bold text-slate-900 text-sm mt-0.5"><?= e($student->user?->name ?? 'N/A') ?></p>
            </div>
            <div>
                <span class="text-slate-500 font-semibold uppercase text-[10px]">Admission Number</span>
                <p class="font-bold text-slate-900 text-sm mt-0.5"><?= e($student->admissionNumber) ?></p>
            </div>
            <div>
                <span class="text-slate-500 font-semibold uppercase text-[10px]">Academic Session</span>
                <p class="font-bold text-slate-900 text-sm mt-0.5"><?= e($session?->name ?? 'N/A') ?></p>
            </div>
            <div>
                <span class="text-slate-500 font-semibold uppercase text-[10px]">Academic Term</span>
                <p class="font-bold text-slate-900 text-sm mt-0.5"><?= e($term?->name ?? 'N/A') ?></p>
            </div>
            <div>
                <span class="text-slate-500 font-semibold uppercase text-[10px]">Class</span>
                <p class="font-bold text-slate-900 mt-0.5"><?= e($summary?->class?->name ?? 'N/A') ?></p>
            </div>
            <div>
                <span class="text-slate-500 font-semibold uppercase text-[10px]">Class Position / Rank</span>
                <p class="font-bold text-blue-800 text-sm mt-0.5">
                    <?= $summary && $summary->rankInClass ? "#{$summary->rankInClass}" : 'N/A' ?>
                </p>
            </div>
            <div>
                <span class="text-slate-500 font-semibold uppercase text-[10px]">Term Average</span>
                <p class="font-bold text-slate-900 text-sm mt-0.5">
                    <?= $summary ? number_format((float)$summary->averageScore, 2) . '%' : 'N/A' ?>
                </p>
            </div>
            <div>
                <span class="text-slate-500 font-semibold uppercase text-[10px]">Cumulative GPA</span>
                <p class="font-bold text-slate-900 text-sm mt-0.5">
                    <?= $summary && $summary->gpa !== null ? number_format((float)$summary->gpa, 2) : 'N/A' ?>
                </p>
            </div>
        </div>

        <!-- Subject Scores Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse border border-slate-300">
                <thead>
                    <tr class="bg-slate-100 text-slate-800 font-bold uppercase border-b border-slate-300">
                        <th class="py-2.5 px-3 border-r border-slate-300">Subject</th>
                        <th class="py-2.5 px-2 text-center border-r border-slate-300">Total (100%)</th>
                        <th class="py-2.5 px-2 text-center border-r border-slate-300">Grade</th>
                        <th class="py-2.5 px-2 text-center border-r border-slate-300">Grade Point</th>
                        <th class="py-2.5 px-3">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-900 font-medium">
                    <?php if (empty($subject_results)): ?>
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-400">
                                No subject records available for this term report.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subject_results as $res): ?>
                            <tr>
                                <td class="py-2 px-3 font-bold border-r border-slate-200">
                                    <?= e($res->classSubject?->subject?->name ?? 'Subject') ?>
                                </td>
                                <td class="py-2 px-2 text-center font-bold border-r border-slate-200">
                                    <?= number_format($res->computedScore, 2) ?>%
                                </td>
                                <td class="py-2 px-2 text-center font-extrabold border-r border-slate-200">
                                    <?= e($res->gradeLetter) ?>
                                </td>
                                <td class="py-2 px-2 text-center border-r border-slate-200">
                                    <?= $res->gradePoint !== null ? number_format($res->gradePoint, 2) : '&mdash;' ?>
                                </td>
                                <td class="py-2 px-3 text-slate-700">
                                    <?= e($res->remark ?? 'Pass') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Remarks & Signatures Section -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-4 border-t border-slate-200 text-xs">
            <div class="space-y-3 p-4 bg-slate-50 border border-slate-200 rounded-xl">
                <h4 class="font-bold text-slate-800 uppercase text-[11px]">Class Teacher's Assessment</h4>
                <p class="italic text-slate-700 text-xs min-h-[32px]">
                    <?= e($summary?->classTeacherRemark ?? 'Satisfactory academic effort and continuous improvement shown this term.') ?>
                </p>
                <div class="pt-3 border-t border-slate-200 flex justify-between items-center text-[10px] text-slate-500">
                    <span>Teacher's Signature</span>
                    <span class="font-mono">Verified Digital Record</span>
                </div>
            </div>

            <div class="space-y-3 p-4 bg-slate-50 border border-slate-200 rounded-xl">
                <h4 class="font-bold text-slate-800 uppercase text-[11px]">Principal's Final Remark</h4>
                <p class="italic text-slate-700 text-xs min-h-[32px]">
                    <?= e($summary?->principalRemark ?? 'Promising performance. Encouraged to sustain standard in subsequent terms.') ?>
                </p>
                <div class="pt-3 border-t border-slate-200 flex justify-between items-center text-[10px] text-slate-500">
                    <span>Principal's Seal & Signature</span>
                    <span class="font-mono">Approved</span>
                </div>
            </div>
        </div>

        <!-- Grading Key Footer -->
        <div class="pt-4 border-t border-slate-200 text-[10px] text-slate-500 flex flex-wrap justify-between items-center gap-2">
            <div>
                <strong>Grading Scale:</strong> A (70-100% / 5.0) &bull; B (60-69% / 4.0) &bull; C (50-59% / 3.0) &bull; D (45-49% / 2.0) &bull; E (40-44% / 1.0) &bull; F (0-39% / 0.0)
            </div>
            <div>
                Report Generated on: <?= e($generated_at) ?>
            </div>
        </div>

    </div>

</body>
</html>
