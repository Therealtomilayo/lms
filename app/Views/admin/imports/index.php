<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Bulk User Onboarding — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Bulk User Onboarding'
]);

/** @var \App\Models\AcademicSession[] $sessions */
/** @var \App\Models\AcademicSession|null $currentSession */
/** @var \App\Models\SchoolClass[] $classes */
/** @var array $classMappings */
/** @var \App\Models\ImportBatch[] $recentImports */
?>

<div class="space-y-8" id="onboarding-app">
    <!-- Top Header & Actions: Clean institutional style matching User Directory -->
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Bulk Onboarding Workbench</h1>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Browser-Side Engine
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-500 max-w-3xl leading-relaxed">
                Upload school spreadsheets directly in your browser. Map columns flexibly, resolve class cohort arms conservatively, auto-deduplicate parent contacts, and batch-provision student and teacher accounts with live progress.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5 flex-shrink-0">
            <button type="button" onclick="downloadSampleTemplate('students')" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold border border-slate-300 shadow-2xs transition-colors cursor-pointer">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Student Template
            </button>
            <button type="button" onclick="downloadSampleTemplate('teachers')" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold border border-slate-300 shadow-2xs transition-colors cursor-pointer">
                <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Teacher Template
            </button>
            <button type="button" onclick="downloadSampleTemplate('parents')" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold border border-slate-300 shadow-2xs transition-colors cursor-pointer">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Parent / Guardian Template
            </button>
        </div>
    </div>

    <!-- Stepper Navigation -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
        <nav aria-label="Progress">
            <ol role="list" class="flex flex-col md:flex-row items-center justify-between gap-4">
                <li class="w-full md:w-auto flex-1 step-indicator" id="step-nav-1">
                    <div class="flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-[#7B3046] text-white text-sm font-bold shadow-sm">1</span>
                        <div>
                            <p class="text-xs font-bold text-[#7B3046] uppercase tracking-wider">Step 1</p>
                            <p class="text-sm font-semibold text-slate-800">File & Session</p>
                        </div>
                    </div>
                </li>
                <li class="hidden md:block w-8 h-0.5 bg-slate-200"></li>
                <li class="w-full md:w-auto flex-1 step-indicator opacity-50" id="step-nav-2">
                    <div class="flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-slate-200 text-slate-600 text-sm font-bold">2</span>
                        <div>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Step 2</p>
                            <p class="text-sm font-semibold text-slate-800">Column Mapping</p>
                        </div>
                    </div>
                </li>
                <li class="hidden md:block w-8 h-0.5 bg-slate-200"></li>
                <li class="w-full md:w-auto flex-1 step-indicator opacity-50" id="step-nav-3">
                    <div class="flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-slate-200 text-slate-600 text-sm font-bold">3</span>
                        <div>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Step 3</p>
                            <p class="text-sm font-semibold text-slate-800">Class Resolution</p>
                        </div>
                    </div>
                </li>
                <li class="hidden md:block w-8 h-0.5 bg-slate-200"></li>
                <li class="w-full md:w-auto flex-1 step-indicator opacity-50" id="step-nav-4">
                    <div class="flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-slate-200 text-slate-600 text-sm font-bold">4</span>
                        <div>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Step 4</p>
                            <p class="text-sm font-semibold text-slate-800">Chunk Execution</p>
                        </div>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    <!-- WORKBENCH CARDS CONTAINER -->

    <!-- STEP 1: CONFIGURATION & FILE DROP -->
    <div id="step-container-1" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Step 1: Onboarding Target & Spreadsheet File</h2>
            <p class="text-sm text-slate-500">Select what you are onboarding and choose your XLSX, XLS, or CSV spreadsheet.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Target Type Selector -->
            <div>
                <label for="import-type-select" class="block text-sm font-semibold text-slate-700 mb-2">Onboarding Target Model <span class="text-rose-500">*</span></label>
                <select id="import-type-select" class="w-full h-11 px-3.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-800 font-medium focus:ring-2 focus:ring-[#7B3046]/20 focus:border-[#7B3046] shadow-xs">
                    <option value="students" selected>Students (Roster + Parent Contacts + Class Enrollment)</option>
                    <option value="teachers">Teachers (Staff Identity + Teaching Staff Role)</option>
                    <option value="parents">Parents / Guardians (Standalone Contact Profiles)</option>
                </select>
                <p class="mt-1.5 text-xs text-slate-500" id="type-help-text">Student onboarding automatically creates portal accounts, enrolls students in classes and subjects, and deduplicates parent contacts.</p>
            </div>

            <!-- Target Session Selector -->
            <div id="session-select-wrapper">
                <label for="import-session-select" class="block text-sm font-semibold text-slate-700 mb-2">
                    Academic Session <span class="text-rose-500">*</span>
                    <?php if ($currentSession): ?>
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Current: <?= e($currentSession->name) ?></span>
                    <?php endif; ?>
                </label>
                <select id="import-session-select" class="w-full h-11 px-3.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-800 font-medium focus:ring-2 focus:ring-[#7B3046]/20 focus:border-[#7B3046] shadow-xs">
                    <?php foreach ($sessions as $session): ?>
                        <option value="<?= e($session->id) ?>" <?= ($currentSession && $session->id === $currentSession->id) ? 'selected' : '' ?>>
                            <?= e($session->name) ?> (<?= ucfirst($session->status) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="mt-1.5 text-xs text-slate-500">Determines the session for class enrollments and automatic subject registrations.</p>
            </div>
        </div>

        <!-- Drag & Drop Zone -->
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Spreadsheet Document <span class="text-rose-500">*</span></label>
            <div id="drop-zone" class="border-2 border-dashed border-slate-300 hover:border-[#7B3046] rounded-2xl p-8 text-center transition cursor-pointer bg-slate-50/50 hover:bg-rose-50/20 group">
                <input type="file" id="file-input" accept=".xlsx, .xls, .csv" class="hidden">
                <div class="max-w-md mx-auto space-y-3 pointer-events-none">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-white shadow-sm border border-slate-200 flex items-center justify-center text-slate-400 group-hover:text-[#7B3046] group-hover:border-rose-200 transition">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800">
                            <span class="text-[#7B3046] underline">Click to choose a file</span> or drag & drop here
                        </p>
                        <p class="text-xs text-slate-500 mt-1">Supports Microsoft Excel (.xlsx, .xls) and Comma-Separated Values (.csv)</p>
                    </div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-200/60 text-slate-600 text-xs font-semibold">
                        Browser-side parsing • Fast • Private
                    </div>
                </div>
            </div>

            <!-- Selected File Metadata Bar (Hidden by default) -->
            <div id="file-meta-bar" class="hidden mt-4 p-4 rounded-xl bg-slate-100 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">
                        XLS
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-900" id="meta-filename">—</p>
                        <p class="text-xs text-slate-500" id="meta-filesize">—</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div id="sheet-picker-wrapper" class="hidden flex items-center gap-2">
                        <label for="sheet-select" class="text-xs font-semibold text-slate-600">Worksheet:</label>
                        <select id="sheet-select" class="text-xs font-semibold py-1 px-2.5 bg-white border border-slate-300 rounded-md"></select>
                    </div>
                    <button type="button" id="btn-remove-file" class="text-xs font-semibold text-rose-600 hover:text-rose-800 px-2 py-1 cursor-pointer">Change File</button>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t border-slate-100">
            <button type="button" id="btn-to-step-2" disabled class="px-6 py-2.5 rounded-xl bg-[#7B3046] text-white font-semibold text-sm shadow-sm hover:bg-[#5F2234] disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center gap-2 cursor-pointer">
                Continue to Column Mapping
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </div>
    </div>

    <!-- STEP 2: COLUMN MAPPING & DATA PREVIEW -->
    <div id="step-container-2" class="hidden bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Step 2: Column Mapping Matrix</h2>
                <p class="text-sm text-slate-500">Confirm how columns in your spreadsheet connect to Claret LMS fields. Auto-matched fields are highlighted.</p>
            </div>
            <div class="text-xs font-semibold text-slate-500 px-3 py-1.5 rounded-lg bg-slate-100 border border-slate-200">
                <span id="preview-row-count" class="font-bold text-slate-900">0</span> total rows detected
            </div>
        </div>

        <!-- Mapping Matrix Grid -->
        <div class="bg-slate-50/70 p-5 rounded-xl border border-slate-200">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="mapping-fields-grid">
                <!-- Dynamically populated via JS -->
            </div>
        </div>

        <!-- 5-Row Data Preview Table -->
        <div class="space-y-2">
            <h3 class="text-sm font-bold text-slate-800">Preliminary Row Preview (First 5 Rows)</h3>
            <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-xs">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs" id="preview-table">
                    <thead class="bg-slate-100 text-slate-700 font-semibold" id="preview-thead">
                        <!-- Populated by JS -->
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white font-medium text-slate-700" id="preview-tbody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <button type="button" id="btn-back-to-step-1" class="px-5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition cursor-pointer">
                Back
            </button>
            <button type="button" id="btn-to-step-3" class="px-6 py-2.5 rounded-xl bg-[#7B3046] text-white font-semibold text-sm shadow-sm hover:bg-[#5F2234] transition flex items-center gap-2 cursor-pointer">
                Continue to Class Resolution
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </div>
    </div>

    <!-- STEP 3: CLASS RESOLUTION & AMBIGUITY RESOLVER -->
    <div id="step-container-3" class="hidden bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Step 3: Class Cohort & Arm Resolution</h2>
                <p class="text-sm text-slate-500">Ensure spreadsheet class names match the exact structural cohort arms in Claret LMS without false matching.</p>
            </div>
            <div id="class-resolution-badge" class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                Checking classes...
            </div>
        </div>

        <div id="class-resolution-alert" class="hidden p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-sm">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <p class="font-bold">Class mapping attention required</p>
                    <p class="text-xs text-amber-800 mt-0.5" id="class-resolution-alert-text">
                        Some class strings in your spreadsheet (e.g. non-canonical grades or arms) are not yet mapped. Please map each raw string to the appropriate canonical class arm below.
                    </p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-xs">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-100 text-slate-700 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Raw Class in Spreadsheet</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Mapped LMS Class Arm</th>
                        <th class="px-6 py-3.5 text-right">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white font-medium text-slate-700" id="class-resolution-tbody">
                    <!-- Populated dynamically via JS -->
                </tbody>
            </table>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" id="chk-save-class-mappings" checked class="w-4 h-4 text-[#7B3046] rounded border-slate-300 focus:ring-[#7B3046]">
            <label for="chk-save-class-mappings" class="text-xs font-semibold text-slate-700 cursor-pointer">
                Save manual class mappings permanently so they are auto-applied in future imports
            </label>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <button type="button" id="btn-back-to-step-2" class="px-5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition cursor-pointer">
                Back
            </button>
            <button type="button" id="btn-start-execution" class="px-6 py-2.5 rounded-xl bg-[#7B3046] text-white font-semibold text-sm shadow-sm hover:bg-[#5F2234] transition flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Begin Bulk Import
            </button>
        </div>
    </div>

    <!-- STEP 4: LIVE CHUNK EXECUTION PROGRESS -->
    <div id="step-container-4" class="hidden bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        <div>
            <h2 class="text-lg font-bold text-slate-900" id="exec-title">Import Execution in Progress</h2>
            <p class="text-sm text-slate-500" id="exec-subtitle">Sending validated rows to the Claret SMS server in isolated transactional chunks...</p>
        </div>

        <!-- Progress Bar -->
        <div class="space-y-2">
            <div class="flex items-center justify-between text-xs font-bold text-slate-700">
                <span id="exec-progress-label">Preparing chunk 1...</span>
                <span id="exec-progress-percent">0%</span>
            </div>
            <div class="w-full h-3.5 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                <div id="exec-progress-bar" class="h-full bg-[#7B3046] transition-all duration-300 rounded-full" style="width: 0%"></div>
            </div>
        </div>

        <!-- Real-Time Metrics KPI Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-center">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Processed</p>
                <p class="text-2xl font-black text-slate-900 mt-1" id="kpi-processed">0</p>
            </div>
            <div class="bg-emerald-50/60 p-4 rounded-xl border border-emerald-200 text-center">
                <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Created</p>
                <p class="text-2xl font-black text-emerald-700 mt-1" id="kpi-created">0</p>
            </div>
            <div class="bg-sky-50/60 p-4 rounded-xl border border-sky-200 text-center">
                <p class="text-xs font-semibold text-sky-700 uppercase tracking-wider">Updated</p>
                <p class="text-2xl font-black text-sky-700 mt-1" id="kpi-updated">0</p>
            </div>
            <div class="bg-rose-50/60 p-4 rounded-xl border border-rose-200 text-center">
                <p class="text-xs font-semibold text-rose-700 uppercase tracking-wider">Errors / Conflicts</p>
                <p class="text-2xl font-black text-rose-700 mt-1" id="kpi-errors">0</p>
            </div>
        </div>

        <!-- Live Status Terminal / Log -->
        <div class="bg-slate-900 rounded-xl p-4 font-mono text-xs text-slate-300 max-h-48 overflow-y-auto space-y-1" id="exec-log">
            <p class="text-emerald-400">&gt; Initializing browser-to-server bulk pipeline...</p>
        </div>

        <!-- Post-Execution Action Controls (Hidden while executing) -->
        <div id="post-exec-actions" class="hidden flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-slate-100">
            <div class="flex items-center gap-3">
                <button type="button" id="btn-export-credentials" class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold text-sm shadow-sm hover:bg-emerald-700 transition flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download Generated Credentials (.csv)
                </button>
                <a id="btn-download-error-csv" href="#" class="hidden px-5 py-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 font-semibold text-sm hover:bg-rose-100 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Download Error Report (.csv)
                </a>
            </div>
            <button type="button" onclick="location.reload()" class="px-5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition cursor-pointer">
                Start Another Import
            </button>
        </div>
    </div>

    <!-- RECENT IMPORT HISTORY TABLE -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Recent Import Batches</h3>
                <p class="text-xs text-slate-500">Audit trail of past bulk onboarding operations</p>
            </div>
        </div>
        <?php if (empty($recentImports)): ?>
            <div class="p-8 text-center text-slate-500 text-sm">
                No previous bulk import batches recorded yet.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3.5">Batch</th>
                            <th class="px-6 py-3.5">Type</th>
                            <th class="px-6 py-3.5">File Name</th>
                            <th class="px-6 py-3.5">Rows (Total / Valid / Invalid)</th>
                            <th class="px-6 py-3.5">Status</th>
                            <th class="px-6 py-3.5">Date</th>
                            <th class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-medium text-slate-700">
                        <?php foreach ($recentImports as $imp): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 font-mono font-bold text-slate-900">#<?= e($imp->id) ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700">
                                        <?= ucfirst(e($imp->type)) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-600 truncate max-w-xs font-normal"><?= e($imp->originalName) ?></td>
                                <td class="px-6 py-4 font-normal">
                                    <span class="font-bold text-slate-900"><?= e($imp->totalRows) ?></span> total
                                    (<span class="text-emerald-700 font-semibold"><?= e($imp->validRows) ?> valid</span>, 
                                     <span class="text-rose-700 font-semibold"><?= e($imp->invalidRows) ?> invalid</span>)
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($imp->status === 'committed'): ?>
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Committed</span>
                                    <?php elseif ($imp->status === 'failed'): ?>
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">Failed</span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200"><?= ucfirst(e($imp->status)) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-slate-500 font-normal"><?= e($imp->createdAt ? date('M j, Y H:i', strtotime($imp->createdAt)) : '—') ?></td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <?php if ($imp->invalidRows > 0): ?>
                                        <a href="/admin/imports/<?= e($imp->id) ?>/errors.csv" class="inline-flex items-center text-xs font-semibold text-rose-600 hover:text-rose-800 underline">
                                            Errors CSV
                                        </a>
                                    <?php endif; ?>
                                    <a href="/admin/imports/<?= e($imp->id) ?>/review" class="inline-flex items-center text-xs font-semibold text-[#7B3046] hover:text-[#5F2234] underline">
                                        Review
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Friendly Session Expiration Modal -->
<div id="session-expired-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 text-center space-y-4 border border-slate-200">
        <div class="w-14 h-14 mx-auto rounded-full bg-rose-100 text-rose-600 flex items-center justify-center">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <div>
            <h3 class="text-lg font-bold text-slate-900">Your Session Has Expired</h3>
            <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                Your login session expired due to inactivity. Your parsed spreadsheet data is still safe in your browser!
            </p>
            <p class="text-xs text-slate-600 mt-2 font-medium">
                Please open the login portal in a new tab, sign in to renew your credentials, then click <strong>Resume Import</strong> below.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 pt-2">
            <a href="/login" target="_blank" class="flex-1 py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center justify-center gap-1.5 border border-slate-200">
                Log In in New Tab
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
            <button type="button" onclick="closeSessionModalAndRetry()" class="flex-1 py-2.5 px-4 rounded-xl bg-[#7B3046] hover:bg-[#5F2234] text-white text-xs font-bold transition cursor-pointer shadow-sm">
                Resume Import
            </button>
        </div>
    </div>
</div>

<!-- PINNED LOCAL SHEETJS 0.20.3 SCRIPT -->
<script src="/assets/js/vendor/xlsx.full.min.js"></script>

<script>
window.CSRF_TOKEN = '<?= csrf_token() ?>';
window.AVAILABLE_CLASSES = <?= json_encode(array_map(fn($c) => [
    'id' => $c->id,
    'name' => $c->name,
    'section_arm' => $c->sectionArm,
    'display' => $c->name . ($c->sectionArm ? " ({$c->sectionArm})" : ''),
], $classes), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

// Target Field Definitions
const TARGET_SCHEMA = {
    students: [
        { key: 'admission_number', label: 'Admission Number', required: true, aliases: ['admission_number', 'admission number', 'adm_no', 'adm no', 'admno', 'reg_no', 'reg no', 'student_id'] },
        { key: 'first_name', label: 'First Name', required: true, aliases: ['first_name', 'firstname', 'first name', 'first', 'given_name'] },
        { key: 'last_name', label: 'Last Name / Surname', required: true, aliases: ['last_name', 'lastname', 'last name', 'surname', 'family_name'] },
        { key: 'other_names', label: 'Other / Middle Names', required: false, aliases: ['other_names', 'other names', 'middle_name', 'middlename', 'middle'] },
        { key: 'name', label: 'Full Name (Combined)', required: false, aliases: ['name', 'full_name', 'fullname', 'student_name'] },
        { key: 'class_name', label: 'Class / Arm', required: true, aliases: ['class_name', 'class name', 'class', 'grade', 'classroom', 'arm', 'class arm'] },
        { key: 'gender', label: 'Gender', required: false, aliases: ['gender', 'sex'] },
        { key: 'date_of_birth', label: 'Date of Birth', required: false, aliases: ['date_of_birth', 'date of birth', 'dob', 'birth_date', 'birthdate'] },
        { key: 'state_of_origin', label: 'State of Origin', required: false, aliases: ['state_of_origin', 'state of origin', 'state', 'origin_state'] },
        { key: 'lga', label: 'L.G.A.', required: false, aliases: ['lga', 'l_g_a', 'local_government', 'local government', 'local_gov', 'local govt'] },
        { key: 'religion', label: 'Religion', required: false, aliases: ['religion', 'faith', 'denomination'] },
        { key: 'nationality', label: 'Nationality', required: false, aliases: ['nationality', 'country', 'citizen', 'citizenship'] },
        { key: 'admission_date', label: 'Admission Date', required: false, aliases: ['admission_date', 'admission date', 'enrollment_date', 'date_of_admission'] },
        { key: 'email', label: 'Student Email (Optional)', required: false, aliases: ['email', 'student_email'] },
        { key: 'parent_name', label: 'Parent / Guardian Name', required: false, aliases: ['parent_name', 'parent name', 'guardian_name', 'guardian name', 'parent', 'guardian'] },
        { key: 'parent_phone', label: 'Parent Phone Number', required: false, aliases: ['parent_phone', 'parent phone', 'parent_contact', 'guardian_phone', 'phone_number'] },
        { key: 'parent_email', label: 'Parent Email', required: false, aliases: ['parent_email', 'parent email', 'guardian_email'] },
        { key: 'parent_relationship', label: 'Parent Relationship', required: false, aliases: ['parent_relationship', 'relationship', 'relation'] },
    ],
    teachers: [
        { key: 'staff_id', label: 'Staff ID', required: true, aliases: ['staff_id', 'staff id', 'staffid', 'employee_id'] },
        { key: 'name', label: 'Full Name', required: true, aliases: ['name', 'full_name', 'fullname', 'teacher_name', 'staff_name'] },
        { key: 'first_name', label: 'First Name', required: false, aliases: ['first_name', 'firstname', 'first name'] },
        { key: 'last_name', label: 'Last Name', required: false, aliases: ['last_name', 'lastname', 'last name', 'surname'] },
        { key: 'email', label: 'Email Address', required: true, aliases: ['email', 'email_address', 'e-mail'] },
        { key: 'phone', label: 'Phone Number', required: false, aliases: ['phone', 'phone_number', 'mobile'] },
        { key: 'gender', label: 'Gender', required: false, aliases: ['gender', 'sex'] },
    ],
    parents: [
        { key: 'name', label: 'Full Name', required: true, aliases: ['name', 'full_name', 'fullname', 'parent_name', 'guardian_name'] },
        { key: 'first_name', label: 'First Name', required: false, aliases: ['first_name', 'firstname', 'first name'] },
        { key: 'last_name', label: 'Last Name', required: false, aliases: ['last_name', 'lastname', 'last name', 'surname'] },
        { key: 'phone', label: 'Phone Number', required: false, aliases: ['phone', 'phone_number', 'mobile', 'parent_phone'] },
        { key: 'email', label: 'Email Address', required: false, aliases: ['email', 'email_address', 'parent_email'] },
        { key: 'student_admission_number', label: 'Child Admission No(s). (Comma-separated)', required: false, aliases: ['student_admission_number', 'student_adm_no', 'child_adm_no', 'admission_number', 'children_admission_numbers', 'wards', 'student_id'] },
        { key: 'relationship', label: 'Relationship', required: false, aliases: ['relationship', 'relation', 'parent_relationship'] },
    ]
};

// Workbench State
let currentFile = null;
let currentFileSha256 = null;
let parsedWorkbook = null;
let currentRawRows = [];
let sheetHeaders = [];
let columnMappings = {};
let classResolutionData = { matched: {}, unmatched: [], ambiguous: {}, available_classes: [] };
let activeBatchId = null;
let collectedCredentials = [];
let pendingActionCallback = null;

document.addEventListener('DOMContentLoaded', () => {
    initDropzone();
    initNavigationButtons();
});

/**
 * Robust Client DOB Normalizer
 * Expands 2-digit year representations (e.g. 12/5/00 -> 2000-05-12).
 */
function normalizeDobClient(val) {
    if (!val) return '';
    const str = String(val).trim();
    if (!str) return '';

    // Already YYYY-MM-DD
    if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
        return str;
    }

    // Match 2-digit year: d/m/yy or d-m-yy (e.g. 12/5/00 or 12/05/00)
    const m2 = str.match(/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{2})$/);
    if (m2) {
        const d = parseInt(m2[1], 10);
        const m = parseInt(m2[2], 10);
        const rawY = parseInt(m2[3], 10);
        const y = rawY <= 35 ? (2000 + rawY) : (1900 + rawY);
        return `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    }

    // Match 4-digit year at end: d/m/yyyy or d-m-yyyy
    const m4 = str.match(/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/);
    if (m4) {
        const d = parseInt(m4[1], 10);
        const m = parseInt(m4[2], 10);
        const y = m4[3];
        return `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    }

    return str;
}

/**
 * Standard Authenticated API Fetch Wrapper with Same-Origin Credentials & 401 Interception
 */
async function apiFetch(url, options = {}) {
    options.credentials = 'same-origin';
    options.headers = Object.assign({
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': window.CSRF_TOKEN,
        'Accept': 'application/json'
    }, options.headers || {});

    const res = await fetch(url, options);

    if (res.status === 401 || res.status === 419) {
        showSessionExpiredModal();
        throw new Error('Your session has expired. Please log in to continue.');
    }

    let json;
    try {
        json = await res.json();
    } catch (e) {
        throw new Error(`Server returned invalid response (Status ${res.status}).`);
    }

    if (!res.ok || !json.success) {
        throw new Error(json.error || `Server request failed with status ${res.status}.`);
    }

    return json;
}

function showSessionExpiredModal(retryCallback = null) {
    pendingActionCallback = retryCallback;
    document.getElementById('session-expired-modal').classList.remove('hidden');
}

function closeSessionModalAndRetry() {
    document.getElementById('session-expired-modal').classList.add('hidden');
    if (typeof pendingActionCallback === 'function') {
        const cb = pendingActionCallback;
        pendingActionCallback = null;
        cb();
    } else {
        // Default retry class resolution if in step 3
        checkClassResolution();
    }
}

function initDropzone() {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');
    const btnRemove = document.getElementById('btn-remove-file');

    dropZone.addEventListener('click', () => fileInput.click());

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-[#7B3046]', 'bg-rose-50/40');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('border-[#7B3046]', 'bg-rose-50/40');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-[#7B3046]', 'bg-rose-50/40');
        if (e.dataTransfer.files.length > 0) {
            handleFile(e.dataTransfer.files[0]);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFile(e.target.files[0]);
        }
    });

    btnRemove.addEventListener('click', () => {
        resetFile();
    });

    document.getElementById('sheet-select').addEventListener('change', (e) => {
        loadSheet(e.target.value);
    });
}

async function handleFile(file) {
    if (!file) return;
    const name = file.name.toLowerCase();
    if (!name.endsWith('.xlsx') && !name.endsWith('.xls') && !name.endsWith('.csv')) {
        alert('Please select an Excel (.xlsx, .xls) or CSV (.csv) file.');
        return;
    }

    currentFile = file;

    // Show meta bar
    document.getElementById('meta-filename').textContent = file.name;
    document.getElementById('meta-filesize').textContent = (file.size / 1024).toFixed(1) + ' KB';
    document.getElementById('drop-zone').classList.add('hidden');
    document.getElementById('file-meta-bar').classList.remove('hidden');

    // Compute SHA-256
    const buffer = await file.arrayBuffer();
    const hashBuffer = await crypto.subtle.digest('SHA-256', buffer);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    currentFileSha256 = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');

    // Parse with SheetJS
    try {
        parsedWorkbook = XLSX.read(buffer, {
            type: 'array',
            cellDates: true,
            dateNF: 'yyyy-mm-dd',
            cellNF: false,
            cellText: false
        });
        const sheetPickerWrapper = document.getElementById('sheet-picker-wrapper');
        const sheetSelect = document.getElementById('sheet-select');

        if (parsedWorkbook.SheetNames.length > 1) {
            sheetSelect.innerHTML = parsedWorkbook.SheetNames.map(s => `<option value="${s}">${s}</option>`).join('');
            sheetPickerWrapper.classList.remove('hidden');
        } else {
            sheetPickerWrapper.classList.add('hidden');
        }

        loadSheet(parsedWorkbook.SheetNames[0]);
        document.getElementById('btn-to-step-2').disabled = false;
    } catch (err) {
        alert('Failed reading spreadsheet file: ' + err.message);
        resetFile();
    }
}

function loadSheet(sheetName) {
    const worksheet = parsedWorkbook.Sheets[sheetName];
    // Read raw data as array of objects with explicit date formatting
    const jsonData = XLSX.utils.sheet_to_json(worksheet, {
        defval: '',
        raw: false,
        dateNF: 'yyyy-mm-dd'
    });

    if (!jsonData || jsonData.length === 0) {
        alert(`Worksheet '${sheetName}' contains no data rows.`);
        return;
    }

    currentRawRows = jsonData;
    sheetHeaders = Object.keys(jsonData[0] || {});
    document.getElementById('preview-row-count').textContent = currentRawRows.length;

    setupMappingMatrix();
}

function resetFile() {
    currentFile = null;
    currentFileSha256 = null;
    parsedWorkbook = null;
    currentRawRows = [];
    sheetHeaders = [];
    document.getElementById('file-input').value = '';
    document.getElementById('drop-zone').classList.remove('hidden');
    document.getElementById('file-meta-bar').classList.add('hidden');
    document.getElementById('btn-to-step-2').disabled = true;
}

function setupMappingMatrix() {
    const type = document.getElementById('import-type-select').value;
    const schema = TARGET_SCHEMA[type] || TARGET_SCHEMA.students;
    const container = document.getElementById('mapping-fields-grid');
    container.innerHTML = '';
    columnMappings = {};

    schema.forEach(field => {
        // Auto-match best header
        let matchedHeader = '';
        for (const h of sheetHeaders) {
            const cleanH = h.toLowerCase().trim().replace(/[^a-z0-9]/g, '_');
            const cleanHRaw = h.toLowerCase().trim();
            if (field.aliases.includes(cleanH) || field.aliases.includes(cleanHRaw)) {
                matchedHeader = h;
                break;
            }
        }
        if (matchedHeader) {
            columnMappings[field.key] = matchedHeader;
        }

        const div = document.createElement('div');
        div.className = 'bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between';
        
        let sampleVal = '—';
        if (matchedHeader && currentRawRows[0]) {
            let rawSample = currentRawRows[0][matchedHeader] || '—';
            if (field.key === 'date_of_birth') {
                rawSample = normalizeDobClient(rawSample);
            }
            sampleVal = rawSample;
        }

        div.innerHTML = `
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="text-xs font-bold text-slate-800">${field.label}</label>
                    ${field.required ? '<span class="text-2xs font-bold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200">Required</span>' : '<span class="text-2xs font-medium text-slate-400">Optional</span>'}
                </div>
                <select data-field-key="${field.key}" class="mapping-select w-full text-xs font-semibold px-2.5 py-2 bg-white border ${matchedHeader ? 'border-emerald-400 bg-emerald-50/20' : 'border-slate-300'} rounded-lg focus:ring-2 focus:ring-[#7B3046]/20 focus:border-[#7B3046]">
                    <option value="">-- Do Not Map --</option>
                    ${sheetHeaders.map(h => `<option value="${escapeHtml(h)}" ${h === matchedHeader ? 'selected' : ''}>${escapeHtml(h)}</option>`).join('')}
                </select>
            </div>
            <p class="text-2xs text-slate-500 mt-2 truncate">Sample: <span class="font-mono font-semibold text-slate-700">${escapeHtml(String(sampleVal))}</span></p>
        `;
        container.appendChild(div);
    });

    // Listen to manual changes
    container.querySelectorAll('.mapping-select').forEach(sel => {
        sel.addEventListener('change', (e) => {
            const key = e.target.getAttribute('data-field-key');
            const val = e.target.value;
            if (val) {
                columnMappings[key] = val;
                e.target.classList.add('border-emerald-400', 'bg-emerald-50/20');
                e.target.classList.remove('border-slate-300');
            } else {
                delete columnMappings[key];
                e.target.classList.remove('border-emerald-400', 'bg-emerald-50/20');
                e.target.classList.add('border-slate-300');
            }
            renderPreviewTable();
        });
    });

    renderPreviewTable();
}

function renderPreviewTable() {
    const type = document.getElementById('import-type-select').value;
    const schema = TARGET_SCHEMA[type] || TARGET_SCHEMA.students;
    const thead = document.getElementById('preview-thead');
    const tbody = document.getElementById('preview-tbody');

    const mappedKeys = schema.filter(f => columnMappings[f.key]);

    thead.innerHTML = `
        <tr>
            <th class="px-3.5 py-2.5">#</th>
            ${mappedKeys.map(f => `<th class="px-3.5 py-2.5">${escapeHtml(f.label)}</th>`).join('')}
        </tr>
    `;

    const sampleRows = currentRawRows.slice(0, 5);
    tbody.innerHTML = sampleRows.map((row, idx) => `
        <tr class="hover:bg-slate-50">
            <td class="px-3.5 py-2.5 font-mono text-slate-400">${idx + 1}</td>
            ${mappedKeys.map(f => {
                let rawVal = row[columnMappings[f.key]] ?? '';
                if (f.key === 'date_of_birth' && rawVal) {
                    rawVal = normalizeDobClient(rawVal);
                }
                return `<td class="px-3.5 py-2.5 font-mono text-slate-800">${escapeHtml(String(rawVal))}</td>`;
            }).join('')}
        </tr>
    `).join('');
}

function initNavigationButtons() {
    // Step 1 -> Step 2
    document.getElementById('btn-to-step-2').addEventListener('click', () => {
        switchStep(2);
    });

    // Step 2 -> Step 1
    document.getElementById('btn-back-to-step-1').addEventListener('click', () => {
        switchStep(1);
    });

    // Step 2 -> Step 3
    document.getElementById('btn-to-step-3').addEventListener('click', () => {
        const type = document.getElementById('import-type-select').value;
        if (type === 'students') {
            if (!columnMappings['class_name']) {
                alert('Please map a column to "Class / Arm" before continuing.');
                return;
            }
            switchStep(3);
            checkClassResolution();
        } else {
            // For teachers/parents, jump directly to Step 4
            switchStep(4);
            prepareExecution();
        }
    });

    // Step 3 -> Step 2
    document.getElementById('btn-back-to-step-2').addEventListener('click', () => {
        switchStep(2);
    });

    // Step 3 -> Step 4
    document.getElementById('btn-start-execution').addEventListener('click', () => {
        // Check if any classes unresolved
        const unmappedSelects = document.querySelectorAll('.class-resolution-select');
        let hasUnmapped = false;
        unmappedSelects.forEach(s => {
            if (!s.value) hasUnmapped = true;
        });

        if (hasUnmapped) {
            if (!confirm('Some spreadsheet classes are unassigned. Students in unmapped classes will be flagged as errors during chunk import. Do you want to proceed?')) {
                return;
            }
        }

        switchStep(4);
        prepareExecution();
    });
}

function switchStep(stepNumber) {
    [1, 2, 3, 4].forEach(s => {
        const container = document.getElementById(`step-container-${s}`);
        const nav = document.getElementById(`step-nav-${s}`);
        if (s === stepNumber) {
            container.classList.remove('hidden');
            if (nav) {
                nav.classList.remove('opacity-50');
                const badge = nav.querySelector('span');
                badge.className = 'flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-[#7B3046] text-white text-sm font-bold shadow-sm';
            }
        } else {
            container.classList.add('hidden');
        }
    });
}

async function checkClassResolution() {
    const classCol = columnMappings['class_name'];
    const sessionId = document.getElementById('import-session-select').value;

    const uniqueClasses = Array.from(new Set(currentRawRows.map(r => String(r[classCol] || '').trim()).filter(Boolean)));
    const tbody = document.getElementById('class-resolution-tbody');
    const badge = document.getElementById('class-resolution-badge');
    const alertBox = document.getElementById('class-resolution-alert');

    tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-slate-500 font-medium">Resolving classes with LMS database...</td></tr>';

    try {
        const json = await apiFetch('/admin/imports/resolve-classes', {
            method: 'POST',
            body: JSON.stringify({
                class_strings: uniqueClasses,
                session_id: sessionId
            })
        });

        classResolutionData = json.data;
        renderClassResolutionTable();
    } catch (err) {
        if (err.message.includes('session has expired') || err.message.includes('Unauthenticated')) {
            showSessionExpiredModal(() => checkClassResolution());
        }
        tbody.innerHTML = `<tr><td colspan="4" class="px-6 py-6 text-center text-rose-700 font-bold bg-rose-50/50">Error: ${escapeHtml(err.message)}</td></tr>`;
    }
}

function renderClassResolutionTable() {
    const tbody = document.getElementById('class-resolution-tbody');
    const badge = document.getElementById('class-resolution-badge');
    const alertBox = document.getElementById('class-resolution-alert');
    const alertText = document.getElementById('class-resolution-alert-text');
    const { matched, unmatched, ambiguous, available_classes } = classResolutionData;

    let hasAmbiguity = (ambiguous && Object.keys(ambiguous).length > 0) || (unmatched && unmatched.length > 0);

    if (hasAmbiguity) {
        badge.className = 'px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-300';
        badge.textContent = 'Action Required';
        const unCount = (unmatched ? unmatched.length : 0);
        const ambCount = (ambiguous ? Object.keys(ambiguous).length : 0);
        alertText.textContent = `${unCount + ambCount} class string(s) from your spreadsheet are not exact matches in the database. Please select which existing Claret LMS class arm they should map to below.`;
        alertBox.classList.remove('hidden');
    } else {
        badge.className = 'px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-300';
        badge.textContent = 'All Classes Matched';
        alertBox.classList.add('hidden');
    }

    const allKeys = [...Object.keys(matched || {}), ...(unmatched || []), ...Object.keys(ambiguous || {})];
    const uniqueKeys = Array.from(new Set(allKeys));

    if (uniqueKeys.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-6 text-center text-slate-500">No class entries found in spreadsheet.</td></tr>';
        return;
    }

    tbody.innerHTML = uniqueKeys.map(raw => {
        let statusBadge = '';
        let selectedId = '';
        let note = '';

        if (matched && matched[raw]) {
            selectedId = matched[raw].canonical_class_id;
            statusBadge = '<span class="inline-flex items-center gap-1 text-emerald-700 font-bold"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Exact Match</span>';
            note = matched[raw].section_arm ? `Matched arm ${matched[raw].section_arm}` : 'Exact class match';
        } else if (ambiguous && ambiguous[raw]) {
            statusBadge = '<span class="inline-flex items-center gap-1 text-amber-700 font-bold"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg> Ambiguous</span>';
            note = ambiguous[raw].reason || 'Multiple arms exist';
        } else {
            statusBadge = '<span class="inline-flex items-center gap-1 text-rose-700 font-bold"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> Unmatched</span>';
            note = 'Not found in database';
        }

        return `
            <tr class="hover:bg-slate-50 transition">
                <td class="px-6 py-4 font-bold font-mono text-slate-800">${escapeHtml(raw)}</td>
                <td class="px-6 py-4">${statusBadge}</td>
                <td class="px-6 py-4">
                    <select data-raw-class="${escapeHtml(raw)}" class="class-resolution-select text-xs font-semibold py-2 px-3 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#7B3046]/20 focus:border-[#7B3046] w-full max-w-sm">
                        <option value="">-- Choose Canonical LMS Class Arm --</option>
                        ${available_classes.map(c => `
                            <option value="${c.id}" ${c.id === selectedId ? 'selected' : ''}>
                                ${escapeHtml(c.display)}
                            </option>
                        `).join('')}
                    </select>
                </td>
                <td class="px-6 py-4 text-slate-500 text-xs font-normal text-right">${escapeHtml(note)}</td>
            </tr>
        `;
    }).join('');
}

async function prepareExecution() {
    const type = document.getElementById('import-type-select').value;
    const sessionId = type === 'students' ? parseInt(document.getElementById('import-session-select').value) : null;
    const chkSave = document.getElementById('chk-save-class-mappings');
    const log = document.getElementById('exec-log');
    log.innerHTML = '';

    addLog('Initiating import batch registration...', 'info');

    // Save class mappings if needed
    if (type === 'students' && chkSave && chkSave.checked) {
        const manualMappings = {};
        document.querySelectorAll('.class-resolution-select').forEach(s => {
            const raw = s.getAttribute('data-raw-class');
            const val = parseInt(s.value);
            if (raw && val > 0) {
                manualMappings[raw] = val;
            }
        });

        if (Object.keys(manualMappings).length > 0) {
            addLog(`Persisting ${Object.keys(manualMappings).length} class cohort mapping rules...`, 'info');
            try {
                await apiFetch('/admin/imports/save-class-mappings', {
                    method: 'POST',
                    body: JSON.stringify({ mappings: manualMappings })
                });
            } catch (e) {
                console.warn('Failed saving class mappings:', e);
            }
        }
    }

    // Initialize Batch on Server
    try {
        const initJson = await apiFetch('/admin/imports/init', {
            method: 'POST',
            body: JSON.stringify({
                type: type,
                file_name: currentFile.name,
                sha256: currentFileSha256,
                session_id: sessionId,
                total_rows: currentRawRows.length
            })
        });

        activeBatchId = initJson.data.import_id;
        addLog(`Registered batch #${activeBatchId} on server. Starting chunk pipeline...`, 'success');

        executeChunks();
    } catch (err) {
        addLog(`Fatal Error initializing batch: ${err.message}`, 'error');
        alert('Failed starting import: ' + err.message);
    }
}

async function executeChunks() {
    const CHUNK_SIZE = 250;
    const totalRows = currentRawRows.length;
    const totalChunks = Math.ceil(totalRows / CHUNK_SIZE);

    let createdTotal = 0;
    let updatedTotal = 0;
    let errorTotal = 0;
    let processedTotal = 0;
    collectedCredentials = [];

    const type = document.getElementById('import-type-select').value;

    for (let chunkIdx = 0; chunkIdx < totalChunks; chunkIdx++) {
        const chunkNumber = chunkIdx + 1;
        const start = chunkIdx * CHUNK_SIZE;
        const end = Math.min(start + CHUNK_SIZE, totalRows);
        const slice = currentRawRows.slice(start, end);

        // Normalize rows using column mappings and date sanitizer
        const normalizedRows = slice.map((rawRow, i) => {
            const rowNumber = start + i + 2; // +1 for 0-index, +1 for header row
            const data = {};
            for (const [targetKey, sheetHeader] of Object.entries(columnMappings)) {
                let cellVal = rawRow[sheetHeader] ?? '';
                if (targetKey === 'date_of_birth' && cellVal) {
                    cellVal = normalizeDobClient(cellVal);
                }
                data[targetKey] = cellVal;
            }
            return {
                row_number: rowNumber,
                data: data
            };
        });

        const percent = Math.round(((chunkIdx) / totalChunks) * 100);
        updateProgress(percent, `Processing chunk ${chunkNumber} of ${totalChunks} (${slice.length} rows)...`);
        addLog(`Transmitting Chunk ${chunkNumber}/${totalChunks} (Rows ${start + 1} to ${end})...`, 'info');

        try {
            const json = await apiFetch(`/admin/imports/${activeBatchId}/process-chunk`, {
                method: 'POST',
                body: JSON.stringify({
                    chunk_number: chunkNumber,
                    total_chunks: totalChunks,
                    rows: normalizedRows
                })
            });

            const data = json.data;
            createdTotal += (data.created_count || 0);
            updatedTotal += (data.updated_count || 0);
            errorTotal += (data.invalid_count || 0);
            processedTotal += (data.valid_count + data.invalid_count);

            if (data.credentials && data.credentials.length > 0) {
                collectedCredentials.push(...data.credentials);
            }

            // Update KPI counters
            document.getElementById('kpi-processed').textContent = processedTotal;
            document.getElementById('kpi-created').textContent = createdTotal;
            document.getElementById('kpi-updated').textContent = updatedTotal;
            document.getElementById('kpi-errors').textContent = errorTotal;

            if (data.errors && data.errors.length > 0) {
                addLog(`Chunk ${chunkNumber}: ${data.valid_count} valid, ${data.invalid_count} errors.`, 'warn');
            } else {
                addLog(`Chunk ${chunkNumber} committed cleanly (${data.valid_count} records processed).`, 'success');
            }
        } catch (err) {
            addLog(`Error processing chunk ${chunkNumber}: ${err.message}`, 'error');
            alert(`Chunk ${chunkNumber} encountered an error: ${err.message}\nProcessing halted.`);
            return;
        }
    }

    // Finalize Batch
    updateProgress(100, 'Finalizing import session...');
    addLog('Committing final import metrics and updating audit log...', 'info');

    try {
        await apiFetch(`/admin/imports/${activeBatchId}/finalize`, {
            method: 'POST'
        });
        addLog('Batch successfully finalized and committed!', 'success');
    } catch (e) {
        console.warn('Finalize warning:', e);
    }

    document.getElementById('exec-title').textContent = 'Import Completed Successfully!';
    document.getElementById('exec-subtitle').textContent = `Total rows: ${totalRows} | Created: ${createdTotal} | Updated: ${updatedTotal} | Errors: ${errorTotal}`;

    // Enable download buttons
    const postExec = document.getElementById('post-exec-actions');
    postExec.classList.remove('hidden');

    if (errorTotal > 0) {
        const btnErr = document.getElementById('btn-download-error-csv');
        btnErr.href = `/admin/imports/${activeBatchId}/errors.csv`;
        btnErr.classList.remove('hidden');
    }

    const btnCreds = document.getElementById('btn-export-credentials');
    if (collectedCredentials.length > 0) {
        btnCreds.classList.remove('hidden');
        btnCreds.onclick = () => exportCredentialsCsv(collectedCredentials);
    } else {
        btnCreds.classList.add('hidden');
    }
}

function updateProgress(percent, label) {
    document.getElementById('exec-progress-bar').style.width = `${percent}%`;
    document.getElementById('exec-progress-percent').textContent = `${percent}%`;
    document.getElementById('exec-progress-label').textContent = label;
}

function addLog(message, type = 'info') {
    const log = document.getElementById('exec-log');
    const p = document.createElement('p');
    if (type === 'success') p.className = 'text-emerald-400 font-semibold';
    else if (type === 'warn') p.className = 'text-amber-400';
    else if (type === 'error') p.className = 'text-rose-400 font-bold';
    else p.className = 'text-slate-300';
    p.textContent = `> ${message}`;
    log.appendChild(p);
    log.scrollTop = log.scrollHeight;
}

function exportCredentialsCsv(creds) {
    if (!creds || creds.length === 0) return;
    const header = ['Identifier / Admission No', 'Full Name', 'Portal Email', 'Temporary Password', 'Role', 'Assigned Class'];
    const rows = creds.map(c => [
        `"${(c.identifier || '').replace(/"/g, '""')}"`,
        `"${(c.name || '').replace(/"/g, '""')}"`,
        `"${(c.email || '').replace(/"/g, '""')}"`,
        `"${(c.temp_password || '').replace(/"/g, '""')}"`,
        `"${(c.role || '').replace(/"/g, '""')}"`,
        `"${(c.class || '').replace(/"/g, '""')}"`
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,' + [header.join(','), ...rows.map(r => r.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `claret_credentials_import_${activeBatchId}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function downloadSampleTemplate(type) {
    let headers = [];
    let samples = [];
    if (type === 'students') {
        headers = ['admission_number', 'first_name', 'last_name', 'other_names', 'gender', 'date_of_birth', 'state_of_origin', 'lga', 'religion', 'nationality', 'admission_date', 'class_name', 'parent_name', 'parent_phone', 'parent_email', 'parent_relationship'];
        samples = [
            ['CIS-2026-001', 'Chidi', 'Okeke', 'Emeka', 'Male', '2010-04-15', 'Enugu', 'Nkanu West', 'Christianity', 'Nigerian', '2023-09-11', 'JSS 1 A', 'Mr. Emmanuel Okeke', '08031234567', 'e.okeke@gmail.com', 'Father'],
            ['CIS-2026-002', 'Amina', 'Bello', 'Zainab', 'Female', '2009-08-22', 'Kaduna', 'Zaria', 'Islam', 'Nigerian', '2022-09-12', 'SSS 1 Gold', 'Hajiya Fatima Bello', '08029876543', 'fatima.bello@yahoo.com', 'Mother'],
            ['CIS-2026-003', 'Oluwaseun', 'Adeyemi', '', 'Male', '2011-11-05', 'Ogun', 'Abeokuta South', 'Christianity', 'Nigerian', '2024-09-09', 'Basic 4 Diamond', 'Pastor David Adeyemi', '08055551234', 'david.adeyemi@claret.org', 'Guardian']
        ];
    } else if (type === 'teachers') {
        headers = ['staff_id', 'first_name', 'last_name', 'email', 'phone', 'gender'];
        samples = [
            ['TCH-2026-001', 'Grace', 'Okafor', 'grace.okafor@claret.edu.ng', '08061112233', 'Female'],
            ['TCH-2026-002', 'Ibrahim', 'Musa', 'ibrahim.musa@claret.edu.ng', '08072223344', 'Male']
        ];
    } else if (type === 'parents') {
        headers = ['name', 'phone', 'email', 'student_admission_number', 'relationship'];
        samples = [
            ['Mr. Emmanuel Okeke', '08031234567', 'e.okeke@gmail.com', 'CIS-2026-001, CIS-2026-005', 'Father'],
            ['Hajiya Fatima Bello', '08029876543', 'fatima.bello@yahoo.com', 'CIS-2026-002', 'Mother'],
            ['Pastor David Adeyemi', '08055551234', 'david.adeyemi@claret.org', 'CIS-2026-003, CIS-2026-004', 'Guardian']
        ];
    }

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...samples.map(s => s.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `claret_${type}_sample_template.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
