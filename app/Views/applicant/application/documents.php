<?php
/**
 * Ward Document Uploads Screen — Seamless Reactive UI
 * 
 * @var \App\Models\AdmissionWard $ward
 * @var \App\Models\AdmissionApplication $application
 * @var \App\Models\User $user
 * @var array $errors
 */
$this->layout('layouts/applicant', ['title' => 'Upload Documents — ' . $ward->getFullName()]);

$hasPassport = !empty($ward->passportPhotoFileId);
$hasBirthCert = !empty($ward->birthCertificateFileId);
$hasPrevReport = !empty($ward->previousReportFileId);
$hasParentPassport = !empty($ward->parentPassportFileId);
$hasPickerPassport = !empty($ward->authorizedPickerPassportFileId);
$hasImmunization = !empty($ward->immunizationRecordFileId);

$uploadedCount = ($hasPassport ? 1 : 0) + ($hasBirthCert ? 1 : 0) + ($hasPrevReport ? 1 : 0)
               + ($hasParentPassport ? 1 : 0) + ($hasPickerPassport ? 1 : 0) + ($hasImmunization ? 1 : 0);
$allRequiredUploaded = ($uploadedCount === 6);

$documents = [
    [
        'type' => 'passport_photo',
        'title' => 'Ward Passport Photograph',
        'description' => 'Clear, colored passport-sized photograph of the prospective student on a plain white or light background.',
        'accepted' => '.jpg,.jpeg,.png',
        'fileId' => $ward->passportPhotoFileId,
        'hasFile' => $hasPassport,
        'icon' => 'image',
    ],
    [
        'type' => 'birth_certificate',
        'title' => 'Birth Certificate',
        'description' => 'Official National Population Commission (NPC) birth certificate or verified hospital birth declaration.',
        'accepted' => '.pdf,.jpg,.jpeg,.png',
        'fileId' => $ward->birthCertificateFileId,
        'hasFile' => $hasBirthCert,
        'icon' => 'file-text',
    ],
    [
        'type' => 'previous_report',
        'title' => 'Previous Academic Report / Transcript',
        'description' => 'Most recent terminal report card or official cumulative transcript from the former school attended.',
        'accepted' => '.pdf,.jpg,.jpeg,.png',
        'fileId' => $ward->previousReportFileId,
        'hasFile' => $hasPrevReport,
        'icon' => 'award',
    ],
    [
        'type' => 'parent_passport',
        'title' => 'Parent / Guardian Passport Photograph',
        'description' => 'Recent colored passport photograph of the primary parent or legal guardian for official school records.',
        'accepted' => '.jpg,.jpeg,.png',
        'fileId' => $ward->parentPassportFileId,
        'hasFile' => $hasParentPassport,
        'icon' => 'user-check',
    ],
    [
        'type' => 'authorized_picker_passport',
        'title' => 'Authorized Picker Passport Photograph',
        'description' => 'Passport photograph of the designated adult authorized to pick up the child from school premises.',
        'accepted' => '.jpg,.jpeg,.png',
        'fileId' => $ward->authorizedPickerPassportFileId,
        'hasFile' => $hasPickerPassport,
        'icon' => 'shield-check',
    ],
    [
        'type' => 'immunization_record',
        'title' => 'Immunization / Vaccination Record',
        'description' => 'Childhood immunization card or signed medical certificate confirming required vaccinations.',
        'accepted' => '.pdf,.jpg,.jpeg,.png',
        'fileId' => $ward->immunizationRecordFileId,
        'hasFile' => $hasImmunization,
        'icon' => 'activity',
    ],
];
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
                <div class="flex items-center gap-2 mt-1">
                    <span id="doc-counter-badge" class="inline-flex items-center gap-1 text-[11px] font-semibold <?= $allRequiredUploaded ? 'text-emerald-700' : 'text-amber-700' ?>">
                        <span id="doc-counter-text"><?= $uploadedCount ?> of 6 Required Documents Uploaded</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Top Proceed Button -->
        <a id="btn-proceed-top" 
           href="/applicant/application" 
           class="group inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-semibold shadow-xs transition-all <?= $allRequiredUploaded ? 'bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer' : 'bg-slate-200 text-slate-400 opacity-60 cursor-not-allowed pointer-events-none' ?>">
            <span id="btn-proceed-top-text"><?= $allRequiredUploaded ? 'All Documents Ready / Proceed' : 'Upload Remaining (' . (6 - $uploadedCount) . ')' ?></span>
            <i data-lucide="check-circle-2" class="w-4 h-4 shrink-0" style="width:16px;height:16px;"></i>
        </a>
    </div>

    <!-- Instruction Notice -->
    <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 text-xs text-slate-600 flex items-start gap-3">
        <i data-lucide="info" class="w-4 h-4 text-[#7B3046] shrink-0 mt-0.5" style="width:16px;height:16px;"></i>
        <div>
            <span class="font-bold text-slate-800">Quick Upload:</span> 
            Choose or drop a file for each document below. Files upload automatically once selected, turning the card and check badge green. All 6 documents are required before application submission.
        </div>
    </div>

    <!-- Hidden CSRF form token for async requests -->
    <form id="global-csrf-form" class="hidden">
        <?= csrf_field() ?>
    </form>

    <!-- Upload Cards Grid -->
    <div class="space-y-4">
        <?php foreach ($documents as $doc): ?>
            <div id="card-<?= $doc['type'] ?>" 
                 data-doc-type="<?= $doc['type'] ?>"
                 data-uploaded="<?= $doc['hasFile'] ? 'true' : 'false' ?>"
                 class="doc-card bg-white rounded-2xl border transition-all duration-300 p-5 sm:p-6 shadow-xs <?= $doc['hasFile'] ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/20' : 'border-slate-200/80 hover:border-slate-300' ?>">
                
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
                    <div class="flex items-start gap-3.5">
                        <div id="icon-box-<?= $doc['type'] ?>" 
                             class="size-11 rounded-xl transition-all duration-300 flex items-center justify-center shrink-0 <?= $doc['hasFile'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' ?>">
                            <i data-lucide="<?= $doc['hasFile'] ? 'check-circle-2' : $doc['icon'] ?>" 
                               class="w-5 h-5" 
                               style="width:20px;height:20px;"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-slate-900 text-sm">
                                    <?= e($doc['title']) ?>
                                </h3>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-100">
                                    Required
                                </span>
                                <span id="status-badge-<?= $doc['type'] ?>" 
                                      class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border <?= $doc['hasFile'] ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-slate-500 bg-slate-100 border-slate-200' ?>">
                                    <?= $doc['hasFile'] ? 'Uploaded' : 'Pending' ?>
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                <?= e($doc['description']) ?>
                            </p>
                        </div>
                    </div>

                    <div id="preview-container-<?= $doc['type'] ?>" class="<?= $doc['hasFile'] ? '' : 'hidden' ?> shrink-0">
                        <a id="preview-link-<?= $doc['type'] ?>" 
                           href="<?= $doc['hasFile'] ? '/files/' . $doc['fileId'] . '/stream' : '#' ?>" 
                           target="_blank" 
                           class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 hover:text-emerald-800 hover:underline">
                            <i data-lucide="eye" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                            <span>Preview File</span>
                        </a>
                    </div>
                </div>

                <!-- Modern Reactive File Drop/Input Area (No separate upload button) -->
                <div class="relative">
                    <label for="input-<?= $doc['type'] ?>" 
                           class="group relative flex items-center justify-between p-3 rounded-xl border border-dashed border-slate-300 hover:border-[#7B3046] bg-slate-50/60 hover:bg-white cursor-pointer transition-all">
                        <div class="flex items-center gap-3">
                            <div class="size-8 rounded-lg bg-white border border-slate-200 text-slate-600 flex items-center justify-center group-hover:text-[#7B3046] group-hover:border-[#7B3046]/40 transition-colors shrink-0">
                                <i data-lucide="upload-cloud" class="w-4 h-4" style="width:16px;height:16px;"></i>
                            </div>
                            <div class="text-left">
                                <span id="file-label-<?= $doc['type'] ?>" class="text-xs font-semibold text-slate-800 block group-hover:text-[#7B3046] transition-colors">
                                    <?= $doc['hasFile'] ? 'Click to replace uploaded file' : 'Click to select or drop file here' ?>
                                </span>
                                <span class="text-[11px] text-slate-400 block">Accepted: <?= strtoupper(str_replace('.', ' ', $doc['accepted'])) ?> (Max 10MB)</span>
                            </div>
                        </div>

                        <!-- Progress / Uploading Spinner -->
                        <div id="spinner-<?= $doc['type'] ?>" class="hidden items-center gap-2 text-xs font-semibold text-[#7B3046] bg-rose-50 px-3 py-1.5 rounded-lg border border-rose-100">
                            <svg class="animate-spin h-3.5 w-3.5 text-[#7B3046]" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Uploading...</span>
                        </div>

                        <span class="text-[11px] font-bold text-slate-600 group-hover:text-slate-900 px-3 py-1.5 rounded-lg bg-white border border-slate-200 shadow-2xs shrink-0">
                            Browse
                        </span>
                    </label>

                    <input id="input-<?= $doc['type'] ?>" 
                           type="file" 
                           accept="<?= $doc['accepted'] ?>"
                           class="hidden"
                           onchange="handleAsyncFileUpload('<?= $doc['type'] ?>', this)">
                </div>

                <!-- Error feedback message area -->
                <div id="error-msg-<?= $doc['type'] ?>" class="hidden mt-2 p-2 rounded-lg bg-red-50 border border-red-200 text-xs text-red-600 font-medium flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-red-500 shrink-0" style="width:14px;height:14px;"></i>
                    <span id="error-text-<?= $doc['type'] ?>"></span>
                </div>

            </div>
        <?php endforeach; ?>
    </div>

    <!-- Bottom Actions: Proceed Button (clickable only when all 6 uploaded) & Return Button -->
    <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
        <a href="/applicant/application" 
           class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
            <span>Return to Application Workspace</span>
        </a>

        <!-- Bottom Proceed Button -->
        <a id="btn-proceed-bottom" 
           href="/applicant/application" 
           class="group relative inline-flex items-center justify-center gap-2 rounded-xl h-11 px-6 text-sm font-semibold shadow-md transition-all active:scale-[0.99] <?= $allRequiredUploaded ? 'bg-gradient-to-r from-emerald-600 to-teal-700 text-white hover:shadow-lg cursor-pointer' : 'bg-slate-200 text-slate-400 opacity-60 cursor-not-allowed pointer-events-none' ?>">
            <span id="btn-proceed-bottom-text"><?= $allRequiredUploaded ? 'All Documents Ready / Proceed' : 'Select All Required Documents (' . (6 - $uploadedCount) . ' Remaining)' ?></span>
            <i data-lucide="arrow-right" class="w-4 h-4 shrink-0" style="width:16px;height:16px;"></i>
        </a>
    </div>

