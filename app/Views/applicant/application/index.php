<?php
/**
 * Applicant Application Workspace & Ward Management
 * 
 * @var \App\Models\AdmissionApplication|null $application
 * @var \App\Models\AdmissionSession|null $session
 * @var \App\Models\AdmissionWard[] $wards
 * @var \App\Models\AcademicLevel[] $levels
 * @var \App\Models\User $user
 */
$this->layout('layouts/applicant', ['title' => 'Application Workspace']);

$isDraft = $application && $application->status === \App\Models\AdmissionApplication::STATUS_DRAFT;
$canSubmit = false;
$submitErrors = [];

if ($isDraft && !empty($wards)) {
    $allPaid = true;
    $allDocs = true;
    foreach ($wards as $w) {
        if (!$w->isPaid()) {
            $allPaid = false;
            $submitErrors[] = "Fee payment pending for {$w->getFullName()}";
        }
        if (empty($w->birthCertificateFileId)) {
            $allDocs = false;
            $submitErrors[] = "Birth certificate missing for {$w->getFullName()}";
        }
        if (empty($w->passportPhotoFileId)) {
            $allDocs = false;
            $submitErrors[] = "Passport photograph missing for {$w->getFullName()}";
        }
    }
    $canSubmit = $allPaid && $allDocs;
}
?>

