<?php
/**
 * Student Class Subject Discussions Feed (SRS §47, §57 Phase 3)
 *
 * @var \App\Models\ClassSubject $classSubject
 * @var \App\Models\ClassDiscussion[] $discussions
 * @var int $totalCount
 * @var bool $canPost
 * @var \App\Models\AcademicSession|null $activeSession
 * @var \App\Core\UserContext $user
 */

$csrfToken = $_SESSION['_csrf_token'] ?? '';
$csId = (int)$classSubject->id;
?>

<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                    <a href="/student/dashboard" class="hover:text-brand-600 transition">Dashboard</a>
                    <span>&rsaquo;</span>
                    <a href="/student/subjects/<?= $csId ?>" class="hover:text-brand-600 transition">
                        <?= htmlspecialchars($classSubject->subject->name ?? 'Subject') ?>
                    </a>
                    <span>&rsaquo;</span>
                    <span class="text-slate-700 font-bold">Discussions</span>
                </div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                    <?= htmlspecialchars($classSubject->subject->name ?? 'Subject') ?> Class Discussions
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Group instructional questions and peer discussions monitored by your teacher.
                </p>
            </div>

            <div>
                <a href="/student/subjects/<?= $csId ?>"
                   class="px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-xs inline-flex items-center gap-1.5">
                    &larr; Subject Workspace
                </a>
            </div>
        </div>
    </div>

    <!-- Layout: Feed + Ask Question Card -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Feed (2 Cols) -->
        <div class="lg:col-span-2 space-y-3">
            <div class="flex items-center justify-between px-1">
                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">
                    Questions & Topics (<?= (int)$totalCount ?>)
                </h2>
                <span class="text-xs text-slate-400 font-medium">Safe Class Environment</span>
            </div>

            <?php if (empty($discussions)): ?>
                <div class="bg-white rounded-xl border border-slate-200 p-12 text-center shadow-xs">
                    <div class="w-12 h-12 rounded-xl bg-sky-50 text-brand-600 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">No questions asked yet</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                        Have a question about this subject? Use the form to start a discussion with your teacher and classmates.
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($discussions as $topic): ?>
                    <div class="bg-white rounded-xl border <?= $topic->isPinned ? 'border-amber-300 bg-amber-50/15' : 'border-slate-200' ?> p-5 shadow-xs hover:border-brand-400 transition">
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
                                        <?= htmlspecialchars($topic->author->name ?? 'Student') ?> &bull; <?= date('M j, Y g:i A', strtotime($topic->createdAt)) ?>
                                    </span>
                                </div>
                                <h3 class="text-base font-bold text-slate-900 leading-snug">
                                    <a href="/student/subjects/<?= $csId ?>/discussions/<?= (int)$topic->id ?>" class="hover:text-brand-600 transition">
                                        <?= htmlspecialchars($topic->title) ?>
                                    </a>
                                </h3>
                                <p class="text-xs text-slate-600 mt-1.5 line-clamp-2 leading-relaxed">
                                    <?= htmlspecialchars($topic->content) ?>
                                </p>
                            </div>

                            <a href="/student/subjects/<?= $csId ?>/discussions/<?= (int)$topic->id ?>"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-brand-50 hover:text-brand-700 text-slate-700 font-bold text-xs transition flex-shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                <?= (int)$topic->replyCount ?> <?= $topic->replyCount === 1 ? 'Reply' : 'Replies' ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Ask Question Form (1 Col) -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs sticky top-6">
                <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-100">
                    <div class="w-9 h-9 rounded-xl bg-sky-50 text-brand-600 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Ask a Question</h2>
                        <p class="text-xs text-slate-400">Post to your class group</p>
                    </div>
                </div>

                <form method="POST" action="/student/subjects/<?= $csId ?>/discussions" class="space-y-4">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div>
                        <label for="title" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Question Title *</label>
                        <input type="text" name="title" id="title" required placeholder="What do you need help with?"
                               class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 bg-white text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                    </div>

                    <div>
                        <label for="content" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Details / Explanation *</label>
                        <textarea name="content" id="content" rows="4" required placeholder="Provide details about what you're asking..."
                                  class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 bg-white text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden"></textarea>
                    </div>

                    <button type="submit"
                            class="w-full py-2.5 px-4 rounded-lg bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-xs transition flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        Post Question
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
