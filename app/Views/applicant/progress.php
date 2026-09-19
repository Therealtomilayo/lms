<?php
/**
 * Applicant Application Milestone Tracker
 * 
 * @var \App\Models\AdmissionApplication|null $application
 * @var \App\Models\AdmissionSession|null $session
 * @var \App\Models\User $user
 */
$this->layout('layouts/applicant', ['title' => 'Application Progress']);

$status = $application->status ?? 'none';
$wards = $application->wards ?? [];

$isDraft = $status === \App\Models\AdmissionApplication::STATUS_DRAFT;
$isSubmitted = in_array($status, [
    \App\Models\AdmissionApplication::STATUS_SUBMITTED,
    \App\Models\AdmissionApplication::STATUS_UNDER_REVIEW,
    \App\Models\AdmissionApplication::STATUS_APPROVED,
    \App\Models\AdmissionApplication::STATUS_REJECTED,
], true);
$isUnderReview = in_array($status, [
    \App\Models\AdmissionApplication::STATUS_UNDER_REVIEW,
    \App\Models\AdmissionApplication::STATUS_APPROVED,
    \App\Models\AdmissionApplication::STATUS_REJECTED,
], true);
$isApproved = $status === \App\Models\AdmissionApplication::STATUS_APPROVED;
$isRejected = $status === \App\Models\AdmissionApplication::STATUS_REJECTED;

$badge = $application ? $application->getStatusBadge() : ['label' => 'Draft', 'class' => 'bg-slate-100 text-slate-700'];
?>