</div>

<script>
    const WARD_ID = <?= (int)$ward->id ?>;
    
    function getCsrfToken() {
        const tokenInput = document.querySelector('#global-csrf-form input[name="_csrf_token"]')
            || document.querySelector('#global-csrf-form input[name="_token"]')
            || document.querySelector('input[name="_csrf_token"]');
        return tokenInput ? tokenInput.value : '<?= csrf_token() ?>';
    }

    async function handleAsyncFileUpload(docType, inputEl) {
        const file = inputEl.files[0];
        if (!file) return;

        const card = document.getElementById(`card-${docType}`);
        const iconBox = document.getElementById(`icon-box-${docType}`);
        const statusBadge = document.getElementById(`status-badge-${docType}`);
        const fileLabel = document.getElementById(`file-label-${docType}`);
        const spinner = document.getElementById(`spinner-${docType}`);
        const errorContainer = document.getElementById(`error-msg-${docType}`);
        const errorText = document.getElementById(`error-text-${docType}`);
        const previewContainer = document.getElementById(`preview-container-${docType}`);
        const previewLink = document.getElementById(`preview-link-${docType}`);

        // Reset error
        errorContainer.classList.add('hidden');
        errorText.textContent = '';

        // Show uploading spinner & lock input
        spinner.classList.remove('hidden');
        spinner.classList.add('flex');
        inputEl.disabled = true;

        const token = getCsrfToken();
        const formData = new FormData();
        formData.append('_csrf_token', token);
        formData.append('_token', token);
        formData.append('document_type', docType);
        formData.append('document', file);

        try {
            const response = await fetch(`/applicant/wards/${WARD_ID}/documents`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token
                },
                body: formData
            });

            let data = {};
            try {
                data = await response.json();
            } catch (e) {
                // If response is not JSON
            }

            if (response.ok && data.success) {
                // Success: update state
                card.setAttribute('data-uploaded', 'true');
                
                // Border green & green background
                card.className = 'doc-card bg-white rounded-2xl border transition-all duration-300 p-5 sm:p-6 shadow-xs border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/20';
                
                // Icon green check
                iconBox.className = 'size-11 rounded-xl transition-all duration-300 flex items-center justify-center shrink-0 bg-emerald-100 text-emerald-700';
                iconBox.innerHTML = '<i data-lucide="check-circle-2" class="w-5 h-5" style="width:20px;height:20px;"></i>';

                // Status badge
                statusBadge.className = 'text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border text-emerald-700 bg-emerald-50 border-emerald-200';
                statusBadge.textContent = 'Uploaded';

                // Label
                fileLabel.textContent = `Uploaded: ${file.name} (Click to replace)`;

                // Preview Link
                if (data.stream_url) {
                    previewLink.href = data.stream_url;
                    previewContainer.classList.remove('hidden');
                }

                if (window.lucide) {
                    lucide.createIcons();
                }

                // Recalculate all cards state
                updateAllProceedButtons();

            } else {
                const errorMsg = data.message || data.error || (response.statusText ? `Upload failed (${response.status}: ${response.statusText})` : 'File upload failed. Please try again.');
                throw new Error(errorMsg);
            }
        } catch (err) {
            errorText.textContent = err.message || 'An error occurred during upload.';
            errorContainer.classList.remove('hidden');
        } finally {
            spinner.classList.add('hidden');
            spinner.classList.remove('flex');
            inputEl.disabled = false;
        }
    }

    function updateAllProceedButtons() {
        const cards = document.querySelectorAll('.doc-card');
        let uploaded = 0;
        cards.forEach(c => {
            if (c.getAttribute('data-uploaded') === 'true') {
                uploaded++;
            }
        });

        const total = cards.length;
        const allReady = (uploaded === total);

        // Counter text
        const counterText = document.getElementById('doc-counter-text');
        if (counterText) {
            counterText.textContent = `${uploaded} of ${total} Required Documents Uploaded`;
            counterText.parentElement.className = `inline-flex items-center gap-1 text-[11px] font-semibold ${allReady ? 'text-emerald-700' : 'text-amber-700'}`;
        }

        // Top button
        const btnTop = document.getElementById('btn-proceed-top');
        const btnTopText = document.getElementById('btn-proceed-top-text');
        if (btnTop && btnTopText) {
            if (allReady) {
                btnTop.className = 'group inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-semibold shadow-xs transition-all bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer';
                btnTopText.textContent = 'All Documents Ready / Proceed';
            } else {
                btnTop.className = 'group inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-semibold shadow-xs transition-all bg-slate-200 text-slate-400 opacity-60 cursor-not-allowed pointer-events-none';
                btnTopText.textContent = `Upload Remaining (${total - uploaded})`;
            }
        }

        // Bottom button
        const btnBottom = document.getElementById('btn-proceed-bottom');
        const btnBottomText = document.getElementById('btn-proceed-bottom-text');
        if (btnBottom && btnBottomText) {
            if (allReady) {
                btnBottom.className = 'group relative inline-flex items-center justify-center gap-2 rounded-xl h-11 px-6 text-sm font-semibold shadow-md transition-all active:scale-[0.99] bg-gradient-to-r from-emerald-600 to-teal-700 text-white hover:shadow-lg cursor-pointer';
                btnBottomText.textContent = 'All Documents Ready / Proceed';
            } else {
                btnBottom.className = 'group relative inline-flex items-center justify-center gap-2 rounded-xl h-11 px-6 text-sm font-semibold shadow-md transition-all active:scale-[0.99] bg-slate-200 text-slate-400 opacity-60 cursor-not-allowed pointer-events-none';
                btnBottomText.textContent = `Select All Required Documents (${total - uploaded} Remaining)`;
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }
        updateAllProceedButtons();
    });
</script>
