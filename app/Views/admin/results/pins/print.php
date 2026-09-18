<?php
/**
 * Printable Scratch-Card Sheet View (Admin)
 * 
 * @var array<\App\Models\ResultAccessPin> $pins
 * @var \App\Models\AcademicSession|null $currentSession
 * @var \App\Models\AcademicTerm|null $currentTerm
 * @var string $headerTitle
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Result Scratch Cards — Claret International School</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        @media print {
            body { background: #fff !important; color: #000 !important; padding: 0 !important; }
            .no-print { display: none !important; }
            .card-grid { gap: 8mm !important; }
            .scratch-card { 
                page-break-inside: avoid; 
                break-inside: avoid;
                border: 1.5px dashed #94a3b8 !important;
                box-shadow: none !important;
            }
        }
        .scratch-box {
            background: linear-gradient(135deg, #e2e8f0 25%, #cbd5e1 25%, #cbd5e1 50%, #e2e8f0 50%, #e2e8f0 75%, #cbd5e1 75%, #cbd5e1 100%);
            background-size: 16px 16px;
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen p-4 sm:p-6 font-sans antialiased">
    <div class="max-w-6xl mx-auto space-y-4">
        <!-- Control Bar (Hidden in Print) -->
        <div class="no-print bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="/admin/results/pins" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to PIN Directory
                </a>
                <span class="text-slate-300">|</span>
                <span class="text-xs font-bold text-slate-700">Total Cards on Sheet: <span class="text-emerald-700"><?= count($pins) ?></span></span>
                <?php if ($currentTerm): ?>
                    <span class="text-[11px] px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 font-medium">
                        <?= e($currentSession->name ?? '') ?> &bull; <?= e($currentTerm->name ?? '') ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" 
                        onclick="window.print()" 
                        class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-sm transition inline-flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print Scratch Cards (A4)
                </button>
            </div>
        </div>

        <?php if (empty($pins)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
                <p class="text-sm text-slate-500">No active scratch-card PINs available to print for this selection.</p>
                <a href="/admin/results/pins" class="mt-4 inline-block px-4 py-2 bg-slate-900 text-white text-xs font-bold rounded-xl">Generate New PINs</a>
            </div>
        <?php else: ?>
            <!-- 3x3 Perforated Grid of Cards -->
            <div class="card-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($pins as $pin): ?>
                    <div class="scratch-card bg-white rounded-2xl border-2 border-dashed border-slate-300 p-4 shadow-sm relative flex flex-col justify-between overflow-hidden">
                        <!-- Top Header with Crest & Title -->
                        <div class="flex items-start justify-between border-b border-slate-100 pb-2 mb-2.5">
                            <div class="flex items-center gap-2">
                                <img src="/assets/img/logo.png" alt="Logo" class="w-8 h-8 object-contain" onerror="this.src='/favicon.ico'; this.onerror=null;">
                                <div>
                                    <h2 class="text-xs font-black tracking-tight uppercase text-slate-900 leading-tight">Claret International School</h2>
                                    <p class="text-[9px] uppercase tracking-wider font-bold text-emerald-700">Official Result Scratch-Card</p>
                                </div>
                            </div>
                            <span class="text-[9px] font-mono font-bold bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200">
                                <?= $pin->maxUses ?> Uses
                            </span>
                        </div>

                        <!-- Card Body / Target Student / Term -->
                        <div class="text-[10px] space-y-1 mb-2.5 text-slate-600">
                            <div class="flex justify-between">
                                <span class="text-slate-400 font-medium">Session / Term:</span>
                                <span class="font-bold text-slate-800"><?= e($pin->termName ?? 'Any Active Term') ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400 font-medium">Student Allocation:</span>
                                <span class="font-bold text-slate-800 truncate max-w-[170px]" title="<?= e($pin->studentName ?? 'Unassigned') ?>">
                                    <?= e($pin->studentName ?? 'Unassigned (Binds on check)') ?>
                                </span>
                            </div>
                            <?php if ($pin->studentAdmissionNumber): ?>
                                <div class="flex justify-between">
                                    <span class="text-slate-400 font-medium">Admission No:</span>
                                    <span class="font-mono font-bold text-slate-800"><?= e($pin->studentAdmissionNumber) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Scratch Silver Field & PIN Reveal -->
                        <div class="scratch-box rounded-xl p-2.5 border border-slate-300 text-center space-y-1 mb-2">
                            <span class="text-[9px] uppercase tracking-widest font-black text-slate-500 block">Scratch Gently to Reveal PIN</span>
                            <div class="bg-white/90 backdrop-blur-sm rounded-lg py-1.5 px-3 border border-slate-200 inline-block shadow-inner w-full">
                                <span class="font-mono font-black text-base sm:text-lg tracking-widest text-slate-900 select-all">
                                    <?= e($pin->getFormattedPin()) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Footer Serial & Instructions -->
                        <div class="border-t border-slate-100 pt-2 flex items-center justify-between text-[9px] text-slate-400">
                            <div>
                                S/N: <span class="font-mono font-bold text-slate-700"><?= e($pin->serialNumber) ?></span>
                            </div>
                            <div class="text-right font-medium">
                                Visit: <span class="text-emerald-700 font-bold">/results/check</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
