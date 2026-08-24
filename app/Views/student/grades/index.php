<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                    <a href="/student/dashboard" class="hover:text-sky-600 transition">Student Portal</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Academic Grades</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Academic Grades & Assessment
                    </h1>
                    <?php if ($student && $student->schoolClass): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                            <?= htmlspecialchars($student->schoolClass->name) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Review your terminal continuous assessments, computed scores, and official report cards.
                </p>
            </div>

            <?php if ($isPublished && !empty($subjectResults)): ?>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <a href="/student/grades/report-card?term_id=<?= (int)$selectedTermId ?>" target="_blank" 
                       class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>View Official Report Card</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Academic Term Selector Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form method="GET" action="/student/grades" class="flex items-center gap-3 w-full sm:w-auto">
            <label for="term_id" class="text-xs font-bold text-slate-700 uppercase tracking-wider whitespace-nowrap">Academic Term:</label>
            <select name="term_id" id="term_id" onchange="this.form.submit()" 
                    class="px-3.5 py-2 text-xs font-bold rounded-xl border border-slate-300 bg-slate-50 text-slate-800 focus:bg-white focus:border-sky-500 focus:ring-sky-500 transition">
                <?php foreach ($terms as $t): ?>
                    <option value="<?= (int)$t->id ?>" <?= (int)$t->id === (int)$selectedTermId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <div class="text-xs font-semibold text-slate-500 flex items-center gap-2">
            <span>Status:</span>
            <?php if ($isPublished): ?>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                    Results Published
                </span>
            <?php else: ?>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                    Results In Processing
                </span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$isPublished): ?>
        <!-- Unpublished Alert Banner -->
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-10 text-center shadow-xs">
            <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 mx-auto flex items-center justify-center mb-3">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-extrabold text-amber-900">Term Results Are Processing</h3>
            <p class="text-xs mt-1.5 text-amber-700 max-w-md mx-auto leading-relaxed">
                Official scores and report cards for this term have not yet been approved and published by school administration. Please check back after publication.
            </p>
        </div>
    <?php else: ?>
        <!-- 4-Card KPI Summary Metrics Strip -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Class Position -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                <p class="text-xs font-bold uppercase tracking-wider text-sky-600">Class Position</p>
                <div class="flex items-baseline gap-1 mt-1">
                    <h3 class="text-2xl font-extrabold text-sky-600">
                        <?= $summary && $summary->rankInClass ? "#{$summary->rankInClass}" : '—' ?>
                    </h3>
                </div>
                <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                    Cohort Rank
                </span>
            </div>

            <!-- Average Score -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Term Average</p>
                <div class="flex items-baseline gap-1 mt-1">
                    <h3 class="text-2xl font-extrabold text-slate-900">
                        <?= $summary ? number_format((float)$summary->averageScore, 2) : '0.00' ?>%
                    </h3>
                </div>
                <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                    Cumulative Score
                </span>
            </div>

            <!-- GPA -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Grade Point</p>
                <div class="flex items-baseline gap-1 mt-1">
                    <h3 class="text-2xl font-extrabold text-slate-900">
                        <?= ($summary && $summary->gpa !== null) ? number_format((float)$summary->gpa, 2) : '—' ?>
                    </h3>
                </div>
                <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                    GPA Rating
                </span>
            </div>

            <!-- Total Subjects Evaluated -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Evaluated Subjects</p>
                <div class="flex items-baseline gap-1 mt-1">
                    <h3 class="text-2xl font-extrabold text-slate-900"><?= count($subjectResults) ?></h3>
                    <span class="text-xs font-semibold text-slate-500">courses</span>
                </div>
                <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                    Total Graded
                </span>
            </div>
        </div>

        <!-- Subject Grades Table Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-900">Coursework & Subject Grade Breakdown</h2>
                <span class="text-xs font-bold text-slate-400 font-mono"><?= count($subjectResults) ?> Subjects</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                            <th class="py-3.5 px-5">Subject Name</th>
                            <th class="py-3.5 px-4">Subject Code</th>
                            <th class="py-3.5 px-4 text-center">Computed Score</th>
                            <th class="py-3.5 px-4 text-center">Grade Letter</th>
                            <th class="py-3.5 px-5">Teacher's Remark</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                        <?php if (empty($subjectResults)): ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
                                    No subject grades recorded for this term.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($subjectResults as $res): 
                                $sName = $res->classSubject?->subject?->name ?? 'Subject';
                                $sCode = $res->classSubject?->subject?->code ?? '';
                                $grade = $res->gradeLetter;
                                $badgeClass = match(strtoupper((string)$grade)) {
                                    'A', 'A+', 'A*' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                    'B', 'B+', 'B-' => 'bg-sky-50 text-sky-800 border-sky-200',
                                    'C', 'C+' => 'bg-amber-50 text-amber-800 border-amber-200',
                                    'D', 'E', 'F' => 'bg-rose-50 text-rose-800 border-rose-200',
                                    default => 'bg-slate-100 text-slate-700 border-slate-200'
                                };
                            ?>
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-3.5 px-5 font-bold text-slate-900 text-sm">
                                        <?= htmlspecialchars($sName) ?>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono font-bold text-slate-500">
                                        <?= htmlspecialchars($sCode) ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-extrabold text-slate-900 text-sm">
                                        <?= number_format((float)$res->computedScore, 2) ?>%
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="inline-flex items-center px-3 py-0.5 rounded-lg font-bold border text-xs <?= $badgeClass ?>">
                                            <?= htmlspecialchars((string)$grade) ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-5 text-slate-600 font-medium">
                                        <?= htmlspecialchars($res->remark ?? 'Good Effort') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
