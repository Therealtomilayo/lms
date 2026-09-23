<?php
/**
 * Teacher Single Discussion Thread & Moderation Screen (SRS §47, §57 Phase 3)
 *
 * @var \App\Models\ClassDiscussion $discussion
 * @var \App\Models\ClassSubject $classSubject
 * @var bool $canModerate
 * @var bool $canReply
 * @var \App\Models\AcademicSession|null $activeSession
 * @var \App\Core\UserContext $user
 */

$csrfToken = $_SESSION['_csrf_token'] ?? '';
$csId = (int)$classSubject->id;
$discId = (int)$discussion->id;
?>

<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Header & Breadcrumbs -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                    <a href="/teacher/dashboard" class="hover:text-brand-600 transition">Dashboard</a>
                    <span>&rsaquo;</span>
                    <a href="/teacher/subjects/<?= $csId ?>/discussions" class="hover:text-brand-600 transition">Discussions</a>
                    <span>&rsaquo;</span>
                    <span class="text-slate-700 font-bold truncate max-w-xs">Thread</span>
                </div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                    <?= htmlspecialchars($discussion->title) ?>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Class: <span class="font-semibold text-slate-800"><?= htmlspecialchars($classSubject->schoolClass->name ?? '') ?> &bull; <?= htmlspecialchars($classSubject->subject->name ?? '') ?></span>
                </p>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <a href="/teacher/subjects/<?= $csId ?>/discussions"
                   class="px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-xs">
                    &larr; All Topics
                </a>

                <?php if ($canModerate): ?>
                    <!-- Pin/Unpin Toggle -->
                    <form method="POST" action="/teacher/subjects/<?= $csId ?>/discussions/<?= $discId ?>/pin" class="inline">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="is_pinned" value="<?= $discussion->isPinned ? '0' : '1' ?>">
                        <button type="submit"
                                class="px-3 py-2 rounded-lg text-xs font-bold transition shadow-xs border <?= $discussion->isPinned ? 'bg-amber-50 text-amber-800 border-amber-300 hover:bg-amber-100' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' ?>">
                            <?= $discussion->isPinned ? '📌 Unpin Topic' : '📌 Pin Topic' ?>
                        </button>
                    </form>

                    <!-- Lock/Unlock Toggle -->
                    <form method="POST" action="/teacher/subjects/<?= $csId ?>/discussions/<?= $discId ?>/lock" class="inline">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="is_locked" value="<?= $discussion->isLocked ? '0' : '1' ?>">
                        <button type="submit"
                                class="px-3 py-2 rounded-lg text-xs font-bold transition shadow-xs border <?= $discussion->isLocked ? 'bg-slate-100 text-slate-800 border-slate-300 hover:bg-slate-200' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' ?>">
                            <?= $discussion->isLocked ? '🔓 Unlock Topic' : '🔒 Lock Topic' ?>
                        </button>
                    </form>

                    <!-- Delete Topic -->
                    <form method="POST" action="/teacher/subjects/<?= $csId ?>/discussions/<?= $discId ?>/delete" class="inline"
                          onsubmit="return confirm('Are you sure you want to delete this discussion topic and all replies?');">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <button type="submit"
                                class="px-3 py-2 rounded-lg text-xs font-bold bg-white text-rose-600 border border-rose-200 hover:bg-rose-50 transition shadow-xs">
                            Delete Topic
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Original Topic Post -->
    <div class="bg-white rounded-xl border <?= $discussion->isPinned ? 'border-amber-300' : 'border-slate-200' ?> p-6 shadow-xs">
        <div class="flex items-center justify-between gap-4 pb-4 border-b border-slate-100 mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-brand-600 text-white font-bold text-sm flex items-center justify-center">
                    <?= strtoupper(substr($discussion->author->name ?? 'U', 0, 1)) ?>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900"><?= htmlspecialchars($discussion->author->name ?? 'User') ?></h3>
                    <span class="text-xs text-slate-400">Author &bull; <?= date('F j, Y g:i A', strtotime($discussion->createdAt)) ?></span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <?php if ($discussion->isPinned): ?>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">📌 Pinned</span>
                <?php endif; ?>
                <?php if ($discussion->isLocked): ?>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700">🔒 Locked</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="prose max-w-none text-slate-800 text-sm leading-relaxed whitespace-pre-line">
            <?= htmlspecialchars($discussion->content) ?>
        </div>
    </div>

    <!-- Replies Section -->
    <div class="space-y-4">
        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2 px-1">
            <span>Replies</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                <?= (int)$discussion->replyCount ?>
            </span>
        </h2>

        <?php if (empty($discussion->replies)): ?>
            <div class="bg-white rounded-xl border border-slate-200 p-8 text-center shadow-xs">
                <p class="text-xs text-slate-500 font-medium">No replies have been posted to this topic yet.</p>
            </div>
        <?php else: ?>
            <div class="relative pl-6 md:pl-8 space-y-4 before:absolute before:left-3 md:before:left-4 before:top-2 before:bottom-6 before:w-0.5 before:bg-brand-200">
                <?php foreach ($discussion->replies as $reply): ?>
                    <div class="relative bg-white rounded-xl border border-slate-200 p-5 shadow-xs hover:border-brand-300 transition">
                        <!-- Connector Node & Horizontal Link -->
                        <div class="absolute -left-6 md:-left-8 top-5 w-3 h-3 rounded-full bg-brand-500 border-2 border-white shadow-xs"></div>
                        <div class="absolute -left-3 md:-left-4 top-6 w-3 md:w-4 h-0.5 bg-brand-200"></div>

                        <div class="flex items-center justify-between gap-4 pb-3 border-b border-slate-100 mb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-800 font-bold text-xs flex items-center justify-center">
                                    <?= strtoupper(substr($reply->author->name ?? 'U', 0, 1)) ?>
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-slate-900"><?= htmlspecialchars($reply->author->name ?? 'User') ?></span>
                                    <span class="text-xs text-slate-400 ml-1">&bull; <?= date('M j, Y g:i A', strtotime($reply->createdAt)) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="text-slate-700 text-sm leading-relaxed whitespace-pre-line">
                            <?= htmlspecialchars($reply->content) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Post Reply Form -->
    <?php if ($canReply): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
            <h3 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                Post a Reply
            </h3>

            <form method="POST" action="/teacher/subjects/<?= $csId ?>/discussions/<?= $discId ?>/replies" class="space-y-4">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div>
                    <textarea name="content" rows="4" required placeholder="Write your response, feedback, or academic explanation..."
                              class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-300 bg-white text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden"></textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="px-5 py-2.5 rounded-lg bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-xs transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        Submit Reply
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="p-4 rounded-xl bg-slate-100 border border-slate-200 text-center">
            <p class="text-xs text-slate-600 font-semibold">
                🔒 This discussion topic is locked by the teacher and is no longer accepting replies.
            </p>
        </div>
    <?php endif; ?>

</div>
