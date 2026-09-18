<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/teacher/content" class="text-slate-400 hover:text-emerald-600 transition">Learning Materials</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Course Modules</span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    Course Modules & Progression Path
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Organize learning documents, quizzes, and coursework into sequential instructional units for student learning progression.
                </p>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2.5 flex-wrap">
                <?php if ($selectedClassSubjectId > 0): ?>
                    <a href="/teacher/subjects/<?= (int)$selectedClassSubjectId ?>/progress"
                       class="px-4 py-2.5 rounded-xl border border-sky-300 bg-sky-50 text-sky-800 hover:bg-sky-100 text-xs font-bold transition inline-flex items-center gap-2 shadow-2xs">
                        <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Progression Report
                    </a>
                <?php endif; ?>
                <a href="/teacher/content<?= $selectedClassSubjectId ? '?class_subject_id=' . (int)$selectedClassSubjectId : '' ?>"
                   class="px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition inline-flex items-center gap-2 shadow-2xs">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    Study Materials
                </a>
                <?php if ($selectedClassSubjectId > 0): ?>
                    <button type="button" onclick="openCreateModuleModal()"
                            class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        New Learning Module
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Class-Subject Selector Toolbar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
        <form method="GET" action="/teacher/modules" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3 flex-1">
                <label for="class_subject_id" class="text-xs font-bold text-slate-600 uppercase tracking-wider whitespace-nowrap flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    Class & Subject:
                </label>
                <select name="class_subject_id" id="class_subject_id" onchange="this.form.submit()"
                        class="flex-1 max-w-lg rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2 px-3 font-semibold text-slate-900 transition">
                    <?php if (empty($classSubjects)): ?>
                        <option value="">No active teaching allocations found</option>
                    <?php else: ?>
                        <?php foreach ($classSubjects as $cs): ?>
                            <?php 
                                $sName = $cs->subject?->name ?? ($cs->subjectName ?? 'Subject');
                                $cName = $cs->schoolClass?->name ?? ($cs->className ?? 'Class');
                                $sArm = $cs->schoolClass?->sectionArm ?? ($cs->sectionArm ?? '');
                            ?>
                            <option value="<?= (int)$cs->id ?>" <?= (int)$cs->id === (int)$selectedClassSubjectId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sName) ?> — <?= htmlspecialchars($cName) ?><?= !empty($sArm) ? ' (' . htmlspecialchars($sArm) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <noscript>
                    <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white rounded-lg text-xs font-medium">Switch</button>
                </noscript>
            </div>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                    <?= count($modules) ?> Module<?= count($modules) === 1 ? '' : 's' ?> Configured
                </span>
            </div>
        </form>
    </div>

    <!-- Modules Container -->
    <?php if (empty($selectedClassSubject)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-xs">
            <h3 class="text-base font-bold text-slate-800">No Teaching Allocation Selected</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                Please select an assigned class subject above to manage its learning modules.
            </p>
        </div>
    <?php elseif (empty($modules)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-xs">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-800">No Learning Modules Created Yet</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                Create structured learning modules to organize lessons, quizzes, and assignments into a coherent instructional path for students.
            </p>
            <div class="mt-6">
                <button type="button" onclick="openCreateModuleModal()"
                        class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Create First Module
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php 
                $moduleCount = count($modules);
                foreach ($modules as $index => $mod): 
            ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden transition hover:border-slate-300">
                    <!-- Module Header Bar -->
                    <div class="p-5 bg-slate-50/70 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start sm:items-center gap-3">
                            <span class="flex-shrink-0 w-8 h-8 rounded-xl bg-slate-900 text-white font-extrabold text-xs flex items-center justify-center shadow-xs">
                                <?= $index + 1 ?>
                            </span>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-base font-bold text-slate-900">
                                        <?= htmlspecialchars($mod->title) ?>
                                    </h3>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider <?= $mod->status === 'published' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' ?>">
                                        <?= htmlspecialchars($mod->status) ?>
                                    </span>
                                    <span class="text-xs text-slate-400 font-medium">
                                        (<?= count($mod->items) ?> <?= count($mod->items) === 1 ? 'activity' : 'activities' ?>)
                                    </span>
                                </div>
                                <?php if (!empty($mod->description)): ?>
                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                        <?= htmlspecialchars($mod->description) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Module Actions -->
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <!-- Reorder Move Up / Down -->
                            <?php if ($index > 0): ?>
                                <button type="button" title="Move Module Up"
                                        onclick="moveModule(<?= (int)$mod->id ?>, -1)"
                                        class="p-2 text-slate-500 hover:text-slate-800 hover:bg-white rounded-xl border border-slate-200 shadow-2xs transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                    </svg>
                                </button>
                            <?php endif; ?>

                            <?php if ($index < $moduleCount - 1): ?>
                                <button type="button" title="Move Module Down"
                                        onclick="moveModule(<?= (int)$mod->id ?>, 1)"
                                        class="p-2 text-slate-500 hover:text-slate-800 hover:bg-white rounded-xl border border-slate-200 shadow-2xs transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            <?php endif; ?>

                            <!-- Add Activity Button -->
                            <button type="button" onclick="openAddActivityModal(<?= (int)$mod->id ?>, '<?= htmlspecialchars(addslashes($mod->title)) ?>')"
                                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Activity
                            </button>

                            <!-- Edit Module -->
                            <button type="button" onclick="openEditModuleModal(<?= (int)$mod->id ?>, '<?= htmlspecialchars(addslashes($mod->title)) ?>', '<?= htmlspecialchars(addslashes($mod->description ?? '')) ?>', '<?= $mod->status ?>', <?= (int)$mod->sequenceOrder ?>)"
                                    class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition">
                                Edit
                            </button>

                            <!-- Delete Module -->
                            <form action="/teacher/modules/<?= (int)$mod->id ?>/delete" method="POST" class="inline"
                                  onsubmit="return confirm('Are you sure you want to delete this module? Activities assigned to it must be removed first.');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="class_subject_id" value="<?= (int)$selectedClassSubjectId ?>">
                                <button type="submit" class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-xl transition" title="Delete Module">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Module Activities List -->
                    <div class="p-5">
                        <?php if (empty($mod->items)): ?>
                            <div class="py-6 px-4 border-2 border-dashed border-slate-200 rounded-xl text-center bg-slate-50/50">
                                <p class="text-xs font-semibold text-slate-500">No learning activities attached to this module yet.</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">Click "Add Activity" above to attach documents, quizzes, or assignments.</p>
                            </div>
                        <?php else: ?>
                            <div class="divide-y divide-slate-100">
                                <?php 
                                    $itemCount = count($mod->items);
                                    foreach ($mod->items as $iIdx => $item): 
                                ?>
                                    <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 group">
                                        <div class="flex items-center gap-3">
                                            <!-- Activity Sequence Number -->
                                            <span class="text-xs font-bold text-slate-400 w-5 text-right">
                                                <?= $iIdx + 1 ?>.
                                            </span>

                                            <!-- Activity Type Badge & Icon -->
                                            <?php if ($item->activityType === 'document'): ?>
                                                <span class="p-2 rounded-xl bg-sky-50 text-sky-700 border border-sky-200">
                                                    <?php if ($item->itemSubtype === 'docx'): ?>
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                    <?php else: ?>
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                        </svg>
                                                    <?php endif; ?>
                                                </span>
                                            <?php elseif ($item->activityType === 'quiz'): ?>
                                                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                                    </svg>
                                                </span>
                                            <?php else: ?>
                                                <span class="p-2 rounded-xl bg-amber-50 text-amber-700 border border-amber-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </span>
                                            <?php endif; ?>

                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-bold text-slate-900">
                                                        <?= htmlspecialchars($item->title) ?>
                                                    </span>
                                                    <span class="inline-flex items-center px-2 py-0.2 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600">
                                                        <?= htmlspecialchars($item->itemSubtype ?: $item->activityType) ?>
                                                    </span>
                                                    <?php if ($item->isRequired): ?>
                                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                            Required
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold uppercase tracking-wider bg-slate-100 text-slate-500">
                                                            Optional
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Activity Actions: Move Up / Down, Remove -->
                                        <div class="flex items-center gap-1.5 self-end sm:self-auto">
                                            <?php if ($iIdx > 0): ?>
                                                <button type="button" title="Move Activity Up"
                                                        onclick="moveActivity(<?= (int)$mod->id ?>, <?= (int)$item->id ?>, -1)"
                                                        class="p-1.5 text-slate-400 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                                    </svg>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($iIdx < $itemCount - 1): ?>
                                                <button type="button" title="Move Activity Down"
                                                        onclick="moveActivity(<?= (int)$mod->id ?>, <?= (int)$item->id ?>, 1)"
                                                        class="p-1.5 text-slate-400 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>
                                            <?php endif; ?>

                                            <form action="/teacher/modules/items/<?= (int)$item->id ?>/delete" method="POST" class="inline"
                                                  onsubmit="return confirm('Remove this activity from the module?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="class_subject_id" value="<?= (int)$selectedClassSubjectId ?>">
                                                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Remove from Module">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ========================================== -->
<!-- MODAL: CREATE MODULE                       -->
<!-- ========================================== -->
<div id="createModuleModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/50">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Create Learning Module</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Add a new instructional unit to this subject.</p>
            </div>
            <button type="button" onclick="closeCreateModuleModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="/teacher/modules" method="POST" class="p-6 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="class_subject_id" value="<?= (int)$selectedClassSubjectId ?>">

            <div>
                <label for="create_module_title" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Module Title <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="title" id="create_module_title" required maxlength="200"
                       placeholder="e.g. Module 1: Introduction to Mechanics"
                       class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
            </div>

            <div>
                <label for="create_module_description" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Description & Objectives
                </label>
                <textarea name="description" id="create_module_description" rows="3"
                          placeholder="Outline the learning goals and expectations for this module..."
                          class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="create_module_status" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Visibility Status
                    </label>
                    <select name="status" id="create_module_status"
                            class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-semibold text-slate-900 transition">
                        <option value="published" selected>Published</option>
                        <option value="draft">Draft (Hidden)</option>
                    </select>
                </div>
                <div>
                    <label for="create_module_order" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Sequence Order
                    </label>
                    <input type="number" name="sequence_order" id="create_module_order" min="1"
                           value="<?= count($modules) + 1 ?>"
                           class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-semibold text-slate-900 transition">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeCreateModuleModal()"
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 rounded-xl transition">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-xs transition">
                    Save Module
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: EDIT MODULE                         -->
<!-- ========================================== -->
<div id="editModuleModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/50">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Edit Learning Module</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Update module properties and status.</p>
            </div>
            <button type="button" onclick="closeEditModuleModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="editModuleForm" action="" method="POST" class="p-6 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="class_subject_id" value="<?= (int)$selectedClassSubjectId ?>">

            <div>
                <label for="edit_module_title" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Module Title <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="title" id="edit_module_title" required maxlength="200"
                       class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
            </div>

            <div>
                <label for="edit_module_description" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Description & Objectives
                </label>
                <textarea name="description" id="edit_module_description" rows="3"
                          class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="edit_module_status" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Visibility Status
                    </label>
                    <select name="status" id="edit_module_status"
                            class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-semibold text-slate-900 transition">
                        <option value="published">Published</option>
                        <option value="draft">Draft (Hidden)</option>
                    </select>
                </div>
                <div>
                    <label for="edit_module_order" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Sequence Order
                    </label>
                    <input type="number" name="sequence_order" id="edit_module_order" min="1"
                           class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-semibold text-slate-900 transition">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeEditModuleModal()"
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 rounded-xl transition">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-xs transition">
                    Update Module
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: ADD ACTIVITY TO MODULE              -->
<!-- ========================================== -->
<div id="addActivityModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-lg overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/50">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Add Learning Activity</h3>
                <p class="text-[11px] text-slate-500 mt-0.5" id="addActivityModalSubtitle">Attach content to module.</p>
            </div>
            <button type="button" onclick="closeAddActivityModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="addActivityForm" action="" method="POST" class="p-6 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="class_subject_id" value="<?= (int)$selectedClassSubjectId ?>">

            <div>
                <label for="modal_activity_type" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Activity Type <span class="text-rose-500">*</span>
                </label>
                <select name="activity_type" id="modal_activity_type" required onchange="renderActivityOptions()"
                        class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-semibold text-slate-900 transition">
                    <option value="document">Document / Study Material</option>
                    <option value="quiz">Online CBT Exam / Quiz</option>
                    <option value="assignment">Coursework Assignment</option>
                </select>
            </div>

            <div>
                <label for="modal_activity_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Select Activity <span class="text-rose-500">*</span>
                </label>
                <select name="activity_id" id="modal_activity_id" required
                        class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
                    <!-- Populated dynamically via JS -->
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="modal_is_required" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Progression Requirement
                    </label>
                    <select name="is_required" id="modal_is_required"
                            class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-semibold text-slate-900 transition">
                        <option value="1" selected>Required for Completion</option>
                        <option value="0">Optional Resource</option>
                    </select>
                </div>
                <div>
                    <label for="modal_item_order" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Order in Module
                    </label>
                    <input type="number" name="sequence_order" id="modal_item_order" min="1" placeholder="Auto"
                           class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-semibold text-slate-900 transition">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeAddActivityModal()"
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 rounded-xl transition">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-xs transition">
                    Attach to Module
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- HIDDEN REORDER FORMS                       -->
<!-- ========================================== -->
<form id="moduleReorderForm" action="/teacher/modules/reorder" method="POST" class="hidden">
    <?= csrf_field() ?>
    <input type="hidden" name="class_subject_id" value="<?= (int)$selectedClassSubjectId ?>">
    <input type="hidden" name="module_ids" id="reorder_module_ids" value="">
</form>

<form id="itemReorderForm" action="" method="POST" class="hidden">
    <?= csrf_field() ?>
    <input type="hidden" name="class_subject_id" value="<?= (int)$selectedClassSubjectId ?>">
    <input type="hidden" name="item_ids" id="reorder_item_ids" value="">
</form>

<script>
    // Available Activities Data from PHP
    const availableActivities = <?= json_encode($availableActivities) ?>;
    const currentModuleOrder = <?= json_encode(array_map(fn($m) => (int)$m->id, $modules)) ?>;
    const currentModuleItems = {
        <?php foreach ($modules as $m): ?>
            <?= (int)$m->id ?>: <?= json_encode(array_map(fn($it) => (int)$it->id, $m->items)) ?>,
        <?php endforeach; ?>
    };

    // Modal Control Functions
    function openCreateModuleModal() {
        document.getElementById('createModuleModal').classList.remove('hidden');
    }
    function closeCreateModuleModal() {
        document.getElementById('createModuleModal').classList.add('hidden');
    }

    function openEditModuleModal(id, title, desc, status, order) {
        const form = document.getElementById('editModuleForm');
        form.action = '/teacher/modules/' + id + '/edit';
        document.getElementById('edit_module_title').value = title;
        document.getElementById('edit_module_description').value = desc;
        document.getElementById('edit_module_status').value = status;
        document.getElementById('edit_module_order').value = order;
        document.getElementById('editModuleModal').classList.remove('hidden');
    }
    function closeEditModuleModal() {
        document.getElementById('editModuleModal').classList.add('hidden');
    }

    function openAddActivityModal(moduleId, moduleTitle) {
        const form = document.getElementById('addActivityForm');
        form.action = '/teacher/modules/' + moduleId + '/items';
        document.getElementById('addActivityModalSubtitle').textContent = 'Module: ' + moduleTitle;
        renderActivityOptions();
        document.getElementById('addActivityModal').classList.remove('hidden');
    }
    function closeAddActivityModal() {
        document.getElementById('addActivityModal').classList.add('hidden');
    }

    function renderActivityOptions() {
        const type = document.getElementById('modal_activity_type').value;
        const select = document.getElementById('modal_activity_id');
        select.innerHTML = '';

        let list = [];
        if (type === 'document') {
            list = availableActivities.documents || [];
        } else if (type === 'quiz') {
            list = availableActivities.quizzes || [];
        } else if (type === 'assignment') {
            list = availableActivities.assignments || [];
        }

        if (list.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'No ' + type + 's found for this subject';
            select.appendChild(opt);
            return;
        }

        list.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.title + (item.is_assigned ? ' (Already in a module)' : '');
            select.appendChild(opt);
        });
    }

    // Reorder Modules
    function moveModule(moduleId, direction) {
        const index = currentModuleOrder.indexOf(moduleId);
        if (index === -1) return;
        const newIndex = index + direction;
        if (newIndex < 0 || newIndex >= currentModuleOrder.length) return;

        // Swap
        const temp = currentModuleOrder[index];
        currentModuleOrder[index] = currentModuleOrder[newIndex];
        currentModuleOrder[newIndex] = temp;

        document.getElementById('reorder_module_ids').value = JSON.stringify(currentModuleOrder);
        document.getElementById('moduleReorderForm').submit();
    }

    // Reorder Activities inside a Module
    function moveActivity(moduleId, itemId, direction) {
        const items = currentModuleItems[moduleId];
        if (!items) return;
        const index = items.indexOf(itemId);
        if (index === -1) return;
        const newIndex = index + direction;
        if (newIndex < 0 || newIndex >= items.length) return;

        // Swap
        const temp = items[index];
        items[index] = items[newIndex];
        items[newIndex] = temp;

        const form = document.getElementById('itemReorderForm');
        form.action = '/teacher/modules/' + moduleId + '/items/reorder';
        document.getElementById('reorder_item_ids').value = JSON.stringify(items);
        form.submit();
    }
</script>