<div class="max-w-3xl mx-auto space-y-6">

    <!-- Top Breadcrumb -->
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="/applicant/dashboard" class="hover:text-[#7B3046] transition-colors flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
            <span>Back to Dashboard</span>
        </a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Application Progress</span>
    </div>

    <!-- Status Overview Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="font-mono text-xs font-bold text-[#7B3046] bg-rose-50 border border-rose-100 px-2.5 py-0.5 rounded-md">
                        <?= e($application->applicationNumber ?? 'APP-DRAFT') ?>
                    </span>
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?= $badge['class'] ?>">
                        <?= e($badge['label']) ?>
                    </span>
                </div>
                <h1 class="font-serif text-2xl font-bold text-slate-900 tracking-tight">
                    Application Milestone Status
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    <?= e($session->title ?? 'Admissions Portal') ?> • Prospective Guardian: <strong class="text-slate-700"><?= e($user->name) ?></strong>
                </p>
            </div>

            <?php if ($isDraft): ?>
                <a href="/applicant/application" 
                   class="group inline-flex items-center gap-1.5 rounded-xl bg-[#7B3046] px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-[#5F2234] transition-all shrink-0">
                    <span>Continue Application</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                </a>
            <?php endif; ?>
        </div>

        <!-- Dynamic Decision Banners -->
        <?php if ($isApproved): ?>
            <div class="mt-6 rounded-2xl bg-emerald-50 border border-emerald-200 p-5 text-emerald-900">
                <div class="flex items-start gap-3.5">
                    <div class="size-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                        <i data-lucide="award" class="w-5 h-5" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <h3 class="font-serif font-bold text-base text-emerald-900">Congratulations! Application Approved</h3>
                        <p class="text-xs text-emerald-800 mt-1 leading-relaxed">
                            Your ward has been accepted into Claret International School. An official student enrollment record has been provisioned.
                        </p>
                        <?php foreach ($wards as $w): ?>
                            <?php if (!empty($w->convertedStudentId)): ?>
                                <div class="mt-3 p-3 bg-white/80 rounded-xl border border-emerald-200 flex items-center justify-between text-xs">
                                    <span class="font-semibold text-slate-800"><?= e($w->getFullName()) ?></span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-slate-500 font-medium">Admission No:</span>
                                        <span class="font-mono font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded">
                                            <?= e($w->studentAdmissionNumber ?? 'STD-ENROLLED') ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php elseif ($isRejected): ?>
            <div class="mt-6 rounded-2xl bg-red-50 border border-red-200 p-5 text-red-900">
                <div class="flex items-start gap-3.5">
                    <div class="size-10 rounded-xl bg-red-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                        <i data-lucide="x-circle" class="w-5 h-5" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <h3 class="font-serif font-bold text-base text-red-900">Application Not Accepted</h3>
                        <p class="text-xs text-red-800 mt-1 leading-relaxed">
                            After thorough evaluation, our admissions committee is unable to offer admission at this time.
                        </p>
                        <?php if (!empty($application->rejectionReason)): ?>
                            <div class="mt-3 p-3 bg-white/80 rounded-xl border border-red-200 text-xs">
                                <span class="font-bold text-red-900 block mb-1">Committee Comments:</span>
                                <p class="text-slate-700 leading-relaxed"><?= nl2br(e($application->rejectionReason)) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Vertical Milestone Timeline Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
        <h2 class="font-serif text-base font-bold text-slate-900 mb-6">
            Admissions Screening Pipeline
        </h2>

        <div class="relative pl-6 space-y-8 before:absolute before:left-3 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
            
            <!-- Step 1: Account Setup -->
            <div class="relative flex items-start gap-4">
                <div class="absolute -left-6 mt-0.5 size-6 rounded-full bg-emerald-500 text-white flex items-center justify-center ring-4 ring-white shrink-0">
                    <i data-lucide="check" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Guardian Account Registered</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Applicant credentials established and application docket initialized.
                    </p>
                    <span class="text-[10px] text-slate-400 font-mono mt-1 block">Completed</span>
                </div>
            </div>

            <!-- Step 2: Wards & Application Fee -->
            <div class="relative flex items-start gap-4">
                <?php
                $hasPaidWards = !empty($wards);
                foreach ($wards as $w) {
                    if (!$w->isPaid()) { $hasPaidWards = false; break; }
                }
                ?>
                <div class="absolute -left-6 mt-0.5 size-6 rounded-full <?= $hasPaidWards ? 'bg-emerald-500 text-white' : 'bg-amber-500 text-white animate-pulse' ?> flex items-center justify-center ring-4 ring-white shrink-0">
                    <i data-lucide="<?= $hasPaidWards ? 'check' : 'clock' ?>" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Ward Registration &amp; Fee Payment</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Per-ward screening fee (<?= e($session ? $session->getFormattedFee() : '₦10,000.00') ?>) verified via Paystack.
                    </p>
                    <span class="text-[10px] <?= $hasPaidWards ? 'text-emerald-600 font-semibold' : 'text-amber-600 font-semibold' ?> mt-1 block">
                        <?= $hasPaidWards ? 'All Wards Paid' : 'Fee Payment Required' ?>
                    </span>
                </div>
            </div>

            <!-- Step 3: Document Verification -->
            <div class="relative flex items-start gap-4">
                <?php
                $allDocsUploaded = !empty($wards);
                foreach ($wards as $w) {
                    if (empty($w->birthCertificateFileId) || empty($w->passportPhotoFileId)) {
                        $allDocsUploaded = false;
                        break;
                    }
                }
                ?>
                <div class="absolute -left-6 mt-0.5 size-6 rounded-full <?= $allDocsUploaded ? 'bg-emerald-500 text-white' : ($hasPaidWards ? 'bg-amber-500 text-white' : 'bg-slate-200 text-slate-400') ?> flex items-center justify-center ring-4 ring-white shrink-0">
                    <i data-lucide="<?= $allDocsUploaded ? 'check' : 'file-text' ?>" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Mandatory Document Uploads</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Birth certificate and passport photographs attached to prospective wards.
                    </p>
                    <span class="text-[10px] <?= $allDocsUploaded ? 'text-emerald-600 font-semibold' : 'text-slate-400' ?> mt-1 block">
                        <?= $allDocsUploaded ? 'Completed' : 'Pending Document Uploads' ?>
                    </span>
                </div>
            </div>

            <!-- Step 4: Final Submission -->
            <div class="relative flex items-start gap-4">
                <div class="absolute -left-6 mt-0.5 size-6 rounded-full <?= $isSubmitted ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-400' ?> flex items-center justify-center ring-4 ring-white shrink-0">
                    <i data-lucide="<?= $isSubmitted ? 'check' : 'send' ?>" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Final Docket Submission</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Application finalized and transmitted to the Claret Admissions Committee.
                    </p>
                    <span class="text-[10px] <?= $isSubmitted ? 'text-emerald-600 font-semibold' : 'text-slate-400' ?> mt-1 block">
                        <?= $isSubmitted ? 'Submitted on ' . date('M j, Y', strtotime($application->submittedAt ?? 'now')) : 'Pending Final Submission' ?>
                    </span>
                </div>
            </div>

            <!-- Step 5: Review & Evaluation -->
            <div class="relative flex items-start gap-4">
                <div class="absolute -left-6 mt-0.5 size-6 rounded-full <?= ($isApproved || $isRejected) ? 'bg-emerald-500 text-white' : ($isUnderReview ? 'bg-blue-600 text-white animate-pulse' : 'bg-slate-200 text-slate-400') ?> flex items-center justify-center ring-4 ring-white shrink-0">
                    <i data-lucide="<?= ($isApproved || $isRejected) ? 'check' : 'search' ?>" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Admissions Committee Screening</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Academic records, grade suitability, and institutional entrance assessment.
                    </p>
                    <span class="text-[10px] <?= $isUnderReview ? 'text-blue-600 font-semibold' : 'text-slate-400' ?> mt-1 block">
                        <?= ($isApproved || $isRejected) ? 'Review Completed' : ($isUnderReview ? 'Under Active Assessment' : 'Awaiting Queue Ingestion') ?>
                    </span>
                </div>
            </div>

            <!-- Step 6: Decision -->
            <div class="relative flex items-start gap-4">
                <div class="absolute -left-6 mt-0.5 size-6 rounded-full <?= $isApproved ? 'bg-emerald-500 text-white' : ($isRejected ? 'bg-red-500 text-white' : 'bg-slate-200 text-slate-400') ?> flex items-center justify-center ring-4 ring-white shrink-0">
                    <i data-lucide="<?= $isApproved ? 'award' : ($isRejected ? 'x' : 'flag') ?>" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Admission Decision &amp; Student ID</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Formal notice of acceptance with generated student admission number.
                    </p>
                    <span class="text-[10px] <?= $isApproved ? 'text-emerald-600 font-bold' : ($isRejected ? 'text-red-600 font-bold' : 'text-slate-400') ?> mt-1 block">
                        <?= $isApproved ? 'Enrolled as Student' : ($isRejected ? 'Application Not Accepted' : 'Pending Review Outcome') ?>
                    </span>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>
