<?php
/**
 * Ward Document Uploads Screen
 * 
 * @var \App\Models\AdmissionWard $ward
 * @var \App\Models\AdmissionApplication $application
 * @var \App\Models\User $user
 * @var array $errors
 */
$this->layout('layouts/applicant', ['title' => 'Upload Documents — ' . $ward->getFullName()]);

$hasBirthCert = !empty($ward->birthCertificateFileId);
$hasPassport = !empty($ward->passportPhotoFileId);
$hasPrevReport = !empty($ward->previousReportFileId);

$allRequiredUploaded = $hasBirthCert && $hasPassport;
?>

<div class="max-w-3xl mx-auto space-y-6">

    <!-- Top Breadcrumb -->
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="/applicant/application" class="hover:text-[#7B3046] transition-colors flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
            <span>Back to Application Docket</span>
        </a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Document Upload</span>
    </div>

    <!-- Ward Summary Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="size-12 rounded-2xl bg-rose-50 text-[#7B3046] flex items-center justify-center font-bold text-base shrink-0">
                <?= strtoupper(substr($ward->firstName, 0, 1) . substr($ward->lastName, 0, 1)) ?>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="font-serif text-lg font-bold text-slate-900">
                        <?= e($ward->getFullName()) ?>
                    </h1>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i data-lucide="check" class="w-3 h-3" style="width:12px;height:12px;"></i>
                        <span>Fee Paid</span>
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Grade: <strong class="text-slate-700"><?= e($ward->classGrade) ?></strong> • Docket: <strong class="font-mono text-slate-700"><?= e($application->applicationNumber) ?></strong>
                </p>
            </div>
        </div>

        <?php if ($allRequiredUploaded): ?>
            <a href="/applicant/application" 
               class="group inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-emerald-700 transition-all">
                <span>All Documents Ready</span>
                <i data-lucide="check-circle-2" class="w-4 h-4" style="width:16px;height:16px;"></i>
            </a>
        <?php endif; ?>
    </div>

    <!-- Upload Cards Grid -->
    <div class="space-y-4">

        <!-- 1. Birth Certificate Card (Required) -->
        <div class="bg-white rounded-2xl border <?= $hasBirthCert ? 'border-emerald-200 bg-emerald-50/20' : 'border-slate-200/80' ?> p-5 sm:p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
                <div class="flex items-start gap-3">
                    <div class="size-10 rounded-xl <?= $hasBirthCert ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' ?> flex items-center justify-center shrink-0">
                        <i data-lucide="<?= $hasBirthCert ? 'check-circle-2' : 'file-text' ?>" class="w-5 h-5" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-slate-900 text-sm">Birth Certificate</h3>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-100">
                                Required
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Official National Population Commission (NPC) certificate or verified hospital birth record.
                        </p>
                    </div>
                </div>

                <?php if ($hasBirthCert): ?>
                    <a href="/files/<?= $ward->birthCertificateFileId ?>/stream" target="_blank" 
                       class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 hover:underline shrink-0">
                        <i data-lucide="eye" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                        <span>Preview Uploaded File</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Upload Form -->
            <form action="/applicant/wards/<?= $ward->id ?>/documents" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row sm:items-center gap-3">
                <?= csrf_field() ?>
                <input type="hidden" name="document_type" value="birth_certificate">
                
                <input type="file" 
                       name="document" 
                       accept=".pdf,.jpg,.jpeg,.png" 
                       required 
                       class="flex-1 text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer">

                <button type="submit" 
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-slate-900 h-9 px-4 text-xs font-semibold text-white hover:bg-slate-800 transition-colors shrink-0 cursor-pointer">
                    <i data-lucide="upload" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                    <span><?= $hasBirthCert ? 'Replace File' : 'Upload File' ?></span>
                </button>
            </form>
            <span class="text-[11px] text-slate-400 mt-2 block">Accepted formats: PDF, JPG, PNG (Max 10MB)</span>
        </div>

        <!-- 2. Passport Photograph Card (Required) -->
        <div class="bg-white rounded-2xl border <?= $hasPassport ? 'border-emerald-200 bg-emerald-50/20' : 'border-slate-200/80' ?> p-5 sm:p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
                <div class="flex items-start gap-3">
                    <div class="size-10 rounded-xl <?= $hasPassport ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' ?> flex items-center justify-center shrink-0">
                        <i data-lucide="<?= $hasPassport ? 'check-circle-2' : 'image' ?>" class="w-5 h-5" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-slate-900 text-sm">Passport Photograph</h3>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-100">
                                Required
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Recent colored passport-sized photograph on a plain white or light background.
                        </p>
                    </div>
                </div>

                <?php if ($hasPassport): ?>
                    <a href="/files/<?= $ward->passportPhotoFileId ?>/stream" target="_blank" 
                       class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 hover:underline shrink-0">
                        <i data-lucide="eye" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                        <span>Preview Uploaded Photo</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Upload Form -->
            <form action="/applicant/wards/<?= $ward->id ?>/documents" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row sm:items-center gap-3">
                <?= csrf_field() ?>
                <input type="hidden" name="document_type" value="passport_photo">
                
                <input type="file" 
                       name="document" 
                       accept=".jpg,.jpeg,.png" 
                       required 
                       class="flex-1 text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer">

                <button type="submit" 
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-slate-900 h-9 px-4 text-xs font-semibold text-white hover:bg-slate-800 transition-colors shrink-0 cursor-pointer">
                    <i data-lucide="upload" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                    <span><?= $hasPassport ? 'Replace Photo' : 'Upload Photo' ?></span>
                </button>
            </form>
            <span class="text-[11px] text-slate-400 mt-2 block">Accepted formats: JPG, PNG (Max 5MB)</span>
        </div>

        <!-- 3. Previous Academic Report / Transcript (Optional) -->
        <div class="bg-white rounded-2xl border <?= $hasPrevReport ? 'border-emerald-200 bg-emerald-50/20' : 'border-slate-200/80' ?> p-5 sm:p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
                <div class="flex items-start gap-3">
                    <div class="size-10 rounded-xl <?= $hasPrevReport ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' ?> flex items-center justify-center shrink-0">
                        <i data-lucide="<?= $hasPrevReport ? 'check-circle-2' : 'award' ?>" class="w-5 h-5" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-slate-900 text-sm">Previous Academic Report / Transcript</h3>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">
                                Optional
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Last term or session report card from the ward's former school (if transferring into Primary or Secondary).
                        </p>
                    </div>
                </div>

                <?php if ($hasPrevReport): ?>
                    <a href="/files/<?= $ward->previousReportFileId ?>/stream" target="_blank" 
                       class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 hover:underline shrink-0">
                        <i data-lucide="eye" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                        <span>Preview Uploaded Report</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Upload Form -->
            <form action="/applicant/wards/<?= $ward->id ?>/documents" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row sm:items-center gap-3">
                <?= csrf_field() ?>
                <input type="hidden" name="document_type" value="previous_report">
                
                <input type="file" 
                       name="document" 
                       accept=".pdf,.jpg,.jpeg,.png" 
                       required 
                       class="flex-1 text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer">

                <button type="submit" 
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-slate-900 h-9 px-4 text-xs font-semibold text-white hover:bg-slate-800 transition-colors shrink-0 cursor-pointer">
                    <i data-lucide="upload" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                    <span><?= $hasPrevReport ? 'Replace Report' : 'Upload Report' ?></span>
                </button>
            </form>
            <span class="text-[11px] text-slate-400 mt-2 block">Accepted formats: PDF, JPG, PNG (Max 10MB)</span>
        </div>

    </div>

    <!-- Return to Workspace Button -->
    <div class="pt-2 flex items-center justify-between">
        <a href="/applicant/application" 
           class="group relative inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#7B3046] to-[#9B3B58] h-11 px-6 text-sm font-semibold text-white shadow-md hover:shadow-lg transition-all active:scale-[0.99]">
            <i data-lucide="arrow-left" class="w-4 h-4" style="width:16px;height:16px;"></i>
            <span>Return to Application Docket</span>
        </a>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>
