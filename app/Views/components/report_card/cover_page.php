<?php
/**
 * Claret International School Report Card Dossier — Page 1 (Cover Page)
 * Exact replica of Claret report card sample.pdf Page 1
 */

$studentName = htmlspecialchars($student->user?->name ?? $student->name ?? 'Student');
$studentPhoto = $student->user?->avatarUrl ?? '/assets/img/logo.png';
$termTitle = strtoupper($term->name ?? 'Summer Term');
$sessionTitle = strtoupper($session->name ?? '2025/2026') . ' ACADEMIC SESSION';
?>

<div class="report-page page-1 relative flex flex-col justify-between overflow-hidden bg-white text-slate-800">
    
    <!-- Left Border Pattern Ribbon (Matching sample PDF) -->
    <div class="absolute left-1.5 top-0 bottom-0 flex flex-col justify-between py-3 pointer-events-none z-10">
        <?php for ($i = 0; $i < 42; $i++): ?>
            <span class="text-[9px] text-slate-800 leading-none select-none">&#9658;</span>
        <?php endfor; ?>
    </div>

    <!-- Right Border Pattern Ribbon -->
    <div class="absolute right-1.5 top-0 bottom-0 flex flex-col justify-between py-3 pointer-events-none z-10">
        <?php for ($i = 0; $i < 42; $i++): ?>
            <span class="text-[9px] text-slate-800 leading-none select-none">&#9668;</span>
        <?php endfor; ?>
    </div>

    <!-- Watermark Background -->
    <div class="watermark-bg"></div>

    <!-- Header & Brand Crest -->
    <div class="relative z-10 pt-10 px-12 text-center">
        <div class="flex items-center justify-center gap-6 mb-2">
            <img src="<?= htmlspecialchars(!empty($school['logo']) ? $school['logo'] : '/assets/img/logo.png') ?>" alt="Claret Crest" class="h-24 w-auto object-contain drop-shadow-sm">
            <div class="text-left">
                <h1 class="text-6xl sm:text-7xl font-black tracking-tight text-[#0C9DD5] font-sans leading-none">
                    CLARET
                </h1>
                <p class="text-xl sm:text-2xl font-black tracking-wider text-slate-900 uppercase font-sans mt-1">
                    <?= htmlspecialchars(strtoupper($school['name'] ?? 'INTERNATIONAL SCHOOL')) ?>
                </p>
                <div class="flex items-center gap-3 text-base sm:text-lg font-serif italic text-slate-700 mt-1">
                    <span class="text-amber-800">Crèche</span>
                    <span class="font-sans not-italic font-extrabold uppercase text-amber-600 tracking-wider text-sm">NURSERY</span>
                    <span class="font-serif font-bold text-slate-900">Primary</span>
                </div>
            </div>
        </div>

        <!-- Grade School Assessment Record Pill Card -->
        <div class="mx-auto max-w-xl mt-8 rounded-2xl border-2 border-amber-300/80 bg-gradient-to-r from-amber-50/90 via-rose-50/80 to-amber-50/90 py-3.5 px-6 shadow-sm">
            <h2 class="text-xl sm:text-2xl font-black text-amber-800 tracking-tight font-serif">
                Grade School Assessment Record
            </h2>
            <p class="text-sm sm:text-base font-semibold text-slate-800 italic mt-0.5 font-serif">
                Dossier d'évaluation de l'école Primaire
            </p>
        </div>

        <!-- Term & Session Pill -->
        <div class="mt-8 inline-block">
            <p class="text-lg font-black tracking-widest text-amber-700 uppercase drop-shadow-xs">
                <?= $termTitle ?>
            </p>
            <p class="text-sm font-extrabold tracking-wider text-amber-800 mt-0.5">
                <?= $sessionTitle ?>
            </p>
        </div>
    </div>

    <!-- Framed Student Photo (Matching exact double-frame aesthetic) -->
    <div class="relative z-10 my-auto flex flex-col items-center justify-center py-6">
        <div class="rounded-lg bg-[#0C9DD5] p-3 shadow-xl ring-2 ring-[#0C9DD5]/40">
            <div class="rounded bg-white p-2.5 shadow-inner">
                <?php if (!empty($student->user?->avatarUrl)): ?>
                    <img src="<?= htmlspecialchars($student->user->avatarUrl) ?>" 
                         alt="<?= $studentName ?>" 
                         class="h-56 w-44 object-cover rounded">
                <?php else: ?>
                    <div class="h-56 w-44 bg-slate-100 flex flex-col items-center justify-center rounded border border-slate-200">
                        <img src="<?= htmlspecialchars(!empty($school['logo']) ? $school['logo'] : '/assets/img/logo.png') ?>" alt="Claret Logo" class="h-20 w-20 object-contain opacity-40 mb-2">
                        <span class="text-xs font-bold text-slate-600 px-2 text-center"><?= $studentName ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4 text-center">
            <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight"><?= $studentName ?></h3>
            <p class="text-xs font-semibold text-slate-600 uppercase tracking-wider mt-0.5">
                Adm No: <span class="font-mono text-slate-900"><?= htmlspecialchars($student->admissionNumber ?? 'N/A') ?></span>
                &bull; Class: <span class="text-slate-900"><?= htmlspecialchars($class?->name ?? 'Primary') ?></span>
            </p>
        </div>
    </div>

    <!-- Bottom Institutional Banner & Curved Border -->
    <div class="relative z-10 px-10 pb-8 text-center">
        <!-- Swoosh Divider Line -->
        <div class="relative mb-5 flex items-center justify-center">
            <div class="h-0.5 w-full bg-gradient-to-r from-transparent via-slate-800 to-transparent"></div>
        </div>

        <h4 class="text-base font-black tracking-wider text-slate-900 uppercase">
            <?= htmlspecialchars(strtoupper($school['name'] ?? 'CLARET INTERNATIONAL SCHOOL')) ?>
        </h4>
        <p class="text-xs font-medium text-slate-600 mt-1">
            <?= htmlspecialchars($school['address'] ?? 'Plot 700 Gitto Street, Mabushi, Abuja, Nigeria.') ?>
        </p>
        <p class="text-[11px] font-semibold text-slate-700 mt-1 tracking-tight">
            Tel: <?= htmlspecialchars($school['phone'] ?? '+234 803 788 1737') ?> &bull; 
            E-mail: <?= htmlspecialchars($school['email'] ?? 'info@claret.edu') ?>
            <?php if (!empty($school['website'])): ?>
                &bull; Website: <?= htmlspecialchars($school['website']) ?>
            <?php endif; ?>
        </p>
    </div>
</div>
