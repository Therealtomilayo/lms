<?php
/**
 * Admin Create Announcement View (ADMIN-21)
 *
 * @var string $title
 * @var string $headerTitle
 * @var array $classes
 * @var array $classSubjects
 * @var string $csrf_token
 */

$csrfToken = $csrf_token ?? ($_SESSION['_csrf_token'] ?? '');
$defaultPublishedAt = date('Y-m-d\TH:i');
?>

<div class="max-w-5xl mx-auto space-y-6">

    <!-- Page Header & Breadcrumb Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1" aria-label="Breadcrumb">
                    <a href="/admin/dashboard" class="hover:text-brand-600 transition-colors">Admin</a>
                    <svg class="w-3 h-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                    <a href="/admin/announcements" class="hover:text-brand-600 transition-colors">Announcements</a>
                    <svg class="w-3 h-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-brand-600">New Broadcast</span>
                </nav>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Broadcast Institutional Announcement</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Publish an official bulletin, class-level notification, or subject group update across the school community.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <?php
                echo $this->render('components/button', [
                    'label' => 'Back to List',
                    'variant' => 'secondary',
                    'href' => '/admin/announcements',
                    'icon' => '<svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>',
                ]);
                ?>
            </div>
        </div>
    </div>

    <!-- Main Grid Layout: Form + Audience Info Sidebar -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Form Card (2 Cols on Large screens) -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-xs p-6 md:p-8">
            <form method="POST" action="/admin/announcements" id="createAnnouncementForm" class="space-y-6">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <!-- Target Audience Scope Selection -->
                <div class="space-y-4 pb-6 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-700 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Audience Scope & Targeting</h2>
                            <p class="text-xs text-slate-500">Determine who will receive this broadcast notice</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <!-- Audience Scope Dropdown -->
                        <div>
                            <label for="scopeSelect" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Target Scope <span class="text-rose-500">*</span>
                            </label>
                            <select
                                name="scope"
                                id="scopeSelect"
                                onchange="toggleScopeInputs()"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-50/50 border border-slate-300 rounded-lg text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                            >
                                <option value="school" selected>School-wide (All Portals)</option>
                                <option value="class">Specific Class</option>
                                <option value="class_subject">Specific Subject Group</option>
                            </select>
                        </div>

                        <!-- Target Class Dropdown (Conditionally Enabled) -->
                        <div id="classSelectWrap" class="hidden">
                            <label for="classScopeId" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Select Class <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="classScopeId"
                                class="w-full px-3.5 py-2.5 bg-slate-50/50 border border-slate-300 rounded-lg text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                            >
                                <option value="">-- Choose Class --</option>
                                <?php foreach ($classes as $cls): ?>
                                    <option value="<?= (int)$cls->id ?>"><?= htmlspecialchars($cls->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Target Subject Group Dropdown (Conditionally Enabled) -->
                        <div id="subjectSelectWrap" class="hidden">
                            <label for="subjectScopeId" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Select Class Subject <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="subjectScopeId"
                                class="w-full px-3.5 py-2.5 bg-slate-50/50 border border-slate-300 rounded-lg text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                            >
                                <option value="">-- Choose Subject Group --</option>
                                <?php foreach ($classSubjects as $cs): ?>
                                    <option value="<?= (int)$cs->id ?>"><?= htmlspecialchars($cs->className ?? 'Class') ?> &mdash; <?= htmlspecialchars($cs->subjectName ?? 'Subject') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Announcement Details Section -->
                <div class="space-y-4 pb-6 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Announcement Content</h2>
                            <p class="text-xs text-slate-500">Provide a clear headline and body text</p>
                        </div>
                    </div>

                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Announcement Headline <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="title"
                            id="title"
                            required
                            placeholder="e.g., 2026/2027 First Term Examination Timetable & Guidelines"
                            class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-300 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                        >
                    </div>

                    <!-- Body -->
                    <div>
                        <label for="body" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Message Body <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            name="body"
                            id="body"
                            rows="6"
                            required
                            placeholder="Enter the full announcement text here. You can include paragraphs, guidelines, instructions, or meeting schedules..."
                            class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-300 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                        ></textarea>
                    </div>
                </div>

                <!-- Schedule & Lifecycle Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Publication Schedule</h2>
                            <p class="text-xs text-slate-500">Configure immediate broadcast or schedule for later release</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                        <!-- Published Date / Time -->
                        <div>
                            <label for="published_at" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Publication Date & Time
                            </label>
                            <input
                                type="datetime-local"
                                name="published_at"
                                id="published_at"
                                value="<?= htmlspecialchars($defaultPublishedAt) ?>"
                                class="w-full px-3.5 py-2.5 bg-slate-50/50 border border-slate-300 rounded-lg text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                            >
                            <p class="text-[11px] text-slate-400 mt-1">Leave as current time to publish immediately</p>
                        </div>

                        <!-- Expiry Date / Time -->
                        <div>
                            <label for="expires_at" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Expiration Date (Optional)
                            </label>
                            <input
                                type="datetime-local"
                                name="expires_at"
                                id="expires_at"
                                class="w-full px-3.5 py-2.5 bg-slate-50/50 border border-slate-300 rounded-lg text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                            >
                            <p class="text-[11px] text-slate-400 mt-1">Notice will automatically archive after this date</p>
                        </div>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="pt-6 border-t border-slate-200 flex items-center justify-end gap-3">
                    <a
                        href="/admin/announcements"
                        class="px-4 py-2.5 rounded-lg text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors min-h-[44px] inline-flex items-center justify-center"
                    >
                        Cancel
                    </a>

                    <?php
                    echo $this->render('components/button', [
                        'label' => 'Broadcast Announcement',
                        'variant' => 'primary',
                        'type' => 'submit',
                        'icon' => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>',
                    ]);
                    ?>
                </div>
            </form>
        </div>

        <!-- Sidebar Information Card (1 Col) -->
        <div class="space-y-6">

            <!-- Audience Summary Info Box -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-6 space-y-4">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-md bg-brand-50 text-brand-700 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">Broadcast Guidelines</h3>
                </div>

                <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
                        <strong class="text-slate-800 block font-semibold">1. School-Wide Scope:</strong>
                        <p class="text-slate-500">Delivered across the portal to Administrators, Teachers, Students, and Parents.</p>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
                        <strong class="text-slate-800 block font-semibold">2. Class-Level Scope:</strong>
                        <p class="text-slate-500">Visible only to students enrolled in that specific class, their assigned teachers, and linked parents.</p>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
                        <strong class="text-slate-800 block font-semibold">3. Subject Scope:</strong>
                        <p class="text-slate-500">Targeted exclusively to students taking that class subject and the subject teacher.</p>
                    </div>
                </div>
            </div>

            <!-- Best Practices Card -->
            <div class="bg-blue-50/60 rounded-xl border border-blue-100 p-6 space-y-2">
                <h4 class="text-xs font-bold text-blue-900 uppercase tracking-wider">Communication Tip</h4>
                <p class="text-xs text-blue-700 leading-relaxed">
                    For time-sensitive announcements (e.g. PTA meetings, mid-term breaks), always specify an <strong>Expiration Date</strong> so older notices clear automatically from student and parent dashboards.
                </p>
            </div>

        </div>
    </div>
</div>

<script>
function toggleScopeInputs() {
    const scopeSelect = document.getElementById('scopeSelect');
    if (!scopeSelect) return;

    const scope = scopeSelect.value;
    const classWrap = document.getElementById('classSelectWrap');
    const subjectWrap = document.getElementById('subjectSelectWrap');
    const classSelect = document.getElementById('classScopeId');
    const subjectSelect = document.getElementById('subjectScopeId');

    if (scope === 'school') {
        if (classWrap) classWrap.classList.add('hidden');
        if (subjectWrap) subjectWrap.classList.add('hidden');
        if (classSelect) {
            classSelect.name = '';
            classSelect.removeAttribute('required');
        }
        if (subjectSelect) {
            subjectSelect.name = '';
            subjectSelect.removeAttribute('required');
        }
    } else if (scope === 'class') {
        if (classWrap) classWrap.classList.remove('hidden');
        if (subjectWrap) subjectWrap.classList.add('hidden');
        if (classSelect) {
            classSelect.name = 'scope_id';
            classSelect.setAttribute('required', 'required');
        }
        if (subjectSelect) {
            subjectSelect.name = '';
            subjectSelect.removeAttribute('required');
        }
    } else if (scope === 'class_subject') {
        if (classWrap) classWrap.classList.add('hidden');
        if (subjectWrap) subjectWrap.classList.remove('hidden');
        if (classSelect) {
            classSelect.name = '';
            classSelect.removeAttribute('required');
        }
        if (subjectSelect) {
            subjectSelect.name = 'scope_id';
            subjectSelect.setAttribute('required', 'required');
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    toggleScopeInputs();
});
</script>
