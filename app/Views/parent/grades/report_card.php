<?php
/**
 * Claret International School — 3-Page Official Terminal Report Card Dossier
 * Exact replica of Claret report card sample.pdf and Claret-report-card-sample-all-term.docx
 */

$isAdmin = !empty($isAdmin);
$isTeacher = !empty($isTeacher);
$isParentPortal = !empty($isParentPortal);
$isPdf = !empty($isPdf);

$rawStudentName = $student->user?->name ?? $student->name ?? 'Student';
$gender = strtolower(trim((string)($student->gender ?? '')));
$isFemale = ($gender === 'female' || $gender === 'girl');
$salutation = $isFemale ? 'Lady' : 'Master';
$studentSalutedName = $salutation . ' ' . $rawStudentName;
$studentName = htmlspecialchars($studentSalutedName);
$studentAdm = htmlspecialchars($student->admissionNumber ?? 'N/A');
$className = htmlspecialchars($class?->name ?? 'Primary');
$termName = htmlspecialchars($term?->name ?? 'Term');
$sessionName = htmlspecialchars($session?->name ?? '2025/2026');

$backUrl = $backUrl ?? ($isParentPortal 
    ? "/parent/children/{$student->id}/grades" 
    : ($isAdmin || str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/') ? "/admin/results/review" : "/student/grades"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
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
            .dossier-wrapper {
                width: 210mm !important;
                margin: 0 auto !important;
                padding: 0 !important;
                display: block !important;
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

            /* Force exact multi-column grid layouts during printing / Ctrl+P PDF generation */
            .academic-subtables-grid {
                display: grid !important;
                grid-template-columns: repeat(12, minmax(0, 1fr)) !important;
                gap: 10px !important;
            }
            .subtable-col-1 {
                grid-column: span 5 / span 5 !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 8px !important;
            }
            .subtable-col-2 {
                grid-column: span 3 / span 3 !important;
                display: block !important;
            }
            .subtable-col-3 {
                grid-column: span 4 / span 4 !important;
                display: block !important;
            }

            .dual-charts-grid {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 12px !important;
            }

            .endorsement-ribbon-grid {
                display: grid !important;
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            }

            .academic-subjects-table {
                min-width: 0 !important;
                width: 100% !important;
            }
            .academic-subjects-scroll-wrapper {
                overflow: visible !important;
            }

            .cover-brand-header {
                flex-direction: row !important;
            }
            .cover-side-ribbon {
                display: flex !important;
            }
        }

        @media screen {
            body {
                background: #0f172a; /* Sophisticated dark backdrop */
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                min-height: 100vh;
                margin: 0;
                padding: 0;
            }
            .dossier-wrapper {
                width: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
                box-sizing: border-box;
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

        @media screen and (max-width: 860px) {
            body {
                overflow-x: hidden;
                padding: 0;
            }
            .dossier-wrapper {
                width: 100% !important;
                max-width: 100vw !important;
                padding: 8px 6px !important;
                overflow-x: hidden !important;
            }
            .report-page {
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto !important;
                height: auto !important;
                max-height: none !important;
                margin: 8px 0 16px 0 !important;
                padding: 12px 10px !important;
                border-radius: 12px !important;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3) !important;
            }
        }

        .watermark-bg {
            position: absolute;
            inset: 0;
            background-image: url('<?= htmlspecialchars(!empty($school['logo']) ? $school['logo'] : '/assets/img/logo.png') ?>');
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
    <div class="no-print sticky top-0 z-50 bg-slate-900/95 backdrop-blur border-b border-slate-700/80 px-3 sm:px-4 py-2.5 shadow-xl">
        <div class="max-w-[210mm] mx-auto flex flex-wrap items-center justify-between gap-2.5 text-white">
            
            <div class="flex items-center gap-2.5 min-w-0">
                <a href="<?= htmlspecialchars($backUrl) ?>" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-600 transition shadow-sm flex-shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Return
                </a>
                
                <div class="border-l border-slate-700 pl-2.5 truncate">
                    <div class="text-xs font-black uppercase text-sky-400 tracking-wide truncate">
                        <?= $studentName ?>
                    </div>
                    <div class="text-[10px] text-slate-400 font-medium truncate">
                        Adm: <span class="font-mono text-slate-300 font-semibold"><?= $studentAdm ?></span> &bull; <?= $className ?> &bull; <?= $termName ?>
                    </div>
                </div>
            </div>

            <!-- Multi-Term Quick Switcher -->
            <?php if (!empty($session_terms)): 
                $activeTermObj = null;
                foreach ($session_terms as $sTerm) {
                    if (in_array($sTerm->status, ['active', 'grading_open'], true)) {
                        $activeTermObj = $sTerm;
                        break;
                    }
                }
                $activeStartDate = $activeTermObj ? $activeTermObj->startDate : date('Y-m-d');
                $buildTermUrl = function($targetTermId) use ($isAdmin, $isTeacher, $isParentPortal, $student): string {
                    $tId = (int)$targetTermId;
                    $sId = (int)($student->id ?? 0);
                    $reqUri = $_SERVER['REQUEST_URI'] ?? '';
                    if (str_contains($reqUri, '/admin/reports/student/')) {
                        return "/admin/reports/student/{$sId}/{$tId}.pdf";
                    }
                    if (str_contains($reqUri, '/teacher/reports/student/')) {
                        return "/teacher/reports/student/{$sId}/{$tId}.pdf";
                    }
                    if (!empty($isParentPortal) || str_contains($reqUri, '/parent/')) {
                        return "/parent/children/{$sId}/grades/report-card?term_id={$tId}";
                    }
                    if (str_contains($reqUri, '/student/')) {
                        return "/student/grades/report-card?term_id={$tId}";
                    }
                    return "?term_id={$tId}";
                };
            ?>
                <!-- Desktop Pill Switcher -->
                <div class="hidden md:flex items-center gap-1 bg-slate-800/90 p-1 rounded-lg border border-slate-700">
                    <?php foreach ($session_terms as $st): 
                        $isCur = (int)$st->id === (int)($term->id ?? 0);
                        $isFuture = ($st->startDate > $activeStartDate) && !in_array($st->status, ['active', 'grading_open', 'completed', 'archived'], true);
                        $tUrl = $buildTermUrl($st->id);
                    ?>
                        <?php if ($isFuture): ?>
                            <span class="px-2.5 py-1 rounded text-[11px] font-semibold text-slate-500 bg-slate-800/40 cursor-not-allowed flex items-center gap-1 opacity-60" title="This academic term has not yet commenced">
                                <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                <?= htmlspecialchars($st->name) ?>
                            </span>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars($tUrl) ?>" 
                               class="px-2.5 py-1 rounded text-[11px] font-bold transition <?= $isCur ? 'bg-[#7B3046] text-white shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-700' ?>">
                                <?= htmlspecialchars($st->name) ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Mobile Dropdown Switcher (Easy term switch on phones) -->
                <div class="md:hidden flex items-center gap-1.5 bg-slate-800/90 py-1 px-2 rounded-lg border border-slate-700">
                    <span class="text-[10px] uppercase font-bold text-slate-400">Term:</span>
                    <select onchange="if(this.value) window.location.href=this.value" 
                            class="bg-transparent text-sky-300 font-bold text-xs py-0.5 px-1 rounded focus:outline-none focus:ring-0 cursor-pointer">
                        <?php foreach ($session_terms as $st): 
                            $isCur = (int)$st->id === (int)($term->id ?? 0);
                            $isFuture = ($st->startDate > $activeStartDate) && !in_array($st->status, ['active', 'grading_open', 'completed', 'archived'], true);
                            $tUrl = $buildTermUrl($st->id);
                        ?>
                            <option value="<?= htmlspecialchars($tUrl) ?>" <?= $isCur ? 'selected' : '' ?> <?= $isFuture ? 'disabled' : '' ?> class="bg-slate-900 text-white">
                                <?= htmlspecialchars($st->name) ?><?= $isFuture ? ' (Upcoming)' : ($isCur ? ' (Viewing)' : '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

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
                        class="inline-flex items-center gap-2 px-4 py-1.5 rounded-lg text-xs font-black bg-[#7B3046] hover:bg-[#8D3850] text-white shadow-lg transition transform active:scale-95 cursor-pointer">
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
