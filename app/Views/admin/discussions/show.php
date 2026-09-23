<?php
/**
 * ADMIN-35 — Moderate Single Class Discussion Thread (SRS §47, §57 Phase 3)
 *
 * @var \App\Models\ClassSubject $classSubject
 * @var \App\Models\ClassDiscussion $discussion
 * @var \App\Models\ClassDiscussionReply[] $replies
 * @var string $csrf_token
 */

$csId = (int)$classSubject->id;
$discId = (int)$discussion->id;
?>
<div class="space-y-6">

    <!-- Header & Breadcrumbs Card -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">
                <a href="/admin/discussions?class_subject_id=<?= $csId ?>" class="hover:text-brand-600 transition">Discussions</a>
                <span>/</span>
                <span class="text-slate-700 font-bold"><?= htmlspecialchars($classSubject->subject?->name ?? 'Subject') ?></span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl font-black text-slate-900 tracking-tight">
                    <?= htmlspecialchars($discussion->title) ?>
                </h1>
                <?php if ($discussion->isPinned): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                        Pinned
                    </span>
                <?php endif; ?>
                <?php if ($discussion->isLocked): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-800 border border-rose-200">
                        Locked
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Class: <?= htmlspecialchars($classSubject->schoolClass?->name ?? 'Class') ?> &bull; Instructor: <?= htmlspecialchars($classSubject->teacher?->user?->name ?? 'Teacher') ?>
            </p>
        </div>

        <!-- Moderation Quick Toolbar -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Pin/Unpin -->
            <form method="POST" action="/admin/discussions/<?= $csId ?>/<?= $discId ?>/pin" class="inline">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 shadow-2xs transition">
                    <?= $discussion->isPinned ? 'Unpin Topic' : 'Pin to Top' ?>
                </button>
            </form>

            <!-- Lock/Unlock -->
            <form method="POST" action="/admin/discussions/<?= $csId ?>/<?= $discId ?>/lock" class="inline">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 shadow-2xs transition">
                    <?= $discussion->isLocked ? 'Unlock Thread' : 'Lock Thread' ?>
                </button>
            </form>

            <!-- Delete Discussion -->
            <form method="POST" action="/admin/discussions/<?= $csId ?>/<?= $discId ?>/delete" class="inline" onsubmit="return confirm('Delete this entire discussion topic and all replies?');">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition">
                    Delete Topic
                </button>
            </form>

            <a href="/admin/discussions?class_subject_id=<?= $csId ?>" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                &larr; Back
            </a>
        </div>
    </div>

    <!-- Original Discussion Post -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-50 border border-brand-200 text-brand-700 font-extrabold flex items-center justify-center">
                    <?= strtoupper(substr($discussion->author?->name ?? 'A', 0, 1)) ?>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900"><?= htmlspecialchars($discussion->author?->name ?? 'Author') ?></h3>
                    <p class="text-[11px] text-slate-400"><?= htmlspecialchars($discussion->author?->email ?? '') ?></p>
                </div>
            </div>
            <span class="text-xs text-slate-400 font-mono"><?= date('M j, Y g:ia', strtotime($discussion->createdAt)) ?></span>
        </div>

        <div class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">
            <?= htmlspecialchars($discussion->content) ?>
        </div>
    </div>

    <!-- Replies Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-700">
                Discussion Replies (<?= count($replies) ?>)
            </h2>
        </div>

        <?php if (empty($replies)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500 text-xs shadow-xs">
                No replies have been posted to this topic yet.
            </div>
        <?php else: ?>
            <div class="relative pl-6 md:pl-8 space-y-4 before:absolute before:left-3 md:before:left-4 before:top-2 before:bottom-6 before:w-0.5 before:bg-brand-200">
                <?php foreach ($replies as $reply): ?>
                    <div class="relative bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3 hover:border-brand-300 transition">
                        <!-- Connector Node & Horizontal Link -->
                        <div class="absolute -left-6 md:-left-8 top-5 w-3 h-3 rounded-full bg-brand-500 border-2 border-white shadow-xs"></div>
                        <div class="absolute -left-3 md:-left-4 top-6 w-3 md:w-4 h-0.5 bg-brand-200"></div>

                        <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-brand-100 text-brand-800 font-bold text-xs flex items-center justify-center">
                                    <?= strtoupper(substr($reply->author?->name ?? 'U', 0, 1)) ?>
                                </div>
                                <div>
                                    <span class="text-xs font-extrabold text-slate-900"><?= htmlspecialchars($reply->author?->name ?? 'User') ?></span>
                                    <span class="text-[10px] text-slate-400 block"><?= htmlspecialchars($reply->author?->email ?? '') ?></span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-[11px] text-slate-400 font-mono"><?= date('M j, Y g:ia', strtotime($reply->createdAt)) ?></span>
                                <!-- Delete Reply Moderation Action -->
                                <form method="POST" action="/admin/discussions/<?= $csId ?>/<?= $discId ?>/replies/<?= (int)$reply->id ?>/delete" class="inline" onsubmit="return confirm('Delete this reply permanently?');">
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <button type="submit" class="text-slate-400 hover:text-rose-600 transition" title="Delete Reply">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="text-xs text-slate-700 leading-relaxed whitespace-pre-line">
                            <?= htmlspecialchars($reply->content) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Administrative Post / Reply Box -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-3">
        <h3 class="text-sm font-extrabold text-slate-900">Post Official Administrative Reply</h3>
        <p class="text-xs text-slate-500">Provide official institutional guidance, clarification, or oversight response into this group channel.</p>

        <form method="POST" action="/admin/discussions/<?= $csId ?>/<?= $discId ?>/replies" class="space-y-3">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <textarea name="content" rows="3" required placeholder="Type administrative response here..." class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-300 focus:border-brand-500 focus:ring-brand-500"></textarea>
            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2 text-xs font-bold bg-brand-600 text-white rounded-xl hover:bg-brand-700 shadow-sm transition">
                    Post Administrative Reply
                </button>
            </div>
        </form>
    </div>

</div>
