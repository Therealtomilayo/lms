<?php
/**
 * Parent Child Class Discussions View (SRS §47, §57 Phase 3)
 * Parents monitor their child's instructional discussions across enrolled subjects (Read-Only).
 *
 * @var \App\Models\Student $student
 * @var \App\Models\Student $selectedChild
 * @var \App\Models\Student[] $children
 * @var \App\Models\StudentSubjectEnrollment[] $subjects
 * @var int $selectedClassSubjectId
 * @var array|null $discussionData
 * @var \App\Models\AcademicSession|null $activeSession
 * @var \App\Core\UserContext $user
 */

$childId = (int)$student->id;
$childName = $student->name;
$discussions = $discussionData['discussions'] ?? [];
$selectedCs = $discussionData['class_subject'] ?? null;
$csrfToken = $_SESSION['_csrf_token'] ?? '';
?>

<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-brand-700 text-white font-extrabold text-2xl flex items-center justify-center shadow-xs flex-shrink-0">
                    <?= strtoupper(substr($childName, 0, 1)) ?>
                </div>
                <div>
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                        <a href="/parent/dashboard" class="hover:text-brand-600 transition">Dashboard</a>
                        <span>&rsaquo;</span>
                        <a href="/parent/children/<?= $childId ?>" class="hover:text-brand-600 transition">Child Profile</a>
                        <span>&rsaquo;</span>
                        <span class="text-slate-700 font-bold">Class Discussions</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            <?= htmlspecialchars($childName) ?>'s Class Discussions
                        </h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            Read-Only Oversight
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Supervise subject questions and teacher explanations.</p>
                </div>
            </div>

            <!-- Subject Selector Filter -->
            <?php if (!empty($subjects)): ?>
                <form method="GET" action="/parent/children/<?= $childId ?>/discussions" class="flex items-center gap-2">
                    <label for="class_subject_id" class="text-xs font-bold text-slate-500 whitespace-nowrap">Course Subject:</label>
                    <select name="class_subject_id" id="class_subject_id" onchange="this.form.submit()"
                            class="px-3 py-2 text-sm font-semibold rounded-lg border border-slate-300 bg-white text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                        <?php foreach ($subjects as $se): ?>
                            <option value="<?= (int)$se->classSubjectId ?>" <?= (int)$se->classSubjectId === $selectedClassSubjectId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($se->classSubject->subject->name ?? 'Subject') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Discussions List -->
    <div class="space-y-4">
        <div class="flex items-center justify-between px-1">
            <h2 class="text-base font-bold text-slate-900">
                <?= $selectedCs && $selectedCs->subject ? htmlspecialchars($selectedCs->subject->name) . ' Topics' : 'Topics' ?>
            </h2>
            <span class="text-xs text-slate-400 font-medium">Safe Educational Channel</span>
        </div>

        <?php if (empty($discussions)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-xs">
                <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">No active discussions</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    No discussion topics have been posted in this course yet.
                </p>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($discussions as $topic): ?>
                    <div class="bg-white rounded-2xl border <?= $topic->isPinned ? 'border-amber-300 bg-amber-50/15' : 'border-slate-200' ?> p-5 shadow-xs">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                    <?php if ($topic->isPinned): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                            📌 Pinned
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($topic->isLocked): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                                            🔒 Closed
                                        </span>
                                    <?php endif; ?>
                                    <span class="text-xs text-slate-400">
                                        <?= htmlspecialchars($topic->author->name ?? 'User') ?> &bull; <?= date('M j, Y g:i A', strtotime($topic->createdAt)) ?>
                                    </span>
                                </div>
                                <h3 class="text-base font-bold text-slate-900 leading-snug">
                                    <?= htmlspecialchars($topic->title) ?>
                                </h3>
                                <p class="text-xs text-slate-600 mt-1.5 leading-relaxed whitespace-pre-line">
                                    <?= htmlspecialchars($topic->content) ?>
                                </p>
                            </div>

                            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 font-bold text-xs flex-shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                <?= (int)$topic->replyCount ?> <?= $topic->replyCount === 1 ? 'Reply' : 'Replies' ?>
                            </span>
                        </div>

                        <!-- Nested Replies if any -->
                        <?php if (!empty($topic->replies)): ?>
                            <div class="mt-4 pt-4 border-t border-slate-100 space-y-2.5">
                                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Responses (<?= count($topic->replies) ?>)</h4>
                                <?php foreach ($topic->replies as $reply): ?>
                                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="font-bold text-slate-900"><?= htmlspecialchars($reply->author->name ?? 'User') ?></span>
                                            <span class="text-[11px] text-slate-400"><?= date('M j, g:i A', strtotime($reply->createdAt)) ?></span>
                                        </div>
                                        <div class="text-slate-700 whitespace-pre-line leading-relaxed">
                                            <?= htmlspecialchars($reply->content) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
