<?php
/**
 * Claret International School — 3-Page Official Terminal Report Card Dossier
 * Exact replica of Claret report card sample.pdf and Claret-report-card-sample-all-term.docx
 */

$studentName = htmlspecialchars($student->user?->name ?? $student->name ?? 'Student');
$studentAdm = htmlspecialchars($student->admissionNumber ?? 'N/A');
$className = htmlspecialchars($class?->name ?? 'Primary');
$termName = htmlspecialchars($term?->name ?? 'Term');
$sessionName = htmlspecialchars($session?->name ?? '2025/2026');

$backUrl = !empty($isParentPortal) 
    ? "/parent/children/{$student->id}/grades" 
    : "/student/grades";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card &mdash; <?= $studentName ?> (<?= $termName ?>, <?= $sessionName ?>)</title>
    
    <!-- Google Fonts & Tailwind CDN -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        :root {
            --brand-700: #7B3046;
            --brand-600: #0C9DD5;
            --brand-blue: #0C9DD5;
            --brand-dark: #0F172A;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        @media print {
            html, body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            .no-print {
                display: none !important;
            }
            .report-page {
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
                min-height: 297mm !important;
                margin: 0 !important;
                box-shadow: none !important;
                border: none !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
            }
            .report-page:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
        }

        @media screen {
            body {
                background: #0f172a; /* Sophisticated dark backdrop */
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                min-height: 100vh;
            }
            .report-page {
                width: 210mm;
                height: 297mm;
                max-height: 297mm;
                margin: 24px auto 36px auto;
                background: #ffffff;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 5px 15px rgba(0, 0, 0, 0.2);
                border-radius: 4px;
                overflow: hidden;
                box-sizing: border-box;
            }
        }

        .watermark-bg {
            position: absolute;
            inset: 0;
            background-image: url('/assets/img/logo.png');
            background-position: center 48%;
            background-repeat: no-repeat;
            background-size: 380px;
            opacity: 0.042;
            pointer-events: none;
            z-index: 1;
        }

        .handwriting-text {
            font-family: 'Caveat', cursive, 'Dancing Script', 'Segoe Print', sans-serif;
        }
    </style>
</head>
<body class="text-slate-800 antialiased selection:bg-sky-500 selection:text-white">

    <!-- Screen-Only Executive Floating Toolbar -->
    <div class="no-print sticky top-0 z-50 bg-slate-900/95 backdrop-blur border-b border-slate-700/80 px-4 py-3 shadow-xl">
        <div class="max-w-[210mm] mx-auto flex flex-wrap items-center justify-between gap-3 text-white">
            
            <div class="flex items-center gap-3">
                <a href="<?= htmlspecialchars($backUrl) ?>" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-600 transition shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Return
                </a>
                
                <div class="border-l border-slate-700 pl-3">
                    <div class="text-xs font-black uppercase text-sky-400 tracking-wide">
                        <?= $studentName ?>
                    </div>
                    <div class="text-[10px] text-slate-400 font-medium">
                        Adm: <span class="font-mono text-slate-300 font-semibold"><?= $studentAdm ?></span> &bull; <?= $className ?> &bull; <?= $termName ?>
                    </div>
                </div>
            </div>

            <!-- PIN Usage Badge (if applicable) -->
            <?php if (!empty($pin)): ?>
                <div class="hidden sm:inline-flex items-center gap-2 px-3 py-1 rounded-md bg-sky-950/80 border border-sky-600/40 text-[11px]">
                    <span class="text-sky-400 font-bold">PIN:</span>
                    <span class="font-mono font-black text-white"><?= htmlspecialchars($pin->pinCode ?? '') ?></span>
                    <span class="text-slate-400 text-[10px]">&bull; <?= (int)($remainingUses ?? 0) ?> use(s) left</span>
                </div>
            <?php endif; ?>

            <div class="flex items-center gap-2">
                <!-- Page Navigation Indicator -->
                <span class="hidden md:inline-block text-[11px] font-semibold text-slate-400">
                    Official 3-Page Dossier
                </span>

                <!-- Print Action Button -->
                <button onclick="window.print()" 
                        class="inline-flex items-center gap-2 px-4 py-1.5 rounded-lg text-xs font-black bg-[#0C9DD5] hover:bg-sky-400 text-white shadow-lg shadow-sky-500/20 transition transform active:scale-95 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print Dossier / Save PDF
                </button>
            </div>

        </div>
    </div>

    <!-- MAIN DOSSIER CONTAINER (3 A4 Pages) -->
    <main class="dossier-wrapper flex flex-col items-center">
        
        <!-- PAGE 1: Official Institutional Cover & Identity Dossier -->
        <?php include dirname(__DIR__, 2) . '/components/report_card/cover_page.php'; ?>

        <!-- PAGE 2: Comprehensive Cognitive Matrix & Behavioral Domains -->
        <?php include dirname(__DIR__, 2) . '/components/report_card/academic_page.php'; ?>

        <!-- PAGE 3: Official Attestation, Promotion Endorsement & Claret Seal -->
        <?php include dirname(__DIR__, 2) . '/components/report_card/endorsement_page.php'; ?>

    </main>

</body>
</html>
