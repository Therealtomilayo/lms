<?php
/**
 * Applicant Application Milestone Tracker
 * 
 * @var \App\Models\AdmissionApplication|null $application
 * @var array<\App\Models\AdmissionApplication> $applications
 * @var array<\App\Models\AdmissionWard> $wards
 * @var array $history
 * @var bool $isParent
 * @var \App\Models\AdmissionSession|null $session
 * @var \App\Models\User $user
 */
$this->layout('layouts/applicant', ['title' => 'Application Progress']);

$applications = $applications ?? ($application ? [$application] : []);
$status = $application->status ?? 'none';
$wards = $wards ?? ($application->wards ?? []);

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

$approvedWards = [];
$rejectedWards = [];
$pendingWards = [];
foreach ($wards as $w) {
    if ($w->isApproved() || !empty($w->convertedStudentId)) {
        $approvedWards[] = $w;
    } elseif ($w->isRejected()) {
        $rejectedWards[] = $w;
    } else {
        $pendingWards[] = $w;
    }
}

$badge = $application ? $application->getStatusBadge() : ['label' => 'Draft', 'class' => 'bg-slate-100 text-slate-700'];
?>

<div class="max-w-3xl mx-auto space-y-6">

    <!-- Top Breadcrumb & Docket Switcher -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-500">
        <div class="flex items-center gap-2">
            <a href="/applicant/dashboard" class="hover:text-[#7B3046] transition-colors flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                <span>Back to Dashboard</span>
            </a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Application Progress</span>
        </div>

        <?php if (count($applications) > 1): ?>
            <div class="flex items-center gap-2">
                <label for="docket-selector" class="text-[11px] font-semibold text-slate-500">Docket:</label>
                <select id="docket-selector" 
                        onchange="window.location.href='/applicant/progress?app=' + this.value" 
                        class="text-xs font-mono font-bold bg-white border border-slate-200 rounded-lg px-2.5 py-1 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#7B3046]/20">
                    <?php foreach ($applications as $a): ?>
                        <option value="<?= $a->id ?>" <?= $application && $application->id === $a->id ? 'selected' : '' ?>>
                            <?= e($a->applicationNumber) ?> (<?= strtoupper($a->status) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
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

            <div class="flex items-center gap-2 shrink-0">
                <?php if ($isDraft): ?>
                    <a href="/applicant/application" 
                       class="group inline-flex items-center gap-1.5 rounded-xl bg-[#7B3046] px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-[#5F2234] transition-all">
                        <span>Continue Application</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                    </a>
                <?php endif; ?>

                <?php if (!empty($approvedWards) || $isParent): ?>
                    <a href="/parent/dashboard" 
                       class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white px-4 py-2 text-xs font-semibold shadow-xs transition-all">
                        <i data-lucide="layout-dashboard" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                        <span>Parent Portal</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- 1. Approved Prospective Ward(s) Celebratory Banner & Credentials -->
        <?php if (!empty($approvedWards)): ?>
            <div class="mt-6 rounded-2xl bg-emerald-50 border border-emerald-200 p-6 text-emerald-950 space-y-5">
                <div class="flex items-start gap-3.5">
                    <div class="size-11 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                        <i data-lucide="award" class="w-6 h-6" style="width:24px;height:24px;"></i>
                    </div>
                    <div>
                        <h3 class="font-serif font-bold text-lg text-emerald-900">Congratulations! Admission Approved</h3>
                        <p class="text-xs text-emerald-800 mt-1 leading-relaxed">
                            We are delighted to confirm that your ward has been accepted into Claret International School! An official student enrollment record has been provisioned.
                        </p>
                    </div>
                </div>

                <!-- Approved Ward Credentials Cards -->
                <div class="space-y-3">
                    <?php foreach ($approvedWards as $w): 
                        $admNo = $w->studentAdmissionNumber ?? 'STD-ENROLLED';
                        $defaultPwd = 'Claret@' . date('Y') . '!';
                        $studentEmail = strtolower($admNo) . '@student.claret.edu.ng';
                    ?>
                        <div class="p-4 bg-white rounded-xl border border-emerald-200/90 shadow-2xs space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                                <div>
                                    <span class="font-bold text-slate-900 text-sm block"><?= e($w->getFullName()) ?></span>
                                    <span class="text-xs text-slate-500">Admitted Grade: <strong class="text-slate-700"><?= e($w->classGrade) ?></strong></span>
                                </div>
                                <div class="flex items-center gap-1.5 self-start sm:self-auto">
                                    <span class="text-xs font-semibold text-slate-500">Admission No:</span>
                                    <span class="font-mono font-black text-xs text-emerald-800 bg-emerald-100 px-2.5 py-1 rounded-lg border border-emerald-200">
                                        <?= e($admNo) ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Student Access Credentials -->
                            <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100 space-y-2 text-xs">
                                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                                    <i data-lucide="key" class="w-3.5 h-3.5 text-brand-600" style="width:14px;height:14px;"></i>
                                    <span>Student Portal Access Credentials</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                    <div>
                                        <span class="text-slate-400 block text-[11px]">Login Username / Institutional Email:</span>
                                        <span class="font-mono font-bold text-slate-800 select-all"><?= e($studentEmail) ?></span>
                                        <span class="text-[10px] text-slate-400 block mt-0.5">or Admission Number: <strong><?= e($admNo) ?></strong></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[11px]">Default Temporary Password:</span>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <code class="font-mono font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded border border-emerald-200 select-all"><?= e($defaultPwd) ?></code>
                                        </div>
                                        <span class="text-[10px] text-slate-400 block mt-0.5">Student will be required to change password on first login.</span>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($w->decisionNote)): ?>
                                <div class="p-2.5 rounded-lg bg-emerald-50/60 border border-emerald-100 text-xs text-emerald-900">
                                    <span class="font-bold">Admissions Committee Remark:</span> <?= e($w->decisionNote) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Student Login Guide Note -->
                <div class="text-xs text-emerald-800/90 bg-emerald-100/50 p-3 rounded-xl border border-emerald-200/60 flex items-start gap-2.5">
                    <i data-lucide="info" class="w-4 h-4 text-emerald-700 shrink-0 mt-0.5" style="width:16px;height:16px;"></i>
                    <div>
                        <strong>Student Portal Guide:</strong> Students can log in at <a href="/login" target="_blank" class="underline font-bold text-emerald-900">/login</a> using either their Admission Number or Student Email and the default password above.
                    </div>
                </div>

                <!-- Parent Portal Activation Card & Button -->
                <div class="p-4 bg-gradient-to-r from-emerald-800 to-teal-900 text-white rounded-xl shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 font-bold text-sm">
                            <i data-lucide="shield-check" class="w-4 h-4 text-emerald-300" style="width:16px;height:16px;"></i>
                            <span>Parent Portal Access Activated</span>
                        </div>
                        <p class="text-xs text-emerald-100 leading-relaxed max-w-lg">
                            Your guardian account is now elevated with full access to the Claret Parent Portal. You can monitor academic performance, term results, attendance, and school fee invoices.
                        </p>
                    </div>
                    <a href="/parent/dashboard" 
                       class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-white text-emerald-950 font-bold text-xs hover:bg-emerald-50 transition shadow-xs shrink-0 cursor-pointer">
                        <span>Go to Parent Portal</span>
                        <i data-lucide="arrow-right" class="w-4 h-4 text-emerald-700" style="width:16px;height:16px;"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- 2. Rejected / Not Admitted Ward(s) Notice Banner -->
        <?php if (!empty($rejectedWards)): ?>
            <div class="mt-6 rounded-2xl bg-rose-50 border border-rose-200 p-6 text-rose-950 space-y-4">
                <div class="flex items-start gap-3.5">
                    <div class="size-11 rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                        <i data-lucide="x-circle" class="w-6 h-6" style="width:24px;height:24px;"></i>
                    </div>
                    <div>
                        <h3 class="font-serif font-bold text-lg text-rose-900">
                            <?= count($rejectedWards) === 1 ? 'Admissions Update: Ward Not Admitted' : 'Admissions Update: Prospective Wards Not Admitted' ?>
                        </h3>
                        <p class="text-xs text-rose-800 mt-1 leading-relaxed">
                            Following evaluation by the admissions committee, we regret to inform you that the prospective student(s) below could not be offered admission for the selected academic stream at this time.
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    <?php foreach ($rejectedWards as $w): ?>
                        <div class="p-4 bg-white rounded-xl border border-rose-200 shadow-2xs space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="font-bold text-slate-900 text-sm block"><?= e($w->getFullName()) ?></span>
                                    <span class="text-xs text-slate-500">Grade Applied: <strong class="text-slate-700"><?= e($w->classGrade) ?></strong></span>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                    Not Admitted
                                </span>
                            </div>

                            <!-- Committee Reason / Remark -->
                            <div class="p-3 rounded-lg bg-rose-50/70 border border-rose-100 text-xs text-slate-700 space-y-1">
                                <span class="font-bold text-rose-900 block text-[11px] uppercase tracking-wider">Committee Feedback &amp; Reason:</span>
                                <p class="leading-relaxed">
                                    <?= e($w->decisionNote ?: $application->rejectionReason ?: 'Admission criteria for the requested grade and arm could not be satisfied at this time.') ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="p-3.5 rounded-xl bg-white/70 border border-rose-200 text-xs text-slate-600 flex items-start gap-2.5">
                    <i data-lucide="help-circle" class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" style="width:16px;height:16px;"></i>
                    <div>
                        <strong>Next Steps &amp; Inquiries:</strong> If you have questions regarding this assessment or would like advice on academic preparation and future admission rounds, please reach out to the Claret Admissions Office at <a href="mailto:admissions@claret.edu.ng" class="text-[#7B3046] font-semibold underline">admissions@claret.edu.ng</a>.
                    </div>
                </div>
            </div>
        <?php elseif ($isRejected && empty($approvedWards)): ?>
            <!-- Fallback if docket itself is rejected with no wards categorized -->
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

        <!-- 3. Submitted / Under Review Info Banner -->
        <?php if (in_array($status, [\App\Models\AdmissionApplication::STATUS_SUBMITTED, \App\Models\AdmissionApplication::STATUS_UNDER_REVIEW], true) && empty($approvedWards) && empty($rejectedWards)): ?>
            <div class="mt-6 rounded-2xl bg-blue-50 border border-blue-200 p-5 text-blue-950 flex items-start gap-3.5">
                <div class="size-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                    <i data-lucide="clock" class="w-5 h-5" style="width:20px;height:20px;"></i>
                </div>
                <div>
                    <h3 class="font-serif font-bold text-base text-blue-900">Application Under Administrative Assessment</h3>
                    <p class="text-xs text-blue-800 mt-1 leading-relaxed">
                        Your application docket was received on <?= date('M j, Y', strtotime($application->submittedAt ?? 'now')) ?> and is actively being screened by the Claret Admissions Committee. Final decisions, credentials, and matriculation notices will appear here once finalized.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Vertical Milestone Timeline Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
        <h2 class="font-serif text-base font-bold text-slate-900 mb-6">
            Admissions Screening Pipeline
        </h2>

        <div class="relative pl-10 space-y-8 before:absolute before:left-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
            
            <!-- Step 1: Account Setup -->
            <div class="relative pl-4">
                <div class="absolute -left-10 mt-0.5 size-8 rounded-full bg-emerald-500 text-white flex items-center justify-center ring-4 ring-white shrink-0 shadow-2xs">
                    <i data-lucide="check" class="w-4 h-4" style="width:16px;height:16px;"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Guardian Account Registered</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Applicant credentials established and application docket initialized.
                    </p>
                    <span class="text-[10px] text-slate-400 font-mono mt-1.5 block">Completed</span>
                </div>
            </div>

            <!-- Step 2: Wards & Application Fee -->
            <div class="relative pl-4">
                <?php
                $hasPaidWards = !empty($wards);
                foreach ($wards as $w) {
                    if (!$w->isPaid()) { $hasPaidWards = false; break; }
                }
                ?>
                <div class="absolute -left-10 mt-0.5 size-8 rounded-full <?= $hasPaidWards ? 'bg-emerald-500 text-white' : 'bg-amber-500 text-white animate-pulse' ?> flex items-center justify-center ring-4 ring-white shrink-0 shadow-2xs">
                    <i data-lucide="<?= $hasPaidWards ? 'check' : 'clock' ?>" class="w-4 h-4" style="width:16px;height:16px;"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Ward Registration &amp; Fee Payment</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Per-ward screening fee (<?= e($session ? $session->getFormattedFee() : '₦10,000.00') ?>) verified via Paystack.
                    </p>
                    <span class="text-[10px] <?= $hasPaidWards ? 'text-emerald-600 font-semibold' : 'text-amber-600 font-semibold' ?> mt-1.5 block">
                        <?= $hasPaidWards ? 'All Wards Paid' : 'Fee Payment Required' ?>
                    </span>
                </div>
            </div>

            <!-- Step 3: Document Verification -->
            <div class="relative pl-4">
                <?php
                $allDocsUploaded = !empty($wards);
                foreach ($wards as $w) {
                    if (empty($w->birthCertificateFileId) 
                        || empty($w->passportPhotoFileId)
                        || empty($w->previousReportFileId)
                        || empty($w->parentPassportFileId)
                        || empty($w->authorizedPickerPassportFileId)
                        || empty($w->immunizationRecordFileId)) {
                        $allDocsUploaded = false;
                        break;
                    }
                }
                ?>
                <div class="absolute -left-10 mt-0.5 size-8 rounded-full <?= $allDocsUploaded ? 'bg-emerald-500 text-white' : ($hasPaidWards ? 'bg-amber-500 text-white' : 'bg-slate-200 text-slate-400') ?> flex items-center justify-center ring-4 ring-white shrink-0 shadow-2xs">
                    <i data-lucide="<?= $allDocsUploaded ? 'check' : 'file-text' ?>" class="w-4 h-4" style="width:16px;height:16px;"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Mandatory Document Uploads</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Birth certificate, report cards, immunizations, and guardian/picker passport photographs.
                    </p>
                    <span class="text-[10px] <?= $allDocsUploaded ? 'text-emerald-600 font-semibold' : 'text-slate-400' ?> mt-1.5 block">
                        <?= $allDocsUploaded ? 'Completed (All 6 Documents Uploaded)' : 'Pending Document Uploads' ?>
                    </span>
                </div>
            </div>

            <!-- Step 4: Final Submission -->
            <div class="relative pl-4">
                <div class="absolute -left-10 mt-0.5 size-8 rounded-full <?= $isSubmitted ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-400' ?> flex items-center justify-center ring-4 ring-white shrink-0 shadow-2xs">
                    <i data-lucide="<?= $isSubmitted ? 'check' : 'send' ?>" class="w-4 h-4" style="width:16px;height:16px;"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Final Docket Submission</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Application finalized and transmitted to the Claret Admissions Committee.
                    </p>
                    <span class="text-[10px] <?= $isSubmitted ? 'text-emerald-600 font-semibold' : 'text-slate-400' ?> mt-1.5 block">
                        <?= $isSubmitted ? 'Submitted on ' . date('M j, Y', strtotime($application->submittedAt ?? 'now')) : 'Pending Final Submission' ?>
                    </span>
                </div>
            </div>

            <!-- Step 5: Review & Evaluation -->
            <div class="relative pl-4">
                <div class="absolute -left-10 mt-0.5 size-8 rounded-full <?= ($isApproved || $isRejected) ? 'bg-emerald-500 text-white' : ($isUnderReview ? 'bg-blue-600 text-white animate-pulse' : 'bg-slate-200 text-slate-400') ?> flex items-center justify-center ring-4 ring-white shrink-0 shadow-2xs">
                    <i data-lucide="<?= ($isApproved || $isRejected) ? 'check' : 'search' ?>" class="w-4 h-4" style="width:16px;height:16px;"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Admissions Committee Screening</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Academic records, grade suitability, and institutional entrance assessment.
                    </p>
                    <span class="text-[10px] <?= $isUnderReview ? 'text-blue-600 font-semibold' : 'text-slate-400' ?> mt-1.5 block">
                        <?= ($isApproved || $isRejected) ? 'Review Completed' : ($isUnderReview ? 'Under Active Assessment' : 'Awaiting Queue Ingestion') ?>
                    </span>
                </div>
            </div>

            <!-- Step 6: Decision -->
            <div class="relative pl-4">
                <?php
                $step6Bg = !empty($approvedWards) ? 'bg-emerald-500 text-white' : (!empty($rejectedWards) ? 'bg-rose-500 text-white' : 'bg-slate-200 text-slate-400');
                $step6Icon = !empty($approvedWards) ? 'award' : (!empty($rejectedWards) ? 'x' : 'flag');
                ?>
                <div class="absolute -left-10 mt-0.5 size-8 rounded-full <?= $step6Bg ?> flex items-center justify-center ring-4 ring-white shrink-0 shadow-2xs">
                    <i data-lucide="<?= $step6Icon ?>" class="w-4 h-4" style="width:16px;height:16px;"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Admission Decision &amp; Student ID</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Formal notice of acceptance with generated student admission number.
                    </p>
                    <span class="text-[10px] <?= !empty($approvedWards) ? 'text-emerald-600 font-bold' : (!empty($rejectedWards) ? 'text-rose-600 font-bold' : 'text-slate-400') ?> mt-1.5 block">
                        <?php if (!empty($approvedWards) && !empty($rejectedWards)): ?>
                            Decided (<?= count($approvedWards) ?> Admitted, <?= count($rejectedWards) ?> Not Admitted)
                        <?php elseif (!empty($approvedWards)): ?>
                            Enrolled as Student (<?= count($approvedWards) ?> Admitted)
                        <?php elseif (!empty($rejectedWards)): ?>
                            Application Not Accepted
                        <?php else: ?>
                            Pending Review Outcome
                        <?php endif; ?>
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
