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

        <form action="<?= $actionUrl ?>" method="POST" class="space-y-6" novalidate>
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
                            <option value="">Select Level</option>
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

                    <!-- Class Grade -->
                    <div>
                        <label for="class_grade" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Class Grade <span class="text-red-500">*</span>
                        </label>
                        <input id="class_grade" 
                               name="class_grade" 
                               type="text" 
                               value="<?= e(old('class_grade', $ward?->classGrade ?? '')) ?>" 
                               required 
                               placeholder="e.g. Primary 1, JSS 1, Nursery 2"
                               class="w-full px-3.5 py-2.5 text-sm rounded-xl border <?= !empty($errors['class_grade']) ? 'border-red-400 bg-red-50/30' : 'border-slate-200 bg-slate-50/50' ?> text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all">
                        <?php if (!empty($errors['class_grade'])): ?>
                            <p class="mt-1 text-xs text-red-600 font-medium"><?= e($errors['class_grade'][0]) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <!-- Curriculum Choice -->
                    <div>
                        <label for="curriculum_choice" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Curriculum Preference
                        </label>
                        <select id="curriculum_choice" 
                                name="curriculum_choice" 
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all">
                            <option value="Dual Nigerian &amp; British Integrated" <?= old('curriculum_choice', $ward?->curriculumChoice) === 'Dual Nigerian & British Integrated' ? 'selected' : '' ?>>
                                Dual Nigerian &amp; British Integrated (Recommended)
                            </option>
                            <option value="British / Cambridge International" <?= old('curriculum_choice', $ward?->curriculumChoice) === 'British / Cambridge International' ? 'selected' : '' ?>>
                                British / Cambridge International
                            </option>
                            <option value="Nigerian National Standard" <?= old('curriculum_choice', $ward?->curriculumChoice) === 'Nigerian National Standard' ? 'selected' : '' ?>>
                                Nigerian National Standard
                            </option>
                        </select>
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

            <!-- Section 3: Background & Medical Notes -->
            <div class="pt-4 border-t border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5">
                    <i data-lucide="book-open" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                    <span>Educational Background &amp; Health</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Previous School -->
                    <div>
                        <label for="previous_school" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Previous School Attended (If any)
                        </label>
                        <input id="previous_school" 
                               name="previous_school" 
                               type="text" 
                               value="<?= e(old('previous_school', $ward?->previousSchool ?? '')) ?>" 
                               placeholder="e.g. St. Jude International Academy"
                               class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all">
                    </div>

                    <!-- Last Grade Passed -->
                    <div>
                        <label for="last_grade_passed" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Last Grade Completed
                        </label>
                        <input id="last_grade_passed" 
                               name="last_grade_passed" 
                               type="text" 
                               value="<?= e(old('last_grade_passed', $ward?->lastGradePassed ?? '')) ?>" 
                               placeholder="e.g. Nursery 2 / Reception"
                               class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all">
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
