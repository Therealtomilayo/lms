<?php
$className = $classSubject->schoolClass?->name ?? 'Class Arm';
$subjectName = $classSubject->subject?->name ?? 'Subject';
$subjectCode = $classSubject->subject?->code ?? '';
?>
<div class="space-y-6">
    <!-- Top Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 print:border-none print:shadow-none">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 print:hidden">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/teacher/classes" class="text-slate-400 hover:text-emerald-600 transition">Class Workspace</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700"><?= htmlspecialchars($className) ?> Roster</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        <?= htmlspecialchars($className) ?> — <?= htmlspecialchars($subjectName) ?>
                    </h1>
                    <?php if (!empty($subjectCode)): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            <?= htmlspecialchars($subjectCode) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($activeTerm): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <?= htmlspecialchars($activeTerm->name) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Official student candidate roster and parent/guardian contact directory for this teaching cohort.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2 print:hidden">
                <a href="/teacher/classes" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    All Classes
                </a>
                <a href="/teacher/subjects/<?= (int)$classSubject->id ?>/discussions" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200 rounded-xl transition">
                    <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                    Discussions
                </a>
                <a href="/teacher/badges?class_subject_id=<?= (int)$classSubject->id ?>" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-xl transition">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                    Award Badges
                </a>
                <a href="/teacher/gradebook/<?= (int)$classSubject->id ?>" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-xl transition">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Gradebook
                </a>
                <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl shadow-xs transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print Roster
                </button>
            </div>
        </div>
    </div>

    <!-- 4-Card KPI Summary Metrics Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 print:grid-cols-4">
        <!-- Candidates -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Candidates</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($totalStudents) ?></h3>
                <span class="text-xs font-semibold text-slate-500">enrolled</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">Active cohort members</span>
        </div>

        <!-- Male -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-sky-600">Male Candidates</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($maleCount) ?></h3>
                <span class="text-xs font-semibold text-slate-500"><?= $totalStudents > 0 ? round(($maleCount / $totalStudents) * 100) : 0 ?>%</span>
            </div>
            <span class="text-[11px] font-medium text-sky-600 mt-1 block">Boys in class</span>
        </div>

        <!-- Female -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-pink-600">Female Candidates</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($femaleCount) ?></h3>
                <span class="text-xs font-semibold text-slate-500"><?= $totalStudents > 0 ? round(($femaleCount / $totalStudents) * 100) : 0 ?>%</span>
            </div>
            <span class="text-[11px] font-medium text-pink-600 mt-1 block">Girls in class</span>
        </div>

        <!-- Linked Guardians -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Linked Guardians</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($guardiansLinkedCount) ?></h3>
                <span class="text-xs font-semibold text-slate-500">parents</span>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">Contactable via portal</span>
        </div>
    </div>

    <!-- Search/Filter Bar (hidden when printing) -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs print:hidden">
        <div class="relative">
            <input type="text" id="roster-search" placeholder="Search candidate by full name, admission number, or guardian contact..." 
                   class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition">
            <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
    </div>

    <!-- Roster Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <?php if (empty($roster)): ?>
            <div class="p-12 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-800">No Enrolled Candidates</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                    There are no students currently enrolled in this class cohort for this session.
                </p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="roster-table">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-3 px-4 w-12 text-center">#</th>
                            <th class="py-3 px-4">Student Candidate</th>
                            <th class="py-3 px-4">Admission No</th>
                            <th class="py-3 px-4 text-center">Gender</th>
                            <th class="py-3 px-4">Parent / Guardian</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        <?php 
                        $idx = 1;
                        foreach ($roster as $item): 
                            $student = $item['student'];
                            $guardians = $item['guardians'];
                            $studentName = $student->user?->name ?? 'Unnamed Student';
                            $studentEmail = $student->user?->email ?? '';
                            $gender = strtolower((string)$student->gender);
                            $genderLabel = ($gender === 'male' || $gender === 'm') ? 'Male' : (($gender === 'female' || $gender === 'f') ? 'Female' : 'Other');
                            $genderBadgeClass = $genderLabel === 'Male' ? 'bg-sky-50 text-sky-700 border-sky-200' : ($genderLabel === 'Female' ? 'bg-pink-50 text-pink-700 border-pink-200' : 'bg-slate-100 text-slate-600 border-slate-200');

                            $guardianNames = [];
                            $guardianPhones = [];
                            foreach ($guardians as $g) {
                                if ($g->user?->name) {
                                    $guardianNames[] = $g->user->name;
                                }
                                if ($g->user?->phone) {
                                    $guardianPhones[] = $g->user->phone;
                                }
                            }
                            $searchString = strtolower($studentName . ' ' . $student->admissionNumber . ' ' . implode(' ', $guardianNames) . ' ' . implode(' ', $guardianPhones));
                        ?>
                            <tr class="student-row hover:bg-slate-50/70 transition-colors" data-search="<?= htmlspecialchars($searchString) ?>">
                                <td class="py-3 px-4 text-center font-bold text-slate-400">
                                    <?= $idx++ ?>
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-extrabold text-xs shrink-0">
                                            <?= htmlspecialchars(strtoupper(substr($studentName, 0, 1))) ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 leading-snug"><?= htmlspecialchars($studentName) ?></p>
                                            <?php if (!empty($studentEmail)): ?>
                                                <p class="text-[11px] font-normal text-slate-400"><?= htmlspecialchars($studentEmail) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded font-mono text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        <?= htmlspecialchars($student->admissionNumber) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold border <?= $genderBadgeClass ?>">
                                        <?= $genderLabel ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <?php if (empty($guardians)): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                            No Linked Guardian
                                        </span>
                                    <?php else: ?>
                                        <div class="space-y-1">
                                            <?php foreach ($guardians as $g): ?>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-slate-800 text-xs">
                                                        <?= htmlspecialchars($g->user?->name ?? 'Guardian') ?>
                                                    </span>
                                                    <?php if ($g->user?->phone): ?>
                                                        <a href="tel:<?= htmlspecialchars($g->user->phone) ?>" class="text-[11px] font-mono text-emerald-600 hover:underline">
                                                            <?= htmlspecialchars($g->user->phone) ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Active
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('roster-search');
    const rows = document.querySelectorAll('.student-row');

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            rows.forEach(row => {
                const searchData = row.getAttribute('data-search') || '';
                if (!query || searchData.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
</script>
