<?php
/**
 * ADMIN-35 — Class Discussions Oversight & Moderation (SRS §47, §57 Phase 3)
 *
 * @var array $sessions
 * @var int $selectedSessionId
 * @var array $classes
 * @var int|null $selectedClassId
 * @var array $classSubjects
 * @var int $selectedClassSubjectId
 * @var \App\Models\ClassSubject|null $selectedClassSubject
 * @var \App\Models\ClassDiscussion[] $discussions
 * @var int $totalCount
 * @var string $csrf_token
 */
?>
<div class="space-y-6">

    <!-- Header Card -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">
                <a href="/admin/dashboard" class="hover:text-brand-600 transition">Admin Portal</a>
                <span>/</span>
                <span class="text-slate-700 font-bold">Class Discussions</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Class Discussions Oversight &amp; Moderation</h1>
            <p class="text-xs text-slate-500 mt-1">
                SRS §47 Child Safeguarding Compliance: All instructional communication is strictly group-based and cohort-scoped. No 1-on-1 private messaging channels exist.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>Group-Only Audited Feeds</span>
            </span>
        </div>
    </div>

    <!-- Filter Bar: Session, Class & Subject Selector -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="/admin/discussions" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            <!-- Academic Session -->
            <div>
                <label for="session_id" class="text-xs font-bold uppercase tracking-wider text-slate-500 block mb-1.5">Academic Session</label>
                <select id="session_id" name="session_id" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs font-bold rounded-xl border border-slate-300 bg-slate-50 text-slate-800 focus:bg-white focus:border-brand-500 focus:ring-brand-500">
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?= (int)$s->id ?>" <?= $selectedSessionId === (int)$s->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Class Filter -->
            <div>
                <label for="class_id" class="text-xs font-bold uppercase tracking-wider text-slate-500 block mb-1.5">Cohort Class</label>
                <select id="class_id" name="class_id" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs font-bold rounded-xl border border-slate-300 bg-slate-50 text-slate-800 focus:bg-white focus:border-brand-500 focus:ring-brand-500">
                    <option value="">-- All Classes --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= (int)$c->id ?>" <?= $selectedClassId === (int)$c->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c->getFullName()) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Class Subject -->
            <div>
                <label for="class_subject_id" class="text-xs font-bold uppercase tracking-wider text-slate-500 block mb-1.5">Subject Feed</label>
                <select id="class_subject_id" name="class_subject_id" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs font-bold rounded-xl border border-slate-300 bg-slate-50 text-slate-800 focus:bg-white focus:border-brand-500 focus:ring-brand-500">
                    <?php if (empty($classSubjects)): ?>
                        <option value="">No subject feeds found</option>
                    <?php else: ?>
                        <?php foreach ($classSubjects as $cs): ?>
                            <option value="<?= (int)$cs->id ?>" <?= $selectedClassSubjectId === (int)$cs->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cs->schoolClass?->name ?? 'Class') ?> — <?= htmlspecialchars($cs->subject?->name ?? 'Subject') ?> (<?= htmlspecialchars($cs->teacher?->user?->name ?? 'Staff') ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </form>
    </div>

    <!-- Active Class Discussions Feed -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">
                    <?php if ($selectedClassSubject): ?>
                        <?= htmlspecialchars($selectedClassSubject->schoolClass?->name ?? 'Class') ?> — <?= htmlspecialchars($selectedClassSubject->subject?->name ?? 'Subject') ?> Feed
                    <?php else: ?>
                        Discussion Topics
                    <?php endif; ?>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    <?= $totalCount ?> topic(s) posted by instructor and enrolled students.
                </p>
            </div>
        </div>

        <?php if (empty($discussions)): ?>
            <div class="text-center py-12 border-2 border-dashed border-slate-200 rounded-xl">
                <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-500 mx-auto flex items-center justify-center mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-900">No Discussions in This Subject Feed</h3>
                <p class="text-xs text-slate-500 mt-1">There are no instructional questions or peer threads posted yet in this course.</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($discussions as $topic): ?>
                    <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/50 p-3 rounded-xl transition">
                        <div class="space-y-1 min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <?php if ($topic->isPinned): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        Pinned
                                    </span>
                                <?php endif; ?>
                                <?php if ($topic->isLocked): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                        Locked
                                    </span>
                                <?php endif; ?>
                                <a href="/admin/discussions/<?= (int)$selectedClassSubjectId ?>/<?= (int)$topic->id ?>" class="text-sm font-extrabold text-slate-900 hover:text-brand-600 transition truncate">
                                    <?= htmlspecialchars($topic->title) ?>
                                </a>
                            </div>
                            <p class="text-xs text-slate-500 line-clamp-1">
                                <?= htmlspecialchars(strip_tags($topic->content)) ?>
                            </p>
                            <div class="flex items-center gap-3 text-[11px] text-slate-400">
                                <span>Author: <strong class="text-slate-700"><?= htmlspecialchars($topic->author?->name ?? 'User') ?></strong></span>
                                <span>&bull;</span>
                                <span><?= date('M j, Y g:ia', strtotime($topic->createdAt)) ?></span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 flex-shrink-0">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                <span><?= (int)$topic->replyCount ?> replies</span>
                            </span>

                            <a href="/admin/discussions/<?= (int)$selectedClassSubjectId ?>/<?= (int)$topic->id ?>" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition shadow-2xs">
                                Inspect Thread &rarr;
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>
