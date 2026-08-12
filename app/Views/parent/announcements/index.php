<?php
$selChildId = is_object($selectedChild) ? $selectedChild->id : ($selectedChild['id'] ?? 0);
$selChildName = is_object($selectedChild) ? $selectedChild->name : ($selectedChild['name'] ?? 'Student');
?>
<div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">School & Class Announcements</h1>
            <p class="text-sm text-slate-600 mt-1">Official bulletins, newsletters, and announcements relevant to your children.</p>
        </div>
    </div>

    <!-- Children Selector Tabs -->
    <?php if (count($children) > 1): ?>
        <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
            <?php foreach ($children as $c): 
                $cId = is_object($c) ? $c->id : ($c['id'] ?? 0);
                $cName = is_object($c) ? $c->name : ($c['name'] ?? '');
            ?>
                <a href="/parent/children/<?= (int)$cId ?>/announcements"
                   class="px-4 py-2 rounded-lg text-sm font-semibold transition <?= ($selChildId === (int)$cId) ? 'bg-brand-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                    <?= htmlspecialchars($cName) ?>'s Feed
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Feed -->
    <?php if (empty($feed)): ?>
        <div class="bg-white p-12 text-center rounded-xl border border-slate-200 shadow-sm text-slate-500">
            <svg class="w-12 h-12 mx-auto text-slate-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            <h3 class="text-base font-semibold text-slate-800">No Announcements</h3>
            <p class="text-sm text-slate-500 mt-1">There are no active notices for this feed.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($feed as $item): 
                $isRead = is_object($item) ? $item->isRead : !empty($item['read_at']);
                $targetName = is_object($item) ? ($item->targetName ?? 'School-wide') : ($item['target_name'] ?? 'School-wide');
                $pubDate = is_object($item) ? ($item->publishedAt ?? $item->createdAt) : ($item['published_at'] ?? $item['created_at']);
                $title = is_object($item) ? $item->title : $item['title'];
                $body = is_object($item) ? $item->body : $item['body'];
                $authorName = is_object($item) ? ($item->authorName ?? 'School Administration') : ($item['author_name'] ?? 'School Administration');
                $itemId = is_object($item) ? $item->id : $item['id'];
            ?>
                <div class="p-6 rounded-xl border transition <?= $isRead ? 'bg-white border-slate-200' : 'bg-brand-50/30 border-brand-200 shadow-sm' ?>">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?= $isRead ? 'bg-slate-100 text-slate-700' : 'bg-brand-600 text-white' ?>">
                                <?= htmlspecialchars($targetName) ?>
                            </span>
                            <?php if (!$isRead): ?>
                                <span class="w-2 h-2 rounded-full bg-brand-600 animate-pulse" title="Unread Notice"></span>
                            <?php endif; ?>
                        </div>
                        <span class="text-xs text-slate-400">
                            <?= htmlspecialchars(date('M d, Y H:i', strtotime($pubDate))) ?>
                        </span>
                    </div>

                    <h3 class="text-lg font-bold text-slate-900 mt-3"><?= htmlspecialchars($title) ?></h3>
                    <p class="text-sm text-slate-700 mt-2 whitespace-pre-line leading-relaxed"><?= htmlspecialchars($body) ?></p>

                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span>Posted by: <strong class="text-slate-700"><?= htmlspecialchars($authorName) ?></strong></span>
                        <?php if (!$isRead): ?>
                            <form method="POST" action="/parent/announcements/<?= (int)$itemId ?>/read">
                                <?= csrf_field() ?>
                                <button type="submit" class="font-medium text-brand-600 hover:text-brand-800 hover:underline">
                                    Mark as Read &check;
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="text-emerald-600 font-medium">&check; Read</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
