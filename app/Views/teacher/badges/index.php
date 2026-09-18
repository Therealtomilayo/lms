<?php
/**
 * Teacher Badges & Honors Management Screen (SRS §33, §57 Phase 3)
 * Allows teachers to review badge catalog and award achievements to enrolled students.
 *
 * @var \App\Models\Badge[] $badges
 * @var \App\Models\ClassSubject[] $classSubjects
 * @var int $selectedClassSubjectId
 * @var \App\Models\Student[] $students
 * @var \App\Models\AcademicSession|null $activeSession
 * @var \App\Core\UserContext $user
 */

$selectedCs = null;
foreach ($classSubjects as $cs) {
    if ((int)$cs->id === $selectedClassSubjectId) {
        $selectedCs = $cs;
        break;
    }
}
$csrfToken = $_SESSION['_csrf_token'] ?? '';
?>

<div class="space-y-6">
    <!-- Breadcrumbs & Header -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                    <a href="/teacher/dashboard" class="hover:text-brand-600 transition">Dashboard</a>
                    <span>&rsaquo;</span>
                    <span class="text-slate-700 font-bold">Badges & Rewards</span>
                </div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Student Achievements & Badges</h1>
                <p class="text-sm text-slate-500 mt-1">Commend exemplary students with verified digital credentials and gamified badges.</p>
            </div>

            <!-- Subject Selector -->
            <?php if (!empty($classSubjects)): ?>
            <form method="GET" action="/teacher/badges" class="flex items-center gap-2">
                <label for="class_subject_id" class="text-xs font-bold text-slate-500 whitespace-nowrap">Class Subject:</label>
                <select name="class_subject_id" id="class_subject_id" onchange="this.form.submit()"
                        class="px-3 py-2 text-sm font-semibold rounded-lg border border-slate-300 bg-white text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                    <?php foreach ($classSubjects as $cs): ?>
                        <option value="<?= (int)$cs->id ?>" <?= (int)$cs->id === $selectedClassSubjectId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cs->subject->name ?? 'Subject') ?> (<?= htmlspecialchars($cs->schoolClass->name ?? 'Class') ?><?= !empty($cs->schoolClass->sectionArm) ? ' ' . htmlspecialchars($cs->schoolClass->sectionArm) : '' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Award Badge Form Card (1 Col) -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs sticky top-6">
                <div class="flex items-center gap-3 mb-5 pb-4 border-b border-slate-100">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Award an Achievement</h2>
                        <p class="text-xs text-slate-500">Recognize outstanding performance</p>
                    </div>
                </div>

                <?php if (empty($students)): ?>
                    <div class="p-4 rounded-lg bg-slate-50 border border-slate-200 text-center">
                        <p class="text-xs text-slate-600 font-medium">No students enrolled in this selected class subject.</p>
                    </div>
                <?php else: ?>
                    <form method="POST" action="/teacher/badges/award" class="space-y-4">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="class_subject_id" value="<?= (int)$selectedClassSubjectId ?>">

                        <div>
                            <label for="student_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Select Student *</label>
                            <select name="student_id" id="student_id" required
                                    class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-300 bg-white text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                                <option value="">-- Choose Student --</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= (int)$student->id ?>">
                                        <?= htmlspecialchars($student->name) ?> (<?= htmlspecialchars($student->admissionNumber) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="badge_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Select Badge *</label>
                            <select name="badge_id" id="badge_id" required
                                    class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-300 bg-white text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                                <option value="">-- Choose Achievement --</option>
                                <?php foreach ($badges as $badge): ?>
                                    <option value="<?= (int)$badge->id ?>">
                                        <?= htmlspecialchars($badge->name) ?> [<?= ucfirst(htmlspecialchars($badge->category)) ?>]
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="reason" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Commendation Citation *</label>
                            <textarea name="reason" id="reason" rows="3" required placeholder="State specific reasons for this commendation (visible to student and parents)..."
                                      class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-300 bg-white text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden"></textarea>
                        </div>

                        <button type="submit"
                                class="w-full py-2.5 px-4 rounded-lg bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-xs transition flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Confirm & Award Badge
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Badges Directory (2 Cols) -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
                <h2 class="text-base font-bold text-slate-900 mb-1">Claret Badges & Rewards Catalog</h2>
                <p class="text-xs text-slate-500 mb-6">Standard institutional criteria and gamification awards available to learners.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($badges as $badge): ?>
                        <div class="p-4 rounded-xl border border-slate-200 hover:border-brand-300 transition bg-slate-50/50 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-white border border-slate-200 text-slate-700">
                                        <?= ucfirst(htmlspecialchars($badge->category)) ?>
                                    </span>
                                    <span class="text-xs font-semibold text-slate-400">
                                        <?= $badge->isSystem ? 'System Verified' : 'Custom Award' ?>
                                    </span>
                                </div>
                                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <span class="text-amber-500">🏆</span>
                                    <?= htmlspecialchars($badge->name) ?>
                                </h3>
                                <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                                    <?= htmlspecialchars($badge->description) ?>
                                </p>
                            </div>
                            <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-xs text-slate-400">
                                <span>Code: <code class="font-mono text-slate-600 font-semibold"><?= htmlspecialchars($badge->slug) ?></code></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
</div>
