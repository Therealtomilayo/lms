<?php
/**
 * Claret International School Report Card Dossier — Page 1 (Cover Page)
 * Exact replica of Claret report card sample.pdf Page 1
 */

$rawStudentName = $student->user?->name ?? $student->name ?? 'Student';
$gender = strtolower(trim((string)($student->gender ?? '')));
$isFemale = ($gender === 'female' || $gender === 'girl');
$salutation = $isFemale ? 'Lady' : 'Master';
$studentSalutedName = $salutation . ' ' . $rawStudentName;
$studentName = htmlspecialchars($studentSalutedName);
$passportPhoto = $passport_url ?? $student->user?->avatarUrl ?? (!empty($student->avatarUrl) ? $student->avatarUrl : null);
$termTitle = strtoupper($term->name ?? 'Summer Term');
$sessionTitle = strtoupper($session->name ?? '2025/2026') . ' ACADEMIC SESSION';
?>

<div class="report-page page-1 relative flex flex-col justify-between overflow-hidden bg-white text-slate-800">
    
    <!-- Left Border Pattern Ribbon (Matching sample PDF) -->
    <div class="cover-side-ribbon hidden sm:flex print:!flex absolute left-1.5 top-0 bottom-0 flex-col justify-between py-3 pointer-events-none z-10">
        <?php for ($i = 0; $i < 42; $i++): ?>
            <span class="text-[9px] text-slate-800 leading-none select-none">&#9658;</span>
        <?php endfor; ?>
    </div>

    <!-- Right Border Pattern Ribbon -->
    <div class="cover-side-ribbon hidden sm:flex print:!flex absolute right-1.5 top-0 bottom-0 flex-col justify-between py-3 pointer-events-none z-10">
        <?php for ($i = 0; $i < 42; $i++): ?>
            <span class="text-[9px] text-slate-800 leading-none select-none">&#9668;</span>
        <?php endfor; ?>
    </div>

    <!-- Watermark Background -->
    <div class="watermark-bg"></div>

    <!-- Header & Brand Crest -->
    <div class="relative z-10 pt-6 sm:pt-10 px-4 sm:px-12 text-center">
        <?php
        $rawSchoolName = trim($school['name'] ?? 'Claret International School');
        $words = preg_split('/\s+/', $rawSchoolName);
        if (count($words) > 1) {
            $primaryBrand = strtoupper($words[0]);
            $subBrand = strtoupper(implode(' ', array_slice($words, 1)));
        } else {
            $primaryBrand = strtoupper($rawSchoolName);
            $subBrand = '';
        }
        ?>
        <div class="cover-brand-header flex flex-col sm:flex-row print:!flex-row items-center justify-center gap-3 sm:gap-6 mb-2">
            <img src="<?= htmlspecialchars(!empty($school['logo']) ? $school['logo'] : '/assets/img/logo.png') ?>" alt="Claret Crest" class="h-16 sm:h-24 w-auto object-contain drop-shadow-sm">
            <div class="text-center sm:text-left">
                <h1 class="text-4xl sm:text-7xl font-black tracking-tight text-[#0C9DD5] font-sans leading-none">
                    <?= htmlspecialchars($primaryBrand) ?>
                </h1>
                <?php if (!empty($subBrand)): ?>
                    <p class="text-lg sm:text-2xl font-black tracking-wider text-slate-900 uppercase font-sans mt-1">
                        <?= htmlspecialchars($subBrand) ?>
                    </p>
                <?php endif; ?>
                <div class="flex items-center justify-center sm:justify-start gap-3 text-sm sm:text-lg font-serif italic text-slate-700 mt-1">
                    <span class="text-amber-800">Crèche</span>
                    <span class="font-sans not-italic font-extrabold uppercase text-amber-600 tracking-wider text-xs sm:text-sm">NURSERY</span>
                    <span class="font-serif font-bold text-slate-900">Primary</span>
                </div>
            </div>
        </div>

        <!-- Grade School Assessment Record Pill Card -->
        <div class="mx-auto max-w-xl mt-4 sm:mt-8 rounded-2xl border-2 border-amber-300/80 bg-gradient-to-r from-amber-50/90 via-rose-50/80 to-amber-50/90 py-2.5 sm:py-3.5 px-4 sm:px-6 shadow-sm">
            <h2 class="text-lg sm:text-2xl font-black text-amber-800 tracking-tight font-serif">
                Grade School Assessment Record
            </h2>
            <p class="text-xs sm:text-base font-semibold text-slate-800 italic mt-0.5 font-serif">
                Dossier d'évaluation de l'école Primaire
            </p>
        </div>

        <!-- Term & Session Pill -->
        <div class="mt-4 sm:mt-8 inline-block">
            <p class="text-base sm:text-lg font-black tracking-widest text-amber-700 uppercase drop-shadow-xs">
                <?= $termTitle ?>
            </p>
            <p class="text-xs sm:text-sm font-extrabold tracking-wider text-amber-800 mt-0.5">
                <?= $sessionTitle ?>
            </p>
        </div>
    </div>

    <!-- Framed Student Photo (Matching exact double-frame aesthetic) -->
    <div class="relative z-10 my-auto flex flex-col items-center justify-center py-4 sm:py-6">
        <div class="rounded-lg bg-[#0C9DD5] p-2 sm:p-3 shadow-xl ring-2 ring-[#0C9DD5]/40">
            <div class="rounded bg-white p-2 sm:p-2.5 shadow-inner">
                <?php if (!empty($passportPhoto)): ?>
                    <img src="<?= htmlspecialchars($passportPhoto) ?>" 
                         alt="<?= $studentName ?>" 
                         class="h-44 sm:h-56 w-36 sm:w-44 object-cover rounded shadow-xs">
                <?php else: ?>
                    <div class="h-44 sm:h-56 w-36 sm:w-44 bg-gradient-to-b from-slate-50 to-slate-100 flex flex-col items-center justify-center rounded border border-slate-200 p-3 text-center">
                        <img src="<?= htmlspecialchars(!empty($school['logo']) ? $school['logo'] : '/assets/img/logo.png') ?>" alt="School Crest" class="h-16 sm:h-20 w-16 sm:w-20 object-contain opacity-80 mb-2 drop-shadow-2xs">
                        <span class="text-xs sm:text-sm font-black text-slate-800 leading-tight tracking-tight"><?= $studentName ?></span>
                        <span class="text-[9px] font-semibold text-slate-500 uppercase mt-1">Official Student Record</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-3 sm:mt-4 text-center">
            <h3 class="text-base sm:text-lg font-black text-slate-900 uppercase tracking-tight"><?= $studentName ?></h3>
            <p class="text-[11px] sm:text-xs font-semibold text-slate-600 uppercase tracking-wider mt-0.5">
                Adm No: <span class="font-mono text-slate-900 font-bold"><?= htmlspecialchars($student->admissionNumber ?? 'N/A') ?></span>
                &bull; Class: <span class="text-slate-900 font-bold"><?= htmlspecialchars($class_full_name ?? ($class?->getFullName() ?? ($class?->name ?? 'Primary'))) ?></span>
            </p>
        </div>
    </div>

    <!-- Bottom Institutional Banner & Curved Border -->
    <div class="relative z-10 px-4 sm:px-10 pb-6 sm:pb-8 text-center">
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
