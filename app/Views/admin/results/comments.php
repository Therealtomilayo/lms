<?php
$this->layout('layouts/admin', [
    'title' => 'Batch Remarks & Behavioral Ratings — Claret LMS',
    'headerTitle' => 'Batch Comments & Behavioral Evaluation',
    'headerSubtitle' => 'Author terminal class teacher and principal comments with quick-fill templates and score behavioral domains.'
]);
?>

<div class="space-y-6">
    <!-- Flash Messages -->
    <?php if (!empty($flashSuccess)): ?>
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span><?= e($flashSuccess) ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($flashError)): ?>
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                <span><?= e($flashError) ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Header & Cohort Selection Toolbar -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
        <form method="GET" action="/admin/results/comments" id="cohort-filter-form" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Academic Session</label>
                <select name="session_id" onchange="document.getElementById('cohort-filter-form').submit()" 
                        class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition bg-white">
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?= (int)$s->id ?>" <?= $selectedSessionId === (int)$s->id ? 'selected' : '' ?>>
                            <?= e($s->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Academic Term</label>
                <select name="term_id" onchange="document.getElementById('cohort-filter-form').submit()" 
                        class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition bg-white">
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= (int)$t->id ?>" <?= $selectedTermId === (int)$t->id ? 'selected' : '' ?>>
                            <?= e($t->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Class Cohort & Arm</label>
                <select name="class_id" onchange="document.getElementById('cohort-filter-form').submit()" 
                        class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition bg-white font-bold text-slate-900">
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= (int)$c->id ?>" <?= $selectedClassId === (int)$c->id ? 'selected' : '' ?>>
                            <?= e($c->name) ?><?= !empty($c->sectionArm) ? ' (' . e($c->sectionArm) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <a href="/admin/skills?tab=presets" class="w-full text-center px-4 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition border border-slate-200">
                    ⚙️ Configure Presets
                </a>
            </div>
        </form>
    </div>

    <!-- Batch Comments Table Form -->
    <?php if (empty($students)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm space-y-3">
            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <h3 class="text-base font-bold text-slate-800">No Students Enrolled in this Class</h3>
            <p class="text-sm text-slate-500 max-w-md mx-auto">Please select a different class arm or ensure students are enrolled in the academic cohort.</p>
        </div>
    <?php else: ?>
        <form method="POST" action="/admin/results/comments" id="batch-comments-form" class="space-y-6">
            <?= csrf_field() ?>
            <input type="hidden" name="session_id" value="<?= (int)$selectedSessionId ?>">
            <input type="hidden" name="term_id" value="<?= (int)$selectedTermId ?>">
            <input type="hidden" name="class_id" value="<?= (int)$selectedClassId ?>">

            <!-- Quick Action & Stats Bar -->
            <div class="flex items-center justify-between flex-wrap gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 bg-brand-100 text-brand-800 font-bold text-xs rounded-full">
                        <?= count($students) ?> Students in <?= e($selectedClass?->name ?? 'Class') ?>
                    </span>
                    <span class="text-xs text-slate-500">
                        Affective & Psychomotor traits: <strong><?= count($skills) ?> dimensions</strong>
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" onclick="toggleRatingsDrawerAll()" class="px-3 py-1.5 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                        Toggle All Behavioral Ratings
                    </button>
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        <span>Save All Comments & Ratings</span>
                    </button>
                </div>
            </div>

            <!-- Student Cards / Rows -->
            <div class="space-y-4">
                <?php foreach ($students as $idx => $st): 
                    $studentId = (int)$st->id;
                    $studentName = $st->user?->name ?? $st->name ?? 'Student';
                    $admNo = $st->admissionNumber ?? "ADM-{$studentId}";
                    $summary = $summariesByStudentId[$studentId] ?? null;

                    $teacherComment = $summary?->classTeacherRemark ?? '';
                    $principalComment = $summary?->principalRemark ?? '';
                    $ratings = $ratingsMatrix[$studentId] ?? [];
                ?>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden transition hover:border-slate-300">
                        <!-- Top Row: Student Header Strip -->
                        <div class="p-4 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between flex-wrap gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-brand-700 text-white flex items-center justify-center font-bold text-xs uppercase shadow-sm">
                                    <?= substr($studentName, 0, 2) ?>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm font-bold text-slate-900"><?= e($studentName) ?></h4>
                                        <span class="px-2 py-0.5 text-[11px] font-mono font-medium rounded bg-white text-slate-600 border border-slate-200">
                                            ID: <?= e($admNo) ?>
                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-2">
                                        <span>Candidate #<?= $idx + 1 ?></span>
                                        <?php if ($summary && $summary->averageScore !== null): ?>
                                            <span>&bull;</span>
                                            <span class="font-bold text-slate-700">Term Avg: <?= number_format((float)$summary->averageScore, 1) ?>%</span>
                                            <?php if ($summary->rankInClass): ?>
                                                <span class="px-1.5 py-0.2 rounded bg-amber-100 text-amber-800 font-bold text-[10px]">Rank #<?= (int)$summary->rankInClass ?></span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Quick Report Card Link & Behavioral toggle button -->
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="toggleStudentRatings(<?= $studentId ?>)" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 shadow-xs transition">
                                    <svg class="w-3.5 h-3.5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Rate Behaviors (1-5)</span>
                                    <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" id="arrow-<?= $studentId ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <a href="/admin/reports/student/<?= $studentId ?>/<?= (int)$selectedTermId ?>.pdf" target="_blank" 
                                   class="px-2.5 py-1.5 text-xs font-semibold text-brand-600 hover:text-brand-800 transition">
                                    View Dossier &rarr;
                                </a>
                            </div>
                        </div>

                        <!-- Main Body: Two Column Comment Textareas with Quick Suggestion Dropdown -->
                        <div class="p-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <!-- Column 1: Teacher Comment -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                        Class Teacher Comment
                                    </label>
                                    
                                    <!-- Quick Suggestion Dropdown -->
                                    <select onchange="applyPreset(this, 'tc-<?= $studentId ?>', '<?= addslashes(e($studentName)) ?>')" 
                                            class="text-xs px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-white text-slate-600 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                        <option value="">⚡ Quick Suggestion...</option>
                                        <?php foreach ($teacherPresets as $pr): ?>
                                            <option value="<?= htmlspecialchars($pr->text, ENT_QUOTES) ?>">
                                                [<?= ucfirst(e($pr->category)) ?>] <?= substr(e($pr->text), 0, 45) ?>...
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <textarea name="comments[<?= $studentId ?>][teacher_remark]" 
                                          id="tc-<?= $studentId ?>" 
                                          rows="3" 
                                          placeholder="Type custom class teacher remark or pick from suggestions above..."
                                          class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition leading-relaxed"><?= e($teacherComment) ?></textarea>
                            </div>

                            <!-- Column 2: Principal Comment -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                        Principal / Head of School Endorsement
                                    </label>

                                    <!-- Quick Suggestion Dropdown -->
                                    <select onchange="applyPreset(this, 'pc-<?= $studentId ?>', '<?= addslashes(e($studentName)) ?>')" 
                                            class="text-xs px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-white text-slate-600 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                        <option value="">⚡ Quick Suggestion...</option>
                                        <?php foreach ($principalPresets as $pr): ?>
                                            <option value="<?= htmlspecialchars($pr->text, ENT_QUOTES) ?>">
                                                [<?= ucfirst(e($pr->category)) ?>] <?= substr(e($pr->text), 0, 45) ?>...
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <textarea name="comments[<?= $studentId ?>][principal_remark]" 
                                          id="pc-<?= $studentId ?>" 
                                          rows="3" 
                                          placeholder="Type principal endorsement or pick from suggestions above..."
                                          class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition leading-relaxed"><?= e($principalComment) ?></textarea>
                            </div>
                        </div>

                        <!-- Collapsible Behavioral Domain Ratings Drawer -->
                        <div id="ratings-drawer-<?= $studentId ?>" class="hidden border-t border-slate-200 bg-gradient-to-b from-slate-50 to-white p-5 space-y-4">
                            <div class="flex items-center justify-between">
                                <h5 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Affective & Psychomotor Domain Evaluation (1–5 Scale)
                                </h5>
                                <div class="text-[11px] text-slate-500">
                                    5: Excellent &bull; 4: Good &bull; 3: Fair &bull; 2: Poor &bull; 1: Needs Help
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                <?php foreach ($skills as $sk): 
                                    $skillId = (int)$sk->id;
                                    $currentScore = $ratings[$skillId] ?? 4; // default 4 if not yet scored
                                ?>
                                    <div class="bg-white p-2.5 rounded-xl border border-slate-200 flex items-center justify-between gap-2 shadow-2xs">
                                        <div class="text-xs font-medium text-slate-800 truncate" title="<?= e($sk->name) ?>">
                                            <span class="text-[10px] font-bold uppercase <?= $sk->isPsychomotor() ? 'text-blue-600' : 'text-emerald-600' ?>">
                                                <?= $sk->isPsychomotor() ? '[Psy]' : '[Aff]' ?>
                                            </span>
                                            <?= e($sk->name) ?>
                                        </div>
                                        <div class="flex items-center gap-1 flex-shrink-0">
                                            <?php for ($val = 1; $val <= 5; $val++): ?>
                                                <label class="cursor-pointer">
                                                    <input type="radio" 
                                                           name="ratings[<?= $studentId ?>][<?= $skillId ?>]" 
                                                           value="<?= $val ?>" 
                                                           <?= $currentScore === $val ? 'checked' : '' ?>
                                                           class="sr-only peer">
                                                    <span class="w-6 h-6 flex items-center justify-center rounded-lg text-xs font-bold transition peer-checked:bg-brand-600 peer-checked:text-white bg-slate-100 text-slate-600 hover:bg-slate-200">
                                                        <?= $val ?>
                                                    </span>
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Sticky Bottom Save Bar -->
            <div class="sticky bottom-4 bg-slate-900/95 backdrop-blur text-white p-4 rounded-2xl shadow-xl flex items-center justify-between flex-wrap gap-4 border border-slate-800">
                <div class="flex items-center gap-3">
                    <span class="text-xs text-slate-300">
                        Ready to update remarks and behavioral ratings for <strong><?= count($students) ?> students</strong>.
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <a href="/admin/results/comments" class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white transition">
                        Reset Changes
                    </a>
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm rounded-xl shadow-md transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Save All Class Remarks & Ratings</span>
                    </button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function applyPreset(selectElem, targetTextareaId, studentName) {
    const text = selectElem.value;
    if (!text) return;
    
    const textarea = document.getElementById(targetTextareaId);
    if (!textarea) return;
    
    // Replace placeholder tags like {name} if present
    const personalized = text.replace(/\{name\}/gi, studentName);
    
    textarea.value = personalized;
    textarea.focus();
    selectElem.value = ''; // Reset select to placeholder
}

function toggleStudentRatings(studentId) {
    const drawer = document.getElementById('ratings-drawer-' + studentId);
    const arrow = document.getElementById('arrow-' + studentId);
    if (!drawer) return;
    
    if (drawer.classList.contains('hidden')) {
        drawer.classList.remove('hidden');
        if (arrow) arrow.style.transform = 'rotate(180deg)';
    } else {
        drawer.classList.add('hidden');
        if (arrow) arrow.style.transform = 'rotate(0deg)';
    }
}

function toggleRatingsDrawerAll() {
    const drawers = document.querySelectorAll('[id^="ratings-drawer-"]');
    const isAnyHidden = Array.from(drawers).some(d => d.classList.contains('hidden'));
    
    drawers.forEach(d => {
        if (isAnyHidden) {
            d.classList.remove('hidden');
        } else {
            d.classList.add('hidden');
        }
    });

    const arrows = document.querySelectorAll('[id^="arrow-"]');
    arrows.forEach(a => {
        a.style.transform = isAnyHidden ? 'rotate(180deg)' : 'rotate(0deg)';
    });
}
</script>
