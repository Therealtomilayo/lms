<?php
/**
 * Student Class Discussions Hub
 *
 * @var \App\Models\Student|null $student
 * @var \App\Models\ClassSubject[] $classSubjects
 * @var \App\Models\AcademicSession|null $activeSession
 * @var \App\Core\UserContext $user
 */
?>

<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-brand-700 text-white flex items-center justify-center shadow-xs flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Class Discussions</h1>
                    <p class="text-xs text-slate-500 mt-1">Connect with your teachers and classmates. Ask questions, participate in study topics, and collaborate.</p>
                </div>
            </div>
            <?php if (!empty($activeSession)): ?>
                <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200 self-start sm:self-auto">
                    Session: <?= htmlspecialchars($activeSession->name) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Subject Discussion Channels Grid -->
    <?php if (empty($classSubjects)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-xs">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            </div>
            <h3 class="text-base font-extrabold text-slate-800">No Enrolled Subjects Available</h3>
            <p class="text-xs text-slate-500 mt-1">You are not currently enrolled in any class subject discussion channels.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($classSubjects as $cs): 
                $subName = $cs->subject?->name ?? 'Subject';
                $code = $cs->subject?->code ?? '';
                $teacherName = $cs->teacher?->user?->name ?? ($cs->teacher?->name ?? 'Subject Teacher');
            ?>
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between hover:shadow-md hover:border-brand-300 transition duration-200 group">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-extrabold bg-sky-50 text-sky-800 border border-sky-200">
                                Enrolled Course
                            </span>
                            <?php if (!empty($code)): ?>
                                <span class="font-mono text-[11px] font-bold text-slate-400 uppercase tracking-wider"><?= htmlspecialchars($code) ?></span>
                            <?php endif; ?>
                        </div>

                        <h3 class="text-base font-extrabold text-slate-900 group-hover:text-brand-600 transition">
                            <?= htmlspecialchars($subName) ?>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                            <span>Instructor:</span>
                            <span class="font-semibold text-slate-700"><?= htmlspecialchars($teacherName) ?></span>
                        </p>
                    </div>

                    <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg>
                            Peer Channel
                        </span>
                        <a href="/student/subjects/<?= (int)$cs->id ?>/discussions" 
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white shadow-xs transition transform active:scale-95">
                            <span>Join Discussion</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