<div class="space-y-6">

    <!-- Top Application Docket Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2.5 mb-1.5">
                    <span class="font-mono text-xs font-bold text-[#7B3046] bg-rose-50 border border-rose-100 px-2.5 py-0.5 rounded-md">
                        <?= e($application->applicationNumber ?? 'APP-DRAFT') ?>
                    </span>
                    <?php if ($application): ?>
                        <?php $badge = $application->getStatusBadge(); ?>
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?= $badge['class'] ?>">
                            <?= e($badge['label']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <h1 class="font-serif text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                    <?= e($session->title ?? 'Admissions Application') ?>
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Guardian: <strong class="text-slate-700"><?= e($user->name) ?></strong> (<?= e($user->email) ?>) • Application Fee: <strong class="text-[#7B3046]"><?= e($session ? $session->getFormattedFee() : '₦10,000.00') ?></strong> per prospective ward.
                </p>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <a href="/applicant/progress" 
                   class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                    <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400" style="width:14px;height:14px;"></i>
                    <span>Milestone Progress</span>
                </a>
                <?php if ($isDraft): ?>
                    <a href="/applicant/wards/create" 
                       class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-[#7B3046] to-[#9B3B58] px-4 py-2 text-xs font-semibold text-white shadow-xs hover:shadow-md transition-all active:scale-[0.99]">
                        <i data-lucide="plus" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                        <span>Add Prospective Ward</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$isDraft && $application): ?>
            <div class="mt-5 rounded-xl bg-blue-50 border border-blue-200 p-3.5 text-xs text-blue-900 flex items-center gap-2.5">
                <i data-lucide="info" class="w-4 h-4 text-blue-600 shrink-0" style="width:16px;height:16px;"></i>
                <span>
                    This application docket was submitted on <strong><?= date('M j, Y \a\t h:i A', strtotime($application->submittedAt ?? $application->updatedAt)) ?></strong> and is currently under administrative assessment. Wards and files are locked against edits.
                </span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Prospective Wards Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="font-serif text-lg font-bold text-slate-900">
                Prospective Wards (<?= count($wards) ?>)
            </h2>
            <span class="text-xs text-slate-500">
                Each ward must have application fee verified and mandatory documents attached.
            </span>
        </div>

        <?php if (empty($wards)): ?>
            <!-- Empty State: No Wards Added -->
            <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-12 text-center">
                <div class="inline-flex size-14 rounded-2xl bg-rose-50 text-[#7B3046] items-center justify-center mb-3">
                    <i data-lucide="users" class="w-7 h-7" style="width:28px;height:28px;"></i>
                </div>
                <h3 class="font-bold text-slate-800 text-sm">No Prospective Wards Added</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto leading-relaxed">
                    Begin by registering the prospective child you are applying for. You can register multiple siblings under this same application docket.
                </p>
                <?php if ($isDraft): ?>
                    <div class="mt-5">
                        <a href="/applicant/wards/create" 
                           class="group relative inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#7B3046] to-[#9B3B58] h-11 px-5 text-sm font-semibold text-white shadow-md hover:shadow-lg transition-all active:scale-[0.99]">
                            <span>Add First Prospective Ward</span>
                            <span class="inline-flex items-center justify-center max-w-0 opacity-0 -translate-x-2 group-hover:max-w-[20px] group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-200 ease-out overflow-hidden">
                                <i data-lucide="arrow-right" class="w-4 h-4 shrink-0" style="width:16px;height:16px;"></i>
                            </span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Wards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <?php foreach ($wards as $idx => $ward): ?>
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between hover:border-rose-200 transition-colors">
                        <div>
                            <!-- Card Header -->
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="size-11 rounded-xl bg-rose-50 text-[#7B3046] flex items-center justify-center font-bold text-sm shrink-0">
                                        <?= strtoupper(substr($ward->firstName, 0, 1) . substr($ward->lastName, 0, 1)) ?>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-900 text-sm">
                                            <?= e($ward->getFullName()) ?>
                                        </h3>
                                        <p class="text-xs text-slate-500">
                                            Applying for: <strong class="text-slate-800"><?= e($ward->classGrade) ?></strong>
                                            <?php if ($ward->academicLevelName): ?>
                                                <span class="text-slate-400">• <?= e($ward->academicLevelName) ?></span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Payment Status Pill -->
                                <?php if ($ward->isPaid()): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                        <i data-lucide="check" class="w-3 h-3 text-emerald-600" style="width:12px;height:12px;"></i>
                                        <span>Fee Paid</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 shrink-0">
                                        <i data-lucide="clock" class="w-3 h-3 text-amber-600" style="width:12px;height:12px;"></i>
                                        <span>Fee Unpaid</span>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Ward Profile Metadata Grid -->
                            <div class="grid grid-cols-2 gap-2 text-[11px] bg-slate-50/70 rounded-xl p-3 border border-slate-100 mb-4">
                                <div>
                                    <span class="text-slate-400 block font-medium">Date of Birth</span>
                                    <span class="font-semibold text-slate-800"><?= date('M j, Y', strtotime($ward->dateOfBirth)) ?></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block font-medium">Gender</span>
                                    <span class="font-semibold text-slate-800 capitalize"><?= e($ward->gender) ?></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block font-medium">Curriculum</span>
                                    <span class="font-semibold text-slate-800"><?= e($ward->curriculumChoice ?? 'Standard') ?></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block font-medium">School Bus</span>
                                    <span class="font-semibold text-slate-800"><?= $ward->useSchoolBus ? 'Required' : 'Not required' ?></span>
                                </div>
                            </div>

                            <!-- Document Upload Status Checklist -->
                            <div class="space-y-1.5 text-xs mb-4">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">
                                    Required Documents
                                </span>

                                <div class="flex items-center justify-between py-1 px-2 rounded-lg <?= $ward->birthCertificateFileId ? 'bg-emerald-50/60 text-emerald-800' : 'bg-slate-50 text-slate-500' ?>">
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="<?= $ward->birthCertificateFileId ? 'check-circle-2' : 'circle' ?>" class="w-3.5 h-3.5 <?= $ward->birthCertificateFileId ? 'text-emerald-600' : 'text-slate-400' ?>" style="width:14px;height:14px;"></i>
                                        <span>Birth Certificate</span>
                                    </span>
                                    <?php if ($ward->birthCertificateFileId): ?>
                                        <a href="/files/<?= $ward->birthCertificateFileId ?>/download" target="_blank" class="text-[10px] font-bold text-emerald-700 hover:underline">View</a>
                                    <?php else: ?>
                                        <span class="text-[10px] text-amber-600 font-semibold">Missing</span>
                                    <?php endif; ?>
                                </div>

                                <div class="flex items-center justify-between py-1 px-2 rounded-lg <?= $ward->passportPhotoFileId ? 'bg-emerald-50/60 text-emerald-800' : 'bg-slate-50 text-slate-500' ?>">
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="<?= $ward->passportPhotoFileId ? 'check-circle-2' : 'circle' ?>" class="w-3.5 h-3.5 <?= $ward->passportPhotoFileId ? 'text-emerald-600' : 'text-slate-400' ?>" style="width:14px;height:14px;"></i>
                                        <span>Passport Photo</span>
                                    </span>
                                    <?php if ($ward->passportPhotoFileId): ?>
                                        <a href="/files/<?= $ward->passportPhotoFileId ?>/download" target="_blank" class="text-[10px] font-bold text-emerald-700 hover:underline">View</a>
                                    <?php else: ?>
                                        <span class="text-[10px] text-amber-600 font-semibold">Missing</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Ward Actions Footer -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                            <?php if ($isDraft): ?>
                                <div class="flex items-center gap-2">
                                    <a href="/applicant/wards/<?= $ward->id ?>/edit" 
                                       class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">
                                        Edit
                                    </a>
                                    <?php if (!$ward->isPaid()): ?>
                                        <span class="text-slate-300">•</span>
                                        <form action="/applicant/wards/<?= $ward->id ?>/delete" method="POST" onsubmit="return confirm('Are you sure you want to remove this ward?');" class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700 transition-colors cursor-pointer">
                                                Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-xs text-slate-400">Locked</span>
                            <?php endif; ?>

                            <!-- Main Ward Action Button -->
                            <?php if (!$ward->isPaid()): ?>
                                <a href="/applicant/payment/<?= $ward->id ?>" 
                                   class="group relative inline-flex items-center gap-1.5 rounded-xl bg-[#7B3046] px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-[#5F2234] transition-all cursor-pointer">
                                    <span>Pay Fee (<?= e($session ? $session->getFormattedFee() : '₦10,000') ?>)</span>
                                    <i data-lucide="credit-card" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                                </a>
                            <?php else: ?>
                                <a href="/applicant/wards/<?= $ward->id ?>/documents" 
                                   class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition-colors">
                                    <i data-lucide="upload-cloud" class="w-3.5 h-3.5 text-slate-500" style="width:14px;height:14px;"></i>
                                    <span>Upload Documents</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Final Submission Action Card -->
    <?php if ($isDraft && !empty($wards)): ?>
        <div class="rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-slate-950 p-6 text-white shadow-lg">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="font-serif text-lg font-bold">Ready to Submit Application?</h3>
                    <p class="text-xs text-slate-300 mt-1 max-w-xl leading-relaxed">
                        Submitting finalizes your application docket and transmits prospective ward information and uploaded documents to the Claret Admissions Committee for screening. Once submitted, records cannot be edited.
                    </p>
                    <?php if (!$canSubmit): ?>
                        <div class="mt-2.5 text-xs text-rose-300 font-medium flex items-center gap-1.5">
                            <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0" style="width:14px;height:14px;"></i>
                            <span><?= e($submitErrors[0] ?? 'Please complete fee payments and required document uploads before submitting.') ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <form action="/applicant/applications/<?= $application->id ?>/submit" method="POST" class="shrink-0" onsubmit="return confirm('Are you sure you are ready to finalize and submit this application? You will not be able to edit details afterward.');">
                    <?= csrf_field() ?>
                    <button type="submit" 
                            <?= !$canSubmit ? 'disabled' : '' ?>
                            class="group relative inline-flex items-center justify-center gap-2 rounded-xl <?= $canSubmit ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md hover:shadow-lg cursor-pointer active:scale-[0.99]' : 'bg-slate-700 text-slate-400 cursor-not-allowed opacity-60' ?> h-11 px-6 text-sm font-semibold transition-all duration-200">
                        <span>Finalize &amp; Submit Docket</span>
                        <span class="inline-flex items-center justify-center max-w-0 opacity-0 -translate-x-2 group-hover:max-w-[20px] group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-200 ease-out overflow-hidden">
                            <i data-lucide="arrow-right" class="w-4 h-4 shrink-0" style="width:16px;height:16px;"></i>
                        </span>
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>
