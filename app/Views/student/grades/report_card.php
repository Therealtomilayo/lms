<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Terminal Report Card &mdash; <?= htmlspecialchars($student->user?->name ?? 'Student') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
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
        <a href="/student/grades" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 text-xs font-bold rounded-xl hover:bg-slate-50 transition shadow-xs">
            &larr; Back to Gradebook
        </a>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Print / Save as PDF</span>
            </button>
        </div>
    </div>

    <!-- Official Report Card Sheet -->
    <div class="w-full max-w-4xl bg-white border border-slate-200 shadow-xl rounded-2xl p-8 sm:p-10 print-container space-y-6">
        
        <!-- School Header Banner -->
        <div class="text-center pb-6 border-b-2 border-slate-900 flex flex-col items-center">
            <div class="w-14 h-14 rounded-2xl bg-sky-900 text-white flex items-center justify-center font-extrabold text-xl mb-2 shadow-xs">
                LMS
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 uppercase">
                CLARET ACADEMY SECONDARY SCHOOL
            </h1>
            <p class="text-xs text-slate-500 font-semibold mt-0.5">
                Official Student Terminal Academic Performance & Progress Report
            </p>
        </div>

        <!-- Student Particulars Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs">
            <div>
                <span class="text-slate-400 font-bold uppercase text-[10px]">Student Name</span>
                <p class="font-extrabold text-slate-900 text-sm mt-0.5"><?= htmlspecialchars($student->user?->name ?? 'N/A') ?></p>
            </div>
            <div>
                <span class="text-slate-400 font-bold uppercase text-[10px]">Admission Number</span>
                <p class="font-extrabold text-slate-900 text-sm mt-0.5"><?= htmlspecialchars($student->admissionNumber) ?></p>
            </div>
            <div>
                <span class="text-slate-400 font-bold uppercase text-[10px]">Academic Session</span>
                <p class="font-extrabold text-slate-900 text-sm mt-0.5"><?= htmlspecialchars($session?->name ?? 'N/A') ?></p>
            </div>
            <div>
                <span class="text-slate-400 font-bold uppercase text-[10px]">Academic Term</span>
                <p class="font-extrabold text-slate-900 text-sm mt-0.5"><?= htmlspecialchars($term?->name ?? 'N/A') ?></p>
            </div>
            <div>
                <span class="text-slate-400 font-bold uppercase text-[10px]">Class Cohort</span>
                <p class="font-bold text-slate-900 mt-0.5"><?= htmlspecialchars($summary?->class?->name ?? $student->schoolClass?->name ?? 'N/A') ?></p>
            </div>
            <div>
                <span class="text-slate-400 font-bold uppercase text-[10px]">Position in Class</span>
                <p class="font-extrabold text-sky-800 text-sm mt-0.5">
                    <?= $summary && $summary->rankInClass ? "#{$summary->rankInClass}" : 'N/A' ?>
                </p>
            </div>
            <div>
                <span class="text-slate-400 font-bold uppercase text-[10px]">Term Average</span>
                <p class="font-extrabold text-slate-900 text-sm mt-0.5">
                    <?= $summary ? number_format((float)$summary->averageScore, 2) . '%' : 'N/A' ?>
                </p>
            </div>
            <div>
                <span class="text-slate-400 font-bold uppercase text-[10px]">Term GPA</span>
                <p class="font-extrabold text-slate-900 text-sm mt-0.5">
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
                        <th class="py-2.5 px-2 text-center border-r border-slate-300">Total Score</th>
                        <th class="py-2.5 px-2 text-center border-r border-slate-300">Grade Letter</th>
                        <th class="py-2.5 px-2 text-center border-r border-slate-300">Grade Point</th>
                        <th class="py-2.5 px-3">Subject Remarks</th>
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
                                <td class="py-2.5 px-3 font-bold border-r border-slate-200">
                                    <?= htmlspecialchars($res->classSubject?->subject?->name ?? 'Subject') ?>
                                </td>
                                <td class="py-2.5 px-2 text-center font-extrabold border-r border-slate-200">
                                    <?= number_format((float)$res->computedScore, 2) ?>%
                                </td>
                                <td class="py-2.5 px-2 text-center font-extrabold border-r border-slate-200">
                                    <?= htmlspecialchars((string)$res->gradeLetter) ?>
                                </td>
                                <td class="py-2.5 px-2 text-center border-r border-slate-200 font-mono font-bold">
                                    <?= $res->gradePoint !== null ? number_format((float)$res->gradePoint, 2) : '&mdash;' ?>
                                </td>
                                <td class="py-2.5 px-3 text-slate-700">
                                    <?= htmlspecialchars($res->remark ?? 'Good Progress') ?>
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
                <p class="italic text-slate-700 text-xs min-h-[32px] leading-relaxed">
                    <?= htmlspecialchars($summary?->classTeacherRemark ?? 'Satisfactory academic effort and continuous improvement demonstrated this term.') ?>
                </p>
                <div class="pt-3 border-t border-slate-200 flex justify-between items-center text-[10px] text-slate-500 font-semibold">
                    <span>Teacher's Signature</span>
                    <span class="font-mono text-emerald-700">Verified &bull; Digital Signature</span>
                </div>
            </div>

            <div class="space-y-3 p-4 bg-slate-50 border border-slate-200 rounded-xl">
                <h4 class="font-bold text-slate-800 uppercase text-[11px]">Principal's Final Remark</h4>
                <p class="italic text-slate-700 text-xs min-h-[32px] leading-relaxed">
                    <?= htmlspecialchars($summary?->principalRemark ?? 'Commendable performance. Encouraged to maintain standard in subsequent terms.') ?>
                </p>
                <div class="pt-3 border-t border-slate-200 flex justify-between items-center text-[10px] text-slate-500 font-semibold">
                    <span>Principal's Seal & Signature</span>
                    <span class="font-mono text-emerald-700">Approved Official Record</span>
                </div>
            </div>
        </div>

        <!-- Grading Key Footer -->
        <div class="pt-4 border-t border-slate-200 text-[10px] text-slate-500 flex flex-wrap justify-between items-center gap-2">
            <div>
                <strong>Grading Scale:</strong> A (70-100% / 5.0) &bull; B (60-69% / 4.0) &bull; C (50-59% / 3.0) &bull; D (45-49% / 2.0) &bull; E (40-44% / 1.0) &bull; F (0-39% / 0.0)
            </div>
            <div>
                Generated on: <?= htmlspecialchars($generated_at ?? date('Y-m-d H:i')) ?>
            </div>
        </div>

    </div>

</body>
</html>
