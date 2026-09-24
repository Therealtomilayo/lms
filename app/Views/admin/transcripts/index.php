<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Student Academic Transcripts — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Student Academic Transcripts',
    'headerSubtitle' => 'Generate official multi-session historical dossiers, cumulative score records, and certified transcripts.'
]);

$classOptions = ['' => 'All Classes'];
foreach ($classes as $c) {
    $classOptions[$c->id] = $c->getFullName();
}
?>
<div class="space-y-6">
    <!-- Header Summary & Search Bar -->
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-xs space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Student Transcript Directory</h2>
                <p class="text-sm text-slate-500 mt-0.5">Select any enrolled or historical student to preview, filter, and print their official certified cumulative transcript.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    QR Code Authenticated (SRS §26, §51)
                </span>
            </div>
        </div>

        <form method="GET" action="/admin/transcripts" class="grid grid-cols-1 sm:grid-cols-12 gap-3 pt-2">
            <div class="sm:col-span-6">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Search student by name, admission number, or email..." 
                           class="w-full text-sm pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition">
                </div>
            </div>

            <div class="sm:col-span-4">
                <select name="class_id" class="w-full text-sm px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition">
                    <?php foreach ($classOptions as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= (string)$val === (string)($selectedClassId ?? '') ? 'selected' : '' ?>><?= e($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="sm:col-span-2 flex gap-2">
                <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold rounded-lg bg-brand-600 hover:bg-brand-700 text-white shadow-xs transition">
                    Filter
                </button>
                <?php if (!empty($search) || !empty($selectedClassId)): ?>
                    <a href="/admin/transcripts" class="inline-flex items-center justify-center px-3 py-2.5 text-sm font-semibold rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition" title="Clear Filters">
                        &times;
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Student List -->
    <?php if (empty($students)): ?>
        <div class="bg-white border border-slate-200 rounded-xl p-12 text-center shadow-xs">
            <?php $this->include('components/empty_state', [
                'title' => 'No Students Found',
                'message' => 'No student profiles match your search filter. Try clearing the search or changing class filter.'
            ]); ?>
        </div>
    <?php else: ?>
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-xs tracking-wider">
                        <tr>
                            <th scope="col" class="py-3.5 px-6">Student</th>
                            <th scope="col" class="py-3.5 px-6">Admission No.</th>
                            <th scope="col" class="py-3.5 px-6">Current Cohort</th>
                            <th scope="col" class="py-3.5 px-6">Gender</th>
                            <th scope="col" class="py-3.5 px-6">Status</th>
                            <th scope="col" class="py-3.5 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($students as $st): ?>
                            <?php 
                            $uName = $st->user ? $st->user->name : 'Student #' . $st->id;
                            $uEmail = $st->user ? $st->user->email : '';
                            $initials = strtoupper(substr(trim($uName), 0, 1));
                            $cName = $st->schoolClass ? $st->schoolClass->getFullName() : 'Enrolled';
                            ?>
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-brand-100 text-brand-700 font-bold flex items-center justify-center text-sm shrink-0">
                                            <?= e($initials) ?>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900"><?= e($uName) ?></p>
                                            <p class="text-xs text-slate-500"><?= e($uEmail) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 font-mono font-medium text-slate-700">
                                    <?= e($st->admissionNumber) ?>
                                </td>
                                <td class="py-4 px-6 text-slate-800 font-medium">
                                    <?= e($cName) ?>
                                </td>
                                <td class="py-4 px-6 text-slate-600">
                                    <?= e(!empty($st->gender) ? ucfirst(strtolower($st->gender)) : '—') ?>
                                </td>
                                <td class="py-4 px-6">
                                    <?php 
                                    $variant = match (strtolower((string)$st->status)) {
                                        'active' => 'success',
                                        'graduated' => 'primary',
                                        'withdrawn' => 'danger',
                                        default => 'neutral'
                                    };
                                    ?>
                                    <?php $this->include('components/badge', ['label' => ucfirst((string)$st->status), 'variant' => $variant]); ?>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <a href="/admin/transcripts/<?= $st->id ?>" 
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-brand-50 text-brand-700 hover:bg-brand-100 border border-brand-200 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        View Transcript
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
