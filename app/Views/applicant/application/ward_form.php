<?php
/**
 * Add / Edit Prospective Ward Form
 * 
 * @var \App\Models\AdmissionWard|null $ward
 * @var \App\Models\AdmissionApplication $application
 * @var \App\Models\AdmissionSession $session
 * @var \App\Models\AcademicLevel[] $levels
 * @var \App\Models\User $user
 * @var array $errors
 */
$this->layout('layouts/applicant', ['title' => $ward ? 'Edit Ward Details' : 'Add Prospective Ward']);

$isEdit = $ward !== null;
$actionUrl = $isEdit ? "/applicant/wards/{$ward->id}" : "/applicant/wards";
?>

<div class="max-w-3xl mx-auto space-y-6">

    <!-- Top Navigation Breadcrumb -->
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="/applicant/application" class="hover:text-[#7B3046] transition-colors flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
            <span>Back to Application Docket</span>
        </a>
        <span>/</span>
        <span class="text-slate-800 font-semibold"><?= $isEdit ? 'Edit Ward' : 'Add Ward' ?></span>
    </div>

    <!-- Main Card Container -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
        
        <div class="mb-6 pb-5 border-b border-slate-100">
            <h1 class="font-serif text-2xl font-bold text-slate-900 tracking-tight">
                <?= $isEdit ? 'Edit Prospective Ward Details' : 'Add Prospective Ward' ?>
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Provide accurate details for the prospective student applying for admission to Claret International School.
            </p>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="mb-6 rounded-xl bg-red-50 border border-red-200 p-4 text-xs text-red-700 flex items-center gap-2.5">
                <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 shrink-0" style="width:16px;height:16px;"></i>
                <span><?= e($errors['general'][0]) ?></span>
            </div>
        <?php endif; ?>

        <form action="<?= $actionUrl ?>" method="POST" enctype="multipart/form-data" class="space-y-6" novalidate>
            <?= csrf_field() ?>

            <!-- Section 1: Basic Identity -->
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5">
                    <i data-lucide="user" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                    <span>Ward Personal Information</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- First Name -->
                    <div>
                        <label for="first_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            First Name <span class="text-red-500">*</span>
                        </label>
                        <input id="first_name" 
                               name="first_name" 
                               type="text" 
                               value="<?= e(old('first_name', $ward?->firstName ?? '')) ?>" 
                               required 
                               placeholder="e.g. David"
                               class="w-full px-3.5 py-2.5 text-sm rounded-xl border <?= !empty($errors['first_name']) ? 'border-red-400 bg-red-50/30' : 'border-slate-200 bg-slate-50/50' ?> text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all">
                        <?php if (!empty($errors['first_name'])): ?>
                            <p class="mt-1 text-xs text-red-600 font-medium"><?= e($errors['first_name'][0]) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Middle Name -->
                    <div>
                        <label for="middle_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Middle Name
                        </label>
                        <input id="middle_name" 
                               name="middle_name" 
                               type="text" 
                               value="<?= e(old('middle_name', $ward?->middleName ?? '')) ?>" 
                               placeholder="e.g. Chukwuemeka"
                               class="w-full px-3.5 py-2.5 text-sm rounded-xl border <?= !empty($errors['middle_name']) ? 'border-red-400 bg-red-50/30' : 'border-slate-200 bg-slate-50/50' ?> text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all">
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label for="last_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Last Name <span class="text-red-500">*</span>
                        </label>
                        <input id="last_name" 
                               name="last_name" 
                               type="text" 
                               value="<?= e(old('last_name', $ward?->lastName ?? '')) ?>" 
                               required 
                               placeholder="e.g. Titilayo"
                               class="w-full px-3.5 py-2.5 text-sm rounded-xl border <?= !empty($errors['last_name']) ? 'border-red-400 bg-red-50/30' : 'border-slate-200 bg-slate-50/50' ?> text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all">
                        <?php if (!empty($errors['last_name'])): ?>
                            <p class="mt-1 text-xs text-red-600 font-medium"><?= e($errors['last_name'][0]) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <!-- Date of Birth -->
                    <div>
                        <label for="date_of_birth" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Date of Birth <span class="text-red-500">*</span>
                        </label>
                        <input id="date_of_birth" 
                               name="date_of_birth" 
                               type="date" 
                               max="<?= date('Y-m-d') ?>"
                               value="<?= e(old('date_of_birth', $ward?->dateOfBirth ?? '')) ?>" 
                               required 
                               class="w-full px-3.5 py-2.5 text-sm rounded-xl border <?= !empty($errors['date_of_birth']) ? 'border-red-400 bg-red-50/30' : 'border-slate-200 bg-slate-50/50' ?> text-slate-900 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all">
                        <?php if (!empty($errors['date_of_birth'])): ?>
                            <p class="mt-1 text-xs text-red-600 font-medium"><?= e($errors['date_of_birth'][0]) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Gender -->
                    <div>
                        <label for="gender" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Gender <span class="text-red-500">*</span>
                        </label>
                        <select id="gender" 
                                name="gender" 
                                required 
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl border <?= !empty($errors['gender']) ? 'border-red-400 bg-red-50/30' : 'border-slate-200 bg-slate-50/50' ?> text-slate-900 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all">
                            <option value="">Select Gender</option>
                            <option value="male" <?= old('gender', $ward?->gender) === 'male' ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= old('gender', $ward?->gender) === 'female' ? 'selected' : '' ?>>Female</option>
                        </select>
                        <?php if (!empty($errors['gender'])): ?>
                            <p class="mt-1 text-xs text-red-600 font-medium"><?= e($errors['gender'][0]) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                    <!-- Nationality -->
                    <div>
                        <label for="nationality" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Nationality
                        </label>
                        <input id="nationality" 
                               name="nationality" 
                               type="text" 
                               value="<?= e(old('nationality', $ward?->nationality ?? 'Nigerian')) ?>" 
                               placeholder="e.g. Nigerian"
                               class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all">
                    </div>

                    <!-- State of Origin -->
                    <div>
                        <label for="state_of_origin" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            State of Origin
                        </label>
                        <input id="state_of_origin" 
                               name="state_of_origin" 
                               type="text" 
                               value="<?= e(old('state_of_origin', $ward?->stateOfOrigin ?? '')) ?>" 
                               placeholder="e.g. Imo, Lagos, Abuja FCT"
                               class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all">
                    </div>

                    <!-- LGA -->
                    <div>
                        <label for="lga" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            L.G.A.
                        </label>
                        <input id="lga" 
                               name="lga" 
                               type="text" 
                               value="<?= e(old('lga', $ward?->lga ?? '')) ?>" 
                               placeholder="e.g. Owerri Municipal, Ikeja"
                               class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all">
                    </div>

                    <!-- Religion -->
                    <div>
                        <label for="religion" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Religion
                        </label>
                        <select id="religion" 
                                name="religion" 
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all">
                            <?php $currRel = old('religion', $ward?->religion ?? ''); ?>
                            <option value="">Select Religion</option>
                            <option value="Christianity" <?= $currRel === 'Christianity' ? 'selected' : '' ?>>Christianity</option>
                            <option value="Islam" <?= $currRel === 'Islam' ? 'selected' : '' ?>>Islam</option>
                            <option value="Other" <?= $currRel === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2: Enrollment & Academics -->
            <div class="pt-4 border-t border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5">
                    <i data-lucide="graduation-cap" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                    <span>Enrollment &amp; Program Options</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Academic Level -->
                    <div>
                        <label for="applying_for_level_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Academic Level <span class="text-red-500">*</span>
                        </label>
                        <select id="applying_for_level_id" 
                                name="applying_for_level_id" 
                                required 
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl border <?= !empty($errors['applying_for_level_id']) ? 'border-red-400 bg-red-50/30' : 'border-slate-200 bg-slate-50/50' ?> text-slate-900 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all">
                            <option value="">Select Academic Level</option>
                            <?php foreach ($levels as $lvl): ?>
                                <option value="<?= $lvl->id ?>" <?= (int)old('applying_for_level_id', (string)($ward?->applyingForLevelId ?? '')) === $lvl->id ? 'selected' : '' ?>>
                                    <?= e($lvl->name) ?> (<?= e(ucfirst($lvl->stage)) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['applying_for_level_id'])): ?>
                            <p class="mt-1 text-xs text-red-600 font-medium"><?= e($errors['applying_for_level_id'][0]) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Class Grade (Dropdown Select) -->
                    <div>
                        <label for="class_grade" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Class Grade / Class Year <span class="text-red-500">*</span>
                        </label>
                        <?php $selectedGrade = old('class_grade', $ward?->classGrade ?? ''); ?>
                        <select id="class_grade" 
                                name="class_grade" 
                                required 
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl border <?= !empty($errors['class_grade']) ? 'border-red-400 bg-red-50/30' : 'border-slate-200 bg-slate-50/50' ?> text-slate-900 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all">
                            <option value="">Select Grade to Enter</option>
                            <optgroup label="Early Years Foundation (EYFS)">
                                <option value="Creche" <?= $selectedGrade === 'Creche' ? 'selected' : '' ?>>Creche / Daycare</option>
                                <option value="Pre-Nursery" <?= $selectedGrade === 'Pre-Nursery' ? 'selected' : '' ?>>Pre-Nursery</option>
                                <option value="Nursery 1" <?= $selectedGrade === 'Nursery 1' ? 'selected' : '' ?>>Nursery 1</option>
                                <option value="Nursery 2" <?= $selectedGrade === 'Nursery 2' ? 'selected' : '' ?>>Nursery 2</option>
                            </optgroup>
                            <optgroup label="Primary School">
                                <option value="Primary 1" <?= $selectedGrade === 'Primary 1' ? 'selected' : '' ?>>Primary 1</option>
                                <option value="Primary 2" <?= $selectedGrade === 'Primary 2' ? 'selected' : '' ?>>Primary 2</option>
                                <option value="Primary 3" <?= $selectedGrade === 'Primary 3' ? 'selected' : '' ?>>Primary 3</option>
                                <option value="Primary 4" <?= $selectedGrade === 'Primary 4' ? 'selected' : '' ?>>Primary 4</option>
                                <option value="Primary 5" <?= $selectedGrade === 'Primary 5' ? 'selected' : '' ?>>Primary 5</option>
                                <option value="Primary 6" <?= $selectedGrade === 'Primary 6' ? 'selected' : '' ?>>Primary 6</option>
                            </optgroup>
                            <optgroup label="Junior Secondary School">
                                <option value="JSS 1" <?= $selectedGrade === 'JSS 1' ? 'selected' : '' ?>>JSS 1 (Grade 7 / Year 7)</option>
                                <option value="JSS 2" <?= $selectedGrade === 'JSS 2' ? 'selected' : '' ?>>JSS 2 (Grade 8 / Year 8)</option>
                                <option value="JSS 3" <?= $selectedGrade === 'JSS 3' ? 'selected' : '' ?>>JSS 3 (Grade 9 / Year 9)</option>
                            </optgroup>
                            <optgroup label="Senior Secondary School">
                                <option value="SS 1" <?= $selectedGrade === 'SS 1' ? 'selected' : '' ?>>SS 1 (Grade 10 / Year 10)</option>
                                <option value="SS 2" <?= $selectedGrade === 'SS 2' ? 'selected' : '' ?>>SS 2 (Grade 11 / Year 11)</option>
                                <option value="SS 3" <?= $selectedGrade === 'SS 3' ? 'selected' : '' ?>>SS 3 (Grade 12 / Year 12)</option>
                            </optgroup>
                            <?php if (!empty($selectedGrade) && !in_array($selectedGrade, ['Creche', 'Pre-Nursery', 'Nursery 1', 'Nursery 2', 'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6', 'JSS 1', 'JSS 2', 'JSS 3', 'SS 1', 'SS 2', 'SS 3'])): ?>
                                <optgroup label="Current Value">
                                    <option value="<?= e($selectedGrade) ?>" selected><?= e($selectedGrade) ?></option>
                                </optgroup>
                            <?php endif; ?>
                        </select>
                        <?php if (!empty($errors['class_grade'])): ?>
                            <p class="mt-1 text-xs text-red-600 font-medium"><?= e($errors['class_grade'][0]) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <!-- Curriculum Preference (Fixed Default Nigerian & British Integrated) -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Curriculum Preference
                        </label>
                        <input type="hidden" name="curriculum_choice" value="Dual Nigerian & British Integrated">
                        <div class="px-3.5 py-3 rounded-xl border border-rose-200 bg-rose-50/40 text-slate-900 flex items-start gap-2.5">
                            <i data-lucide="award" class="w-5 h-5 text-[#7B3046] shrink-0 mt-0.5" style="width:20px;height:20px;"></i>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-[#7B3046]">Dual Nigerian &amp; British Integrated</span>
                                    <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-[#7B3046] text-white">Default</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                    Claret International School operates exclusively on the unified Nigerian-British curriculum standard.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- School Bus Requirement -->
                    <div class="flex items-center pt-6">
                        <label class="flex items-center gap-3 cursor-pointer select-none">
                            <input type="checkbox" 
                                   name="use_school_bus" 
                                   value="1" 
                                   <?= old('use_school_bus', $ward?->useSchoolBus ? '1' : '0') === '1' ? 'checked' : '' ?>
                                   class="w-4 h-4 rounded border-slate-300 text-[#7B3046] focus:ring-[#7B3046] transition cursor-pointer">
                            <span class="text-xs font-semibold text-slate-700">Prospective ward requires school bus transportation</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Section 3: Educational Background & Health (Strictly Required) -->
            <div class="pt-4 border-t border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5">
                    <i data-lucide="book-open" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                    <span>Educational Background &amp; Health</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Previous School (Required) -->
                    <div>
                        <label for="previous_school" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Previous School Attended <span class="text-red-500">*</span>
                        </label>
                        <input id="previous_school" 
                               name="previous_school" 
                               type="text" 
                               value="<?= e(old('previous_school', $ward?->previousSchool ?? '')) ?>" 
                               required
                               placeholder="e.g. St. Jude Academy (or 'None - First School')"
                               class="w-full px-3.5 py-2.5 text-sm rounded-xl border <?= !empty($errors['previous_school']) ? 'border-red-400 bg-red-50/30' : 'border-slate-200 bg-slate-50/50' ?> text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all">
                        <?php if (!empty($errors['previous_school'])): ?>
                            <p class="mt-1 text-xs text-red-600 font-medium"><?= e($errors['previous_school'][0]) ?></p>
                        <?php else: ?>
                            <span class="text-[11px] text-slate-400 mt-1 block">Specify "None / First Entry" if applying for early childhood.</span>
                        <?php endif; ?>
                    </div>

                    <!-- Last Grade Passed (Required) -->
                    <div>
                        <label for="last_grade_passed" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Last Grade Completed <span class="text-red-500">*</span>
                        </label>
                        <input id="last_grade_passed" 
                               name="last_grade_passed" 
                               type="text" 
                               value="<?= e(old('last_grade_passed', $ward?->lastGradePassed ?? '')) ?>" 
                               required
                               placeholder="e.g. Primary 4, Nursery 2, or 'None'"
                               class="w-full px-3.5 py-2.5 text-sm rounded-xl border <?= !empty($errors['last_grade_passed']) ? 'border-red-400 bg-red-50/30' : 'border-slate-200 bg-slate-50/50' ?> text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all">
                        <?php if (!empty($errors['last_grade_passed'])): ?>
                            <p class="mt-1 text-xs text-red-600 font-medium"><?= e($errors['last_grade_passed'][0]) ?></p>
                        <?php else: ?>
                            <span class="text-[11px] text-slate-400 mt-1 block">Specify "None" for new early years entrants.</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Medical Notes -->
                <div class="mt-4">
                    <label for="medical_notes" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Medical Allergies, Dietary, or Learning Support Notes (Optional)
                    </label>
                    <textarea id="medical_notes" 
                              name="medical_notes" 
                              rows="2" 
                              placeholder="Describe any known medical conditions, severe allergies, or special learning accommodations needed..."
                              class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all"><?= e(old('medical_notes', $ward?->medicalNotes ?? '')) ?></textarea>
                </div>
            </div>

            <!-- Section 4: Initial Document Uploads (Ward Passport, Parent Passport, Immunization Record) -->
            <div class="pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                        <i data-lucide="paperclip" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                        <span>Immediate Document Attachments</span>
                    </h3>
                    <span class="text-[11px] text-slate-400">Can also be uploaded/updated in the documents step</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Ward Passport Photograph -->
                    <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/40">
                        <label for="passport_photo" class="block text-xs font-bold text-slate-800 mb-1 flex items-center justify-between">
                            <span>Ward Passport</span>
                            <?php if (!empty($ward?->passportPhotoFileId)): ?>
                                <span class="text-[10px] text-emerald-600 font-bold flex items-center gap-0.5">
                                    <i data-lucide="check-circle" class="w-3 h-3" style="width:12px;height:12px;"></i> Uploaded
                                </span>
                            <?php endif; ?>
                        </label>
                        <p class="text-[11px] text-slate-500 mb-2">Student passport photo (JPG, PNG)</p>
                        <input id="passport_photo" 
                               name="passport_photo" 
                               type="file" 
                               accept=".jpg,.jpeg,.png"
                               class="w-full text-[11px] text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-slate-200 file:text-slate-800 hover:file:bg-slate-300 cursor-pointer">
                        <?php if (!empty($ward?->passportPhotoFileId)): ?>
                            <a href="/files/<?= $ward->passportPhotoFileId ?>/stream" target="_blank" class="mt-2 inline-flex items-center gap-1 text-[11px] font-semibold text-[#7B3046] hover:underline">
                                <i data-lucide="eye" class="w-3 h-3" style="width:12px;height:12px;"></i> View Current
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Parent Passport Photograph -->
                    <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/40">
                        <label for="parent_passport" class="block text-xs font-bold text-slate-800 mb-1 flex items-center justify-between">
                            <span>Parent Passport</span>
                            <?php if (!empty($ward?->parentPassportFileId)): ?>
                                <span class="text-[10px] text-emerald-600 font-bold flex items-center gap-0.5">
                                    <i data-lucide="check-circle" class="w-3 h-3" style="width:12px;height:12px;"></i> Uploaded
                                </span>
                            <?php endif; ?>
                        </label>
                        <p class="text-[11px] text-slate-500 mb-2">Parent/Guardian photo (JPG, PNG)</p>
                        <input id="parent_passport" 
                               name="parent_passport" 
                               type="file" 
                               accept=".jpg,.jpeg,.png"
                               class="w-full text-[11px] text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-slate-200 file:text-slate-800 hover:file:bg-slate-300 cursor-pointer">
                        <?php if (!empty($ward?->parentPassportFileId)): ?>
                            <a href="/files/<?= $ward->parentPassportFileId ?>/stream" target="_blank" class="mt-2 inline-flex items-center gap-1 text-[11px] font-semibold text-[#7B3046] hover:underline">
                                <i data-lucide="eye" class="w-3 h-3" style="width:12px;height:12px;"></i> View Current
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Immunization Record -->
                    <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/40">
                        <label for="immunization_record" class="block text-xs font-bold text-slate-800 mb-1 flex items-center justify-between">
                            <span>Immunization Card</span>
                            <?php if (!empty($ward?->immunizationRecordFileId)): ?>
                                <span class="text-[10px] text-emerald-600 font-bold flex items-center gap-0.5">
                                    <i data-lucide="check-circle" class="w-3 h-3" style="width:12px;height:12px;"></i> Uploaded
                                </span>
                            <?php endif; ?>
                        </label>
                        <p class="text-[11px] text-slate-500 mb-2">Vaccine record (PDF, JPG, PNG)</p>
                        <input id="immunization_record" 
                               name="immunization_record" 
                               type="file" 
                               accept=".pdf,.jpg,.jpeg,.png"
                               class="w-full text-[11px] text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-slate-200 file:text-slate-800 hover:file:bg-slate-300 cursor-pointer">
                        <?php if (!empty($ward?->immunizationRecordFileId)): ?>
                            <a href="/files/<?= $ward->immunizationRecordFileId ?>/stream" target="_blank" class="mt-2 inline-flex items-center gap-1 text-[11px] font-semibold text-[#7B3046] hover:underline">
                                <i data-lucide="eye" class="w-3 h-3" style="width:12px;height:12px;"></i> View Current
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <a href="/applicant/application" 
                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">
                    Cancel
                </a>

                <button type="submit" 
                        class="group relative inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#7B3046] via-[#8B3650] to-[#A33D5E] h-11 px-6 text-sm font-semibold text-white shadow-md shadow-[#7B3046]/20 transition-all duration-200 ease-out hover:shadow-lg hover:shadow-[#7B3046]/30 active:scale-[0.99] cursor-pointer">
                    <span><?= $isEdit ? 'Save Ward Details' : 'Continue to Application Fee' ?></span>
                    <span class="inline-flex items-center justify-center max-w-0 opacity-0 -translate-x-2 group-hover:max-w-[20px] group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-200 ease-out overflow-hidden">
                        <i data-lucide="arrow-right" class="w-4 h-4 shrink-0" style="width:16px;height:16px;"></i>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>
