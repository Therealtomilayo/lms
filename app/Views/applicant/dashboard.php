<?php
/**
 * Applicant Dashboard Home
 * 
 * @var \App\Models\AdmissionSession|null $activeSession
 * @var bool $isAdmissionOpen
 * @var \App\Models\AdmissionApplication[] $applications
 * @var \App\Models\User $user
 */
$this->layout('layouts/applicant', ['title' => 'Dashboard']);
?>

<div class="space-y-6">

    <!-- Welcome Hero Banner -->
    <div class="rounded-3xl bg-gradient-to-r from-[#4A1525] via-[#7B3046] to-[#9B3B58] p-6 sm:p-8 text-white shadow-xl shadow-[#7B3046]/10 relative overflow-hidden">
        <div class="absolute -right-12 -bottom-12 w-64 h-64 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
        <div class="relative z-10 max-w-2xl">
            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-white/15 text-pink-200 backdrop-blur-md mb-3">
                Admission Portal • Guardian Session
            </span>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold tracking-tight">
                Welcome, <?= e($user->name) ?>
            </h1>
            <p class="mt-2 text-sm text-pink-100/90 leading-relaxed">
                Manage your prospective ward admission applications, track review milestones, and complete enrollment requirements from this central dashboard.
            </p>
        </div>
    </div>

    <!-- Active Applications List -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="font-serif text-lg font-bold text-slate-900">My Applications</h2>
                <p class="text-xs text-slate-500 mt-0.5">Summary of wards submitted or in draft for admission.</p>
            </div>
            <a href="/applicant/application" 
               class="inline-flex items-center gap-2 rounded-xl bg-[#7B3046] px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#5F2234] transition-colors shrink-0">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add / Manage Wards</span>
            </a>
        </div>

        <?php if (empty($applications)): ?>
            <div class="p-12 text-center">
                <div class="inline-flex size-14 rounded-2xl bg-slate-100 text-slate-400 items-center justify-center mb-3">
                    <svg class="size-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="font-bold text-slate-800 text-sm">No Applications Started Yet</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    Click the button above to begin adding your prospective ward and start your admission application.
                </p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200/80">
                        <tr>
                            <th class="py-3.5 px-5">Application No.</th>
                            <th class="py-3.5 px-5">Admission Session</th>
                            <th class="py-3.5 px-5">Wards Registered</th>
                            <th class="py-3.5 px-5">Status</th>
                            <th class="py-3.5 px-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($applications as $app): ?>
                            <?php $badge = $app->getStatusBadge(); ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-4 px-5 font-mono font-bold text-slate-900">
                                    <?= e($app->applicationNumber) ?>
                                </td>
                                <td class="py-4 px-5 text-slate-700">
                                    <?= e($app->admissionSession?->title ?? 'Current Session') ?>
                                </td>
                                <td class="py-4 px-5 text-slate-700">
                                    <?php if (empty($app->wards)): ?>
                                        <span class="text-slate-400 italic">No wards added yet</span>
                                    <?php else: ?>
                                        <div class="space-y-1">
                                            <?php foreach ($app->wards as $w): ?>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="font-semibold text-slate-800"><?= e($w->getFullName()) ?></span>
                                                    <span class="text-[10px] text-slate-400">(<?= e($w->classGrade) ?>)</span>
                                                    <?php if ($w->isPaid()): ?>
                                                        <span class="size-1.5 rounded-full bg-emerald-500" title="Fee Paid"></span>
                                                    <?php else: ?>
                                                        <span class="size-1.5 rounded-full bg-amber-500" title="Fee Unpaid"></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-5">
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold border <?= $badge['class'] ?>">
                                        <?= e($badge['label']) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-5 text-right space-x-2">
                                    <a href="/applicant/progress" 
                                       class="inline-flex items-center gap-1 text-xs font-bold text-[#7B3046] hover:underline">
                                        <span>View Progress</span>
                                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Help & Next Steps Guidance -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-xs">
            <div class="size-9 rounded-xl bg-rose-50 text-[#7B3046] flex items-center justify-center font-bold text-sm mb-3">
                1
            </div>
            <h3 class="font-bold text-slate-900 text-sm">Add Ward &amp; Pay Fee</h3>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                Add each child's prospective grade and complete the application fee securely via Paystack.
            </p>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-xs">
            <div class="size-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm mb-3">
                2
            </div>
            <h3 class="font-bold text-slate-900 text-sm">Upload Documents</h3>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                Attach birth certificate, passport photo, and previous school report cards if applicable.
            </p>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-xs">
            <div class="size-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm mb-3">
                3
            </div>
            <h3 class="font-bold text-slate-900 text-sm">Admission Decision</h3>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                Upon approval, an official Student Registration Number will be generated for school enrollment.
            </p>
        </div>
    </div>
</div>
