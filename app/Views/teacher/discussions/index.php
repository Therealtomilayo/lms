<?php
/**
 * Teacher Class Discussions Feed (SRS §47, §57 Phase 3)
 * Controlled class group communication feed with moderation tools (Pin, Lock, Delete).
 *
 * @var \App\Models\ClassSubject $classSubject
 * @var \App\Models\ClassDiscussion[] $discussions
 * @var int $totalCount
 * @var bool $canModerate
 * @var bool $canPost
 * @var \App\Models\AcademicSession|null $activeSession
 * @var \App\Core\UserContext $user
 */

$csrfToken = $_SESSION['_csrf_token'] ?? '';
$csId = (int)$classSubject->id;
?>

<div class="space-y-6">
    <!-- Header & Breadcrumbs -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                    <a href="/teacher/dashboard" class="hover:text-brand-600 transition">Dashboard</a>
                    <span>&rsaquo;</span>
                    <a href="/teacher/modules?class_subject_id=<?= $csId ?>" class="hover:text-brand-600 transition">Modules</a>
                    <span>&rsaquo;</span>
                    <span class="text-slate-700 font-bold">Class Discussions</span>
                </div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                    <?= htmlspecialchars($classSubject->subject->name ?? 'Subject') ?> Discussions
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Class: <span class="font-semibold text-slate-800"><?= htmlspecialchars($classSubject->schoolClass->name ?? '') ?><?= !empty($classSubject->schoolClass->sectionArm) ? ' (' . htmlspecialchars($classSubject->schoolClass->sectionArm) . ')' : '' ?></span> &bull; Safe, group-monitored instructional dialogue.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="/teacher/modules?class_subject_id=<?= $csId ?>"
                   class="px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-xs">
                    &larr; Return to Modules
                </a>
            </div>
        </div>
    </div>

    <!-- Main Grid: Topics Feed + New Topic Form -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Discussion Topics Feed (2 cols) -->
        <div class="lg:col-span-2 space-y-3">
            <div class="flex items-center justify-between px-1">
                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">
                    All Topics (<?= (int)$totalCount ?>)
                </h2>
                <span class="text-xs text-slate-400 font-medium">Group Communication Only</span>
            </div>

            <?php if (empty($discussions)): ?>
                <div class="bg-white rounded-xl border border-slate-200 p-12 text-center shadow-xs">
                    <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">No discussions posted yet</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                        Start the first topic using the form to engage your learners in academic questions and subject clarifications.
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($discussions as $topic): ?>
                    <div class="bg-white rounded-xl border <?= $topic->isPinned ? 'border-amber-300 bg-amber-50/20' : 'border-slate-200' ?> p-5 shadow-xs hover:border-brand-400 transition">
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
                                            🔒 Locked
                                        </span>
                                    <?php endif; ?>
                                    <span class="text-xs text-slate-400">
                                        Posted by <strong class="text-slate-700"><?= htmlspecialchars($topic->author->name ?? 'User') ?></strong> &bull; <?= date('M j, Y g:i A', strtotime($topic->createdAt)) ?>
                                    </span>
                                </div>
                                <h3 class="text-base font-bold text-slate-900 leading-snug">
                                    <a href="/teacher/subjects/<?= $csId ?>/discussions/<?= (int)$topic->id ?>" class="hover:text-brand-600 transition">
                                        <?= htmlspecialchars($topic->title) ?>
                                    </a>
                                </h3>
                                <p class="text-xs text-slate-600 mt-1.5 line-clamp-2 leading-relaxed">
                                    <?= htmlspecialchars($topic->content) ?>
                                </p>
                            </div>

                            <div class="flex flex-col items-end gap-2 flex-shrink-0">
                                <a href="/teacher/subjects/<?= $csId ?>/discussions/<?= (int)$topic->id ?>"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-brand-50 hover:text-brand-700 text-slate-700 font-bold text-xs transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                    <?= (int)$topic->replyCount ?> <?= $topic->replyCount === 1 ? 'Reply' : 'Replies' ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Create Topic Form (1 col) -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs sticky top-6">
                <div class="flex items-center gap-3 mb-5 pb-4 border-b border-slate-100">
                    <div class="w-10 h-10 rounded-xl bg-sky-50 text-brand-600 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Start Discussion</h2>
                        <p class="text-xs text-slate-500">Post a new topic for this class</p>
                    </div>
                </div>

                <form method="POST" action="/teacher/subjects/<?= $csId ?>/discussions" class="space-y-4">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div>
                        <label for="title" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Topic Title *</label>
                        <input type="text" name="title" id="title" required placeholder="e.g. Clarification on Cell Division..."
                               class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-300 bg-white text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                    </div>

                    <div>
                        <label for="content" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Description / Prompt *</label>
                        <textarea name="content" id="content" rows="5" required placeholder="Provide prompt details or instruction for the class..."
                                  class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-300 bg-white text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden"></textarea>
                    </div>

                    <button type="submit"
                            class="w-full py-2.5 px-4 rounded-lg bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-xs transition flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        Publish Discussion Topic
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
